<?php

namespace App\Services\Billing;

use App\Exceptions\BillingException;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\NguoiDung;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class VnpayGatewayService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function createTopUpPayment(NguoiDung $user, int $amount, ?string $bankCode = null, ?string $clientIp = null): array
    {
        $this->ensureConfigured();

        $minAmount = (int) config('services.vnpay.min_amount', 1000);
        if ($amount < $minAmount) {
            throw BillingException::invalidPaymentAmount($minAmount);
        }

        return $this->createPayment($user, $amount, [
            'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
            'goi_dich_vu_id' => null,
            'order_prefix' => 'VNPAYTOPUP',
            'order_info' => "Nap vi AI {$amount} VND",
            'bank_code' => $bankCode,
            'client_ip' => $clientIp,
        ]);
    }

    public function createSubscriptionPayment(NguoiDung $user, GoiDichVu $plan, ?string $bankCode = null, ?string $clientIp = null): array
    {
        $this->ensureConfigured();

        if ($plan->trang_thai !== GoiDichVu::TRANG_THAI_HOAT_DONG || $plan->is_free) {
            throw BillingException::invalidSubscriptionPlan($plan->ma_goi);
        }

        return $this->createPayment($user, (int) $plan->gia, [
            'loai_giao_dich' => GiaoDichThanhToan::LOAI_MUA_GOI,
            'goi_dich_vu_id' => $plan->id,
            'order_prefix' => 'VNPAYSUB',
            'order_info' => 'Mua goi ' . $plan->ten_goi,
            'bank_code' => $bankCode,
            'client_ip' => $clientIp,
        ]);
    }

    public function verifyNotificationSignature(array $payload): bool
    {
        $signature = (string) ($payload['vnp_SecureHash'] ?? '');
        if ($signature === '') {
            return false;
        }

        $inputData = $this->onlyVnpayFields($payload);
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        $secureHash = hash_hmac(
            'sha512',
            $this->buildHashData($inputData),
            (string) config('services.vnpay.hash_secret')
        );

        return hash_equals(strtolower($secureHash), strtolower($signature));
    }

    public function queryPayment(GiaoDichThanhToan $payment): array
    {
        $this->ensureConfigured();

        if ($payment->gateway !== GiaoDichThanhToan::GATEWAY_VNPAY) {
            throw new RuntimeException('Giao dịch không thuộc cổng VNPay.');
        }

        $payload = $this->buildQueryPayload($payment);
        $payload['vnp_SecureHash'] = $this->signQueryPayload($payload);

        try {
            $response = Http::timeout(max(30, (int) config('services.vnpay.timeout', 30)))
                ->acceptJson()
                ->asJson()
                ->post($this->resolveMerchantApiUrl(), $payload)
                ->throw()
                ->json();
        } catch (\Throwable $exception) {
            Log::error('vnpay.reconcile.http_failed', [
                'payment_id' => $payment->id,
                'ma_giao_dich_noi_bo' => $payment->ma_giao_dich_noi_bo,
                'gateway_transaction_id' => $payment->ma_giao_dich_gateway,
                'merchant_api_url' => $this->resolveMerchantApiUrl(),
                'payload' => $payload,
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('VNPay không phản hồi hoặc từ chối yêu cầu đối soát lúc này.');
        }

        if (
            is_array($response)
            && isset($response['vnp_ResponseCode'])
            && (string) $response['vnp_ResponseCode'] !== '00'
            && !isset($response['vnp_SecureHash'])
        ) {
            Log::info('vnpay.reconcile.gateway_error_response', [
                'payment_id' => $payment->id,
                'ma_giao_dich_noi_bo' => $payment->ma_giao_dich_noi_bo,
                'gateway_transaction_id' => $payment->ma_giao_dich_gateway,
                'payload' => $payload,
                'response' => $response,
            ]);

            return $response;
        }

        if (!is_array($response) || !$this->verifyQueryResponseSignature($response)) {
            Log::warning('vnpay.reconcile.invalid_response', [
                'payment_id' => $payment->id,
                'ma_giao_dich_noi_bo' => $payment->ma_giao_dich_noi_bo,
                'gateway_transaction_id' => $payment->ma_giao_dich_gateway,
                'payload' => $payload,
                'response' => $response,
            ]);

            throw new RuntimeException('VNPay trả về dữ liệu đối soát không hợp lệ.');
        }

        return $response;
    }

    private function createPayment(NguoiDung $user, int $amount, array $options): array
    {
        $wallet = $this->walletService->getOrCreateWallet($user);
        $txnRef = $this->newTxnRef((string) $options['order_prefix'], $user->id);
        $requestId = $this->newRequestId($user->id);
        $orderInfo = (string) $options['order_info'];

        $payment = GiaoDichThanhToan::create([
            'nguoi_dung_id' => $user->id,
            'vi_nguoi_dung_id' => $wallet->id,
            'goi_dich_vu_id' => $options['goi_dich_vu_id'] ?? null,
            'gateway' => GiaoDichThanhToan::GATEWAY_VNPAY,
            'ma_giao_dich_noi_bo' => $txnRef,
            'ma_yeu_cau' => $requestId,
            'loai_giao_dich' => (string) $options['loai_giao_dich'],
            'so_tien' => $amount,
            'noi_dung' => $orderInfo,
            'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
        ]);

        $now = CarbonImmutable::now('Asia/Ho_Chi_Minh');
        $expireMinutes = max(1, (int) config('services.vnpay.pending_expire_minutes', 15));

        $payload = array_filter([
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => (string) config('services.vnpay.tmn_code'),
            'vnp_Amount' => (string) ($amount * 100),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => (string) config('services.vnpay.order_type', 'other'),
            'vnp_Locale' => (string) config('services.vnpay.locale', 'vn'),
            'vnp_ReturnUrl' => (string) (config('services.vnpay.return_url') ?: URL::route('payments.vnpay.return')),
            'vnp_IpAddr' => (string) ($options['client_ip'] ?: '127.0.0.1'),
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_ExpireDate' => $now->addMinutes($expireMinutes)->format('YmdHis'),
            'vnp_BankCode' => $options['bank_code'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        $paymentUrl = $this->buildPaymentUrl($payload);
        $gatewayResponse = [
            'payUrl' => $paymentUrl,
            'txnRef' => $txnRef,
            'expireDate' => $payload['vnp_ExpireDate'],
        ];

        $payment->forceFill([
            'raw_request_json' => $payload,
            'raw_response_json' => $gatewayResponse,
            'redirect_url' => $paymentUrl,
            'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
        ])->save();

        return [$payment->fresh(), $gatewayResponse];
    }

    private function buildPaymentUrl(array $payload): string
    {
        $query = $this->buildHashData($payload);
        $secureHash = hash_hmac('sha512', $query, (string) config('services.vnpay.hash_secret'));

        return rtrim((string) config('services.vnpay.base_url'), '?') . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }

    private function buildHashData(array $payload): string
    {
        ksort($payload);

        $parts = [];
        foreach ($payload as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $parts[] = urlencode((string) $key) . '=' . urlencode((string) $value);
        }

        return implode('&', $parts);
    }

    private function buildQueryPayload(GiaoDichThanhToan $payment): array
    {
        return array_filter([
            'vnp_RequestId' => $this->newQueryRequestId((int) $payment->nguoi_dung_id),
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'querydr',
            'vnp_TmnCode' => (string) config('services.vnpay.tmn_code'),
            'vnp_TxnRef' => (string) $payment->ma_giao_dich_noi_bo,
            'vnp_OrderInfo' => (string) $payment->noi_dung,
            'vnp_TransactionNo' => $payment->ma_giao_dich_gateway ?: null,
            'vnp_TransactionDate' => $this->resolveTransactionDate($payment),
            'vnp_CreateDate' => CarbonImmutable::now('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_IpAddr' => '127.0.0.1',
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function signQueryPayload(array $payload): string
    {
        $hashData = implode('|', [
            (string) ($payload['vnp_RequestId'] ?? ''),
            (string) ($payload['vnp_Version'] ?? ''),
            (string) ($payload['vnp_Command'] ?? ''),
            (string) ($payload['vnp_TmnCode'] ?? ''),
            (string) ($payload['vnp_TxnRef'] ?? ''),
            (string) ($payload['vnp_TransactionDate'] ?? ''),
            (string) ($payload['vnp_CreateDate'] ?? ''),
            (string) ($payload['vnp_IpAddr'] ?? ''),
            (string) ($payload['vnp_OrderInfo'] ?? ''),
        ]);

        return hash_hmac('sha512', $hashData, (string) config('services.vnpay.hash_secret'));
    }

    private function verifyQueryResponseSignature(array $payload): bool
    {
        $signature = (string) ($payload['vnp_SecureHash'] ?? '');
        if ($signature === '') {
            return false;
        }

        $hashData = implode('|', [
            (string) ($payload['vnp_ResponseId'] ?? ''),
            (string) ($payload['vnp_Command'] ?? ''),
            (string) ($payload['vnp_ResponseCode'] ?? ''),
            (string) ($payload['vnp_Message'] ?? ''),
            (string) ($payload['vnp_TmnCode'] ?? ''),
            (string) ($payload['vnp_TxnRef'] ?? ''),
            (string) ($payload['vnp_Amount'] ?? ''),
            (string) ($payload['vnp_BankCode'] ?? ''),
            (string) ($payload['vnp_PayDate'] ?? ''),
            (string) ($payload['vnp_TransactionNo'] ?? ''),
            (string) ($payload['vnp_TransactionType'] ?? ''),
            (string) ($payload['vnp_TransactionStatus'] ?? ''),
            (string) ($payload['vnp_OrderInfo'] ?? ''),
            (string) ($payload['vnp_PromotionCode'] ?? ''),
            (string) ($payload['vnp_PromotionAmount'] ?? ''),
        ]);

        $expected = hash_hmac('sha512', $hashData, (string) config('services.vnpay.hash_secret'));

        return hash_equals(strtolower($expected), strtolower($signature));
    }

    private function onlyVnpayFields(array $payload): array
    {
        return array_filter(
            $payload,
            static fn ($value, $key) => str_starts_with((string) $key, 'vnp_') && $value !== null && $value !== '',
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function ensureConfigured(): void
    {
        foreach (['base_url', 'tmn_code', 'hash_secret'] as $key) {
            if (!config('services.vnpay.' . $key)) {
                throw BillingException::paymentGatewayUnavailable(GiaoDichThanhToan::GATEWAY_VNPAY);
            }
        }
    }

    private function newTxnRef(string $prefix, int $userId): string
    {
        return strtoupper($prefix) . $userId . Str::upper(Str::random(18));
    }

    private function newRequestId(int $userId): string
    {
        return 'VNPAYREQ' . $userId . Str::upper(Str::random(18));
    }

    private function newQueryRequestId(int $userId): string
    {
        return 'VNPAYQ' . $userId . Str::upper(Str::random(20));
    }

    private function resolveTransactionDate(GiaoDichThanhToan $payment): string
    {
        $rawRequest = is_array($payment->raw_request_json) ? $payment->raw_request_json : [];
        $transactionDate = $rawRequest['vnp_CreateDate'] ?? null;

        if (is_string($transactionDate) && preg_match('/^\d{14}$/', $transactionDate) === 1) {
            return $transactionDate;
        }

        return ($payment->created_at ?: now())
            ->copy()
            ->timezone('Asia/Ho_Chi_Minh')
            ->format('YmdHis');
    }

    private function resolveMerchantApiUrl(): string
    {
        $baseUrl = (string) config('services.vnpay.base_url');
        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;

        if (!$scheme || !$host) {
            throw new RuntimeException('Cấu hình VNPay không hợp lệ.');
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$scheme}://{$host}{$port}/merchant_webapi/api/transaction";
    }
}
