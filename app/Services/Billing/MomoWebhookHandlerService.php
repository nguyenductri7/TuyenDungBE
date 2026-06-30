<?php

namespace App\Services\Billing;

use App\Events\BillingPaymentCompleted;
use App\Models\GiaoDichThanhToan;
use Illuminate\Support\Facades\DB;

class MomoWebhookHandlerService
{
    public function __construct(
        private readonly MomoGatewayService $momoGatewayService,
        private readonly WalletService $walletService,
        private readonly SubscriptionService $subscriptionService,
    ) {
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

        return $payment->fresh(['goiDichVu']);
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
                ->with('goiDichVu')
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

                return $payment->fresh(['goiDichVu']);
            }

            if ($payment->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG) {
                return $payment;
            }

            $payment->forceFill([
                'ma_giao_dich_gateway' => isset($payload['transId']) ? (string) $payload['transId'] : $payment->ma_giao_dich_gateway,
                'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                'paid_at' => now(),
            ])->save();

            $this->finalizeSuccessfulPayment($payment);

            return $payment->fresh(['goiDichVu']);
        });
    }

    public function handleIpn(array $payload): ?GiaoDichThanhToan
    {
        if (!$this->momoGatewayService->verifyNotificationSignature($payload)) {
            return null;
        }

        return DB::transaction(function () use ($payload): ?GiaoDichThanhToan {
            $payment = GiaoDichThanhToan::query()
                ->with('goiDichVu')
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

            if ((int) ($payload['amount'] ?? 0) !== (int) $payment->so_tien) {
                $payment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();

                return $payment->fresh(['goiDichVu']);
            }

            if ($payment->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG) {
                return $payment;
            }

            if ((int) ($payload['resultCode'] ?? -1) === 0) {
                $payment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                    'paid_at' => now(),
                ])->save();

                $this->finalizeSuccessfulPayment($payment);
            } else {
                $payment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();
            }

            return $payment->fresh(['goiDichVu']);
        });
    }

    public function reconcilePayment(GiaoDichThanhToan $payment): GiaoDichThanhToan
    {
        $gatewayResponse = $this->momoGatewayService->queryPayment($payment);

        return DB::transaction(function () use ($payment, $gatewayResponse): GiaoDichThanhToan {
            $locked = GiaoDichThanhToan::query()
                ->with('goiDichVu')
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $rawResponse = is_array($locked->raw_response_json) ? $locked->raw_response_json : [];
            $locked->forceFill([
                'raw_response_json' => [
                    ...$rawResponse,
                    'latest_query' => $gatewayResponse,
                    'latest_query_at' => now()->toISOString(),
                ],
                'ma_giao_dich_gateway' => isset($gatewayResponse['transId'])
                    ? (string) $gatewayResponse['transId']
                    : $locked->ma_giao_dich_gateway,
            ])->save();

            $payloadAmount = isset($gatewayResponse['amount']) ? (int) $gatewayResponse['amount'] : null;
            if ($payloadAmount !== null && $payloadAmount !== (int) $locked->so_tien) {
                $locked->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();

                return $locked->fresh(['goiDichVu']);
            }

            if ($locked->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG) {
                return $locked->fresh(['goiDichVu']);
            }

            $resultCode = (int) ($gatewayResponse['resultCode'] ?? $gatewayResponse['errorCode'] ?? -1);
            $pendingCodes = [1000, 7000, 7002];

            if ($resultCode === 0) {
                $locked->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                    'paid_at' => $locked->paid_at ?: now(),
                ])->save();

                $this->finalizeSuccessfulPayment($locked);
            } elseif ($resultCode >= 0 && !in_array($resultCode, $pendingCodes, true)) {
                $locked->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
                ])->save();
            }

            return $locked->fresh(['goiDichVu']);
        });
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
}
