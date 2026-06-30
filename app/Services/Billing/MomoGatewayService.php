<?php

namespace App\Services\Billing;

use App\Exceptions\BillingException;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\NguoiDung;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class MomoGatewayService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function createTopUpPayment(NguoiDung $user, int $amount): array
    {
        $this->ensureConfigured();

        $minAmount = (int) config('services.momo.min_amount', 1000);
        if ($amount < $minAmount) {
            throw BillingException::invalidPaymentAmount($minAmount);
        }

        return $this->createPayment($user, $amount, [
            'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
            'goi_dich_vu_id' => null,
            'order_prefix' => 'TOPUP',
            'order_info' => "Nap vi AI {$amount} VND",
            'extra_data' => [
                'nguoi_dung_id' => $user->id,
                'purpose' => GiaoDichThanhToan::LOAI_NAP_VI,
            ],
            'items' => [[
                'id' => 'wallet-topup',
                'name' => 'Nap tien vi AI',
                'quantity' => 1,
                'amount' => $amount,
            ]],
        ]);
    }

    public function createSubscriptionPayment(NguoiDung $user, GoiDichVu $plan): array
    {
        $this->ensureConfigured();

        if ($plan->trang_thai !== GoiDichVu::TRANG_THAI_HOAT_DONG || $plan->is_free) {
            throw BillingException::invalidSubscriptionPlan($plan->ma_goi);
        }

        return $this->createPayment($user, (int) $plan->gia, [
            'loai_giao_dich' => GiaoDichThanhToan::LOAI_MUA_GOI,
            'goi_dich_vu_id' => $plan->id,
            'order_prefix' => 'SUB',
            'order_info' => 'Mua goi ' . $plan->ten_goi,
            'extra_data' => [
                'nguoi_dung_id' => $user->id,
                'purpose' => GiaoDichThanhToan::LOAI_MUA_GOI,
                'goi_dich_vu_id' => $plan->id,
                'ma_goi' => $plan->ma_goi,
            ],
            'items' => [[
                'id' => 'subscription-' . strtolower($plan->ma_goi),
                'name' => $plan->ten_goi,
                'quantity' => 1,
                'amount' => (int) $plan->gia,
            ]],
        ]);
    }

    public function queryPayment(GiaoDichThanhToan $payment): array
    {
        $this->ensureConfigured();

        if ($payment->gateway !== GiaoDichThanhToan::GATEWAY_MOMO) {
            throw new RuntimeException('Giao dịch không thuộc cổng MoMo.');
        }

        $requestId = $this->newRequestId((int) $payment->nguoi_dung_id);
        $payload = [
            'partnerCode' => (string) config('services.momo.partner_code'),
            'requestId' => $requestId,
            'orderId' => (string) $payment->ma_giao_dich_noi_bo,
            'lang' => (string) config('services.momo.lang', 'vi'),
        ];
        $payload['signature'] = $this->signQueryPayload($payload);

        try {
            return Http::timeout(max(30, (int) config('services.momo.timeout', 30)))
                ->acceptJson()
                ->asJson()
                ->post(rtrim((string) config('services.momo.base_url'), '/') . '/v2/gateway/api/query', $payload)
                ->throw()
                ->json();
        } catch (\Throwable $exception) {
            throw new RuntimeException('Không thể đối soát trạng thái giao dịch MoMo lúc này.');
        }
    }

    public function handleIpn(array $payload): ?GiaoDichThanhToan
    {
        if (!$this->verifyNotificationSignature($payload)) {
            return null;
        }

        return DB::transaction(function () use ($payload): ?GiaoDichThanhToan {
            $payment = GiaoDichThanhToan::query()
                ->where('gateway', GiaoDichThanhToan::GATEWAY_MOMO)
                ->where('ma_giao_dich_noi_bo', (string) ($payload['orderId'] ?? ''))
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return null;
            }

            $payment->forceFill([
                'ma_giao_dich_gateway' => isset($payload['transId']) ? (string) $payload['transId'] : $payment->ma_giao_dich_gateway,
                'ipn_payload_json' => $payload,
            ])->save();

            if ((int) $payload['amount'] !== (int) $payment->so_tien) {
                $payment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();

                return $payment;
            }

            if ($payment->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG) {
                return $payment;
            }

            if ((int) ($payload['resultCode'] ?? -1) === 0) {
                $payment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                    'paid_at' => now(),
                ])->save();

                $this->walletService->creditFromPayment($payment);
            } else {
                $payment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();
            }

            return $payment->fresh();
        });
    }

    public function recordReturnPayload(string $orderId, array $payload): ?GiaoDichThanhToan
    {
        $payment = GiaoDichThanhToan::query()
            ->where('gateway', GiaoDichThanhToan::GATEWAY_MOMO)
            ->where('ma_giao_dich_noi_bo', $orderId)
            ->first();

        if (!$payment) {
            return null;
        }

        $payment->forceFill([
            'return_payload_json' => $payload,
        ])->save();

        return $payment->fresh();
    }

    public function autoCompleteFromReturnForLocal(string $orderId, array $payload): ?GiaoDichThanhToan
    {
        if (!config('services.momo.auto_complete_return_local', false)) {
            return null;
        }

        if ((int) ($payload['resultCode'] ?? -1) !== 0) {
            return null;
        }

        return DB::transaction(function () use ($orderId, $payload): ?GiaoDichThanhToan {
            $payment = GiaoDichThanhToan::query()
                ->where('gateway', GiaoDichThanhToan::GATEWAY_MOMO)
                ->where('ma_giao_dich_noi_bo', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return null;
            }

            $payloadAmount = isset($payload['amount']) ? (int) $payload['amount'] : null;
            if ($payloadAmount !== null && $payloadAmount !== (int) $payment->so_tien) {
                $payment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();

                return $payment->fresh();
            }

            if ($payment->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG) {
                return $payment;
            }

            $payment->forceFill([
                'ma_giao_dich_gateway' => isset($payload['transId']) ? (string) $payload['transId'] : $payment->ma_giao_dich_gateway,
                'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                'paid_at' => now(),
            ])->save();

            $this->walletService->creditFromPayment($payment);

            return $payment->fresh();
        });
    }

    public function verifyNotificationSignature(array $payload): bool
    {
        $signature = (string) ($payload['signature'] ?? '');
        if ($signature === '') {
            return false;
        }

        $raw = implode('&', [
            'accessKey=' . (string) config('services.momo.access_key'),
            'amount=' . (string) ($payload['amount'] ?? ''),
            'extraData=' . (string) ($payload['extraData'] ?? ''),
            'message=' . (string) ($payload['message'] ?? ''),
            'orderId=' . (string) ($payload['orderId'] ?? ''),
            'orderInfo=' . (string) ($payload['orderInfo'] ?? ''),
            'orderType=' . (string) ($payload['orderType'] ?? 'momo_wallet'),
            'partnerCode=' . (string) ($payload['partnerCode'] ?? ''),
            'payType=' . (string) ($payload['payType'] ?? ''),
            'requestId=' . (string) ($payload['requestId'] ?? ''),
            'responseTime=' . (string) ($payload['responseTime'] ?? ''),
            'resultCode=' . (string) ($payload['resultCode'] ?? ''),
            'transId=' . (string) ($payload['transId'] ?? ''),
        ]);

        return hash_equals(hash_hmac('sha256', $raw, (string) config('services.momo.secret_key')), $signature);
    }

    private function signCreatePayload(array $payload): string
    {
        $raw = implode('&', [
            'accessKey=' . (string) config('services.momo.access_key'),
            'amount=' . (string) $payload['amount'],
            'extraData=' . (string) $payload['extraData'],
            'ipnUrl=' . (string) $payload['ipnUrl'],
            'orderId=' . (string) $payload['orderId'],
            'orderInfo=' . (string) $payload['orderInfo'],
            'partnerCode=' . (string) $payload['partnerCode'],
            'redirectUrl=' . (string) $payload['redirectUrl'],
            'requestId=' . (string) $payload['requestId'],
            'requestType=' . (string) $payload['requestType'],
        ]);

        return hash_hmac('sha256', $raw, (string) config('services.momo.secret_key'));
    }

    private function signQueryPayload(array $payload): string
    {
        $raw = implode('&', [
            'accessKey=' . (string) config('services.momo.access_key'),
            'orderId=' . (string) $payload['orderId'],
            'partnerCode=' . (string) $payload['partnerCode'],
            'requestId=' . (string) $payload['requestId'],
        ]);

        return hash_hmac('sha256', $raw, (string) config('services.momo.secret_key'));
    }

    private function ensureConfigured(): void
    {
        $required = [
            'base_url',
            'partner_code',
            'access_key',
            'secret_key',
        ];

        foreach ($required as $key) {
            if (!config('services.momo.' . $key)) {
                throw BillingException::paymentGatewayUnavailable(GiaoDichThanhToan::GATEWAY_MOMO);
            }
        }
    }

    private function newOrderId(int $userId): string
    {
        return 'TOPUP-' . $userId . '-' . Str::upper(Str::random(18));
    }

    private function newRequestId(int $userId): string
    {
        return 'REQ-' . $userId . '-' . Str::upper(Str::random(18));
    }

    private function createPayment(NguoiDung $user, int $amount, array $options): array
    {
        $wallet = $this->walletService->getOrCreateWallet($user);
        $orderId = $this->newCustomOrderId((string) $options['order_prefix'], $user->id);
        $requestId = $this->newRequestId($user->id);
        $extraData = base64_encode((string) json_encode(
            $options['extra_data'] ?? [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        $redirectUrl = (string) (config('services.momo.redirect_url') ?: URL::route('payments.momo.return'));
        $ipnUrl = (string) (config('services.momo.ipn_url') ?: URL::route('payments.momo.ipn'));
        $orderInfo = (string) $options['order_info'];

        $payment = GiaoDichThanhToan::create([
            'nguoi_dung_id' => $user->id,
            'vi_nguoi_dung_id' => $wallet->id,
            'goi_dich_vu_id' => $options['goi_dich_vu_id'] ?? null,
            'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
            'ma_giao_dich_noi_bo' => $orderId,
            'ma_yeu_cau' => $requestId,
            'loai_giao_dich' => (string) $options['loai_giao_dich'],
            'so_tien' => $amount,
            'noi_dung' => $orderInfo,
            'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
        ]);

        $payload = [
            'partnerCode' => (string) config('services.momo.partner_code'),
            'partnerName' => (string) config('services.momo.partner_name', config('app.name', 'KhanhMai')),
            'storeId' => (string) config('services.momo.store_id', 'KhanhMai'),
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => (string) config('services.momo.lang', 'vi'),
            'requestType' => (string) config('services.momo.request_type', 'captureWallet'),
            'autoCapture' => true,
            'extraData' => $extraData,
            'items' => $options['items'] ?? [],
            'userInfo' => array_filter([
                'name' => $user->ho_ten,
                'phoneNumber' => $user->so_dien_thoai,
                'email' => $user->email,
            ]),
        ];
        $payload['signature'] = $this->signCreatePayload($payload);

        $payment->forceFill([
            'raw_request_json' => $payload,
        ])->save();

        try {
            $response = Http::timeout(max(30, (int) config('services.momo.timeout', 30)))
                ->acceptJson()
                ->asJson()
                ->post(rtrim((string) config('services.momo.base_url'), '/') . '/v2/gateway/api/create', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $payment->forceFill([
                'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                'raw_response_json' => [
                    'message' => $exception->getMessage(),
                ],
            ])->save();

            throw new RuntimeException('Không thể khởi tạo giao dịch MoMo lúc này.');
        }

        $resultCode = (int) ($response['resultCode'] ?? -1);
        $payUrl = $response['payUrl'] ?? $response['deeplink'] ?? $response['qrCodeUrl'] ?? null;

        $payment->forceFill([
            'raw_response_json' => $response,
            'redirect_url' => is_string($payUrl) ? $payUrl : null,
            'trang_thai' => $resultCode === 0 && $payUrl ? GiaoDichThanhToan::TRANG_THAI_PENDING : GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
        ])->save();

        if ($resultCode !== 0 || !$payUrl) {
            throw new RuntimeException((string) ($response['message'] ?? 'MoMo không trả về liên kết thanh toán hợp lệ.'));
        }

        return [$payment->fresh(), $response];
    }

    private function newCustomOrderId(string $prefix, int $userId): string
    {
        return strtoupper($prefix) . '-' . $userId . '-' . Str::upper(Str::random(18));
    }
}
