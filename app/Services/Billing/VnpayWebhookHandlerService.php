<?php

namespace App\Services\Billing;

use App\Events\BillingPaymentCompleted;
use App\Models\GiaoDichThanhToan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VnpayWebhookHandlerService
{
    public function __construct(
        private readonly VnpayGatewayService $vnpayGatewayService,
        private readonly WalletService $walletService,
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function recordReturnPayload(string $txnRef, array $payload): ?GiaoDichThanhToan
    {
        $payment = GiaoDichThanhToan::query()
            ->where('gateway', GiaoDichThanhToan::GATEWAY_VNPAY)
            ->where('ma_giao_dich_noi_bo', $txnRef)
            ->first();

        if (!$payment) {
            return null;
        }

        $payment->forceFill([
            'return_payload_json' => $payload,
        ])->save();

        return $payment->fresh(['goiDichVu']);
    }

    public function autoCompleteFromReturnForLocal(string $txnRef, array $payload): ?GiaoDichThanhToan
    {
        if (!config('services.vnpay.auto_complete_return_local', false)) {
            return null;
        }

        if (!$this->vnpayGatewayService->verifyNotificationSignature($payload)) {
            return null;
        }

        if (!$this->isSuccessPayload($payload)) {
            return null;
        }

        return DB::transaction(function () use ($txnRef, $payload): ?GiaoDichThanhToan {
            $payment = $this->lockPayment($txnRef);
            if (!$payment) {
                return null;
            }

            $amount = $this->resolveAmount($payload);
            if ($amount === null || $amount !== (int) $payment->so_tien) {
                $payment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();

                return $payment->fresh(['goiDichVu']);
            }

            return $this->applyGatewayResult($payment, $payload, 'return_payload_json');
        });
    }

    public function handleIpn(array $payload): array
    {
        if (!$this->vnpayGatewayService->verifyNotificationSignature($payload)) {
            return [
                'RspCode' => '97',
                'Message' => 'Invalid signature',
            ];
        }

        return DB::transaction(function () use ($payload): array {
            $payment = $this->lockPayment((string) ($payload['vnp_TxnRef'] ?? ''));
            if (!$payment) {
                return [
                    'RspCode' => '01',
                    'Message' => 'Order not found',
                ];
            }

            $amount = $this->resolveAmount($payload);
            if ($amount === null || $amount !== (int) $payment->so_tien) {
                $payment->forceFill([
                    'ma_giao_dich_gateway' => isset($payload['vnp_TransactionNo']) ? (string) $payload['vnp_TransactionNo'] : $payment->ma_giao_dich_gateway,
                    'ipn_payload_json' => $payload,
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();

                return [
                    'RspCode' => '04',
                    'Message' => 'Invalid amount',
                ];
            }

            if ($payment->trang_thai !== GiaoDichThanhToan::TRANG_THAI_PENDING) {
                $payment->forceFill([
                    'ipn_payload_json' => $payload,
                ])->save();

                return [
                    'RspCode' => '02',
                    'Message' => 'Order already confirmed',
                ];
            }

            $this->applyGatewayResult($payment, $payload, 'ipn_payload_json');

            return [
                'RspCode' => '00',
                'Message' => 'Confirm success',
            ];
        });
    }

    public function reconcilePayment(GiaoDichThanhToan $payment): GiaoDichThanhToan
    {
        $rawResponse = is_array($payment->raw_response_json) ? $payment->raw_response_json : [];
        $latestQuery = is_array($rawResponse['latest_query'] ?? null) ? $rawResponse['latest_query'] : null;
        $latestQueryAt = isset($rawResponse['latest_query_at']) ? Carbon::parse((string) $rawResponse['latest_query_at']) : null;
        $reuseLatestQuery = $latestQuery !== null
            && $latestQueryAt !== null
            && $latestQueryAt->gte(now()->subMinutes(5));

        $gatewayResponse = $reuseLatestQuery
            ? $latestQuery
            : $this->vnpayGatewayService->queryPayment($payment);

        return DB::transaction(function () use ($payment, $gatewayResponse): GiaoDichThanhToan {
            $locked = $this->lockPayment($payment->ma_giao_dich_noi_bo);
            if (!$locked) {
                throw new \RuntimeException('Không tìm thấy giao dịch VNPay để đối soát.');
            }

            $rawResponse = is_array($locked->raw_response_json) ? $locked->raw_response_json : [];
            $locked->forceFill([
                'raw_response_json' => [
                    ...$rawResponse,
                    'latest_query' => $gatewayResponse,
                    'latest_query_source' => 'gateway_query',
                    'latest_query_at' => now()->toISOString(),
                ],
                'ma_giao_dich_gateway' => isset($gatewayResponse['vnp_TransactionNo'])
                    ? (string) $gatewayResponse['vnp_TransactionNo']
                    : $locked->ma_giao_dich_gateway,
            ])->save();

            $amount = $this->resolveAmount($gatewayResponse);
            if ($amount === null || $amount !== (int) $locked->so_tien) {
                if ($amount !== null) {
                    $locked->forceFill([
                        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                    ])->save();
                }

                return $locked->fresh(['goiDichVu']);
            }

            if ($locked->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG) {
                return $locked->fresh(['goiDichVu']);
            }

            if ((string) ($gatewayResponse['vnp_ResponseCode'] ?? '') !== '00') {
                return $locked->fresh(['goiDichVu']);
            }

            if ($this->isSuccessPayload($gatewayResponse)) {
                return $this->applyGatewayResult($locked, $gatewayResponse, 'ipn_payload_json');
            }

            if (!$this->isPendingTransactionStatus((string) ($gatewayResponse['vnp_TransactionStatus'] ?? ''))) {
                return $this->applyGatewayResult($locked, $gatewayResponse, 'ipn_payload_json');
            }

            return $locked->fresh(['goiDichVu']);
        });
    }

    private function lockPayment(string $txnRef): ?GiaoDichThanhToan
    {
        return GiaoDichThanhToan::query()
            ->with('goiDichVu')
            ->where('gateway', GiaoDichThanhToan::GATEWAY_VNPAY)
            ->where('ma_giao_dich_noi_bo', $txnRef)
            ->lockForUpdate()
            ->first();
    }

    private function applyGatewayResult(GiaoDichThanhToan $payment, array $payload, string $payloadColumn): GiaoDichThanhToan
    {
        $success = $this->isSuccessPayload($payload);
        $wasAlreadySuccessful = $payment->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG;

        $payment->forceFill([
            'ma_giao_dich_gateway' => isset($payload['vnp_TransactionNo']) ? (string) $payload['vnp_TransactionNo'] : $payment->ma_giao_dich_gateway,
            $payloadColumn => $payload,
            'trang_thai' => $success
                ? GiaoDichThanhToan::TRANG_THAI_THANH_CONG
                : GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
            'paid_at' => $success ? ($payment->paid_at ?: now()) : $payment->paid_at,
        ])->save();

        if ($success && !$wasAlreadySuccessful) {
            $this->finalizeSuccessfulPayment($payment);
        }

        return $payment->fresh(['goiDichVu']);
    }

    private function isSuccessPayload(array $payload): bool
    {
        return (string) ($payload['vnp_ResponseCode'] ?? '') === '00'
            && (string) ($payload['vnp_TransactionStatus'] ?? '') === '00';
    }

    private function isPendingTransactionStatus(string $status): bool
    {
        return in_array($status, ['01'], true);
    }

    private function finalizeSuccessfulPayment(GiaoDichThanhToan $payment): void
    {
        if ($payment->loai_giao_dich === GiaoDichThanhToan::LOAI_NAP_VI) {
            $this->walletService->creditFromPayment($payment);
        } elseif ($payment->loai_giao_dich === GiaoDichThanhToan::LOAI_MUA_GOI) {
            $this->subscriptionService->activateFromPayment($payment);
        }

        event(BillingPaymentCompleted::fromPayment($payment->fresh()));
    }

    private function resolveAmount(array $payload): ?int
    {
        if (!isset($payload['vnp_Amount']) || !is_numeric($payload['vnp_Amount'])) {
            return null;
        }

        return (int) ((int) $payload['vnp_Amount'] / 100);
    }
}
