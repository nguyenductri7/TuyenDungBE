<?php

namespace App\Listeners;

use App\Events\BillingAiFeatureUsed;
use App\Events\BillingPaymentCompleted;
use App\Events\BillingSubscriptionActivated;
use App\Models\GiaoDichThanhToan;
use App\Models\NguoiDungGoiDichVu;
use App\Models\SuDungTinhNangAi;
use App\Services\AuditLogService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogBillingEventActivity implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function handle(BillingPaymentCompleted|BillingSubscriptionActivated|BillingAiFeatureUsed $event): void
    {
        Log::info('billing.event.processed', [
            'event' => class_basename($event),
            'payload' => get_object_vars($event),
        ]);

        try {
            $this->writeAuditLog($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function writeAuditLog(BillingPaymentCompleted|BillingSubscriptionActivated|BillingAiFeatureUsed $event): void
    {
        $auditLogService = app(AuditLogService::class);

        if ($event instanceof BillingPaymentCompleted) {
            $payment = GiaoDichThanhToan::query()->find($event->paymentId);
            if (!$payment) {
                return;
            }

            $auditLogService->logModelAction(
                actor: null,
                action: 'billing_payment_completed',
                description: "Thanh toán {$payment->ma_giao_dich_noi_bo} đã hoàn tất.",
                target: $payment,
                after: $payment->only(['trang_thai', 'so_tien', 'loai_giao_dich', 'paid_at']),
                metadata: [
                    'scope' => 'billing',
                    'gateway' => $event->gateway,
                    'transaction_type' => $event->transactionType,
                    'user_id' => $event->userId,
                    'plan_id' => $event->planId,
                ],
            );

            return;
        }

        if ($event instanceof BillingSubscriptionActivated) {
            $subscription = NguoiDungGoiDichVu::query()->with('goiDichVu')->find($event->subscriptionId);
            if (!$subscription) {
                return;
            }

            $auditLogService->logModelAction(
                actor: null,
                action: 'billing_subscription_activated',
                description: "Gói {$subscription->goiDichVu?->ma_goi} đã được kích hoạt.",
                target: $subscription,
                after: $subscription->toArray(),
                metadata: [
                    'scope' => 'billing',
                    'user_id' => $event->userId,
                    'plan_id' => $event->planId,
                    'payment_id' => $event->paymentId,
                ],
            );

            return;
        }

        $usage = SuDungTinhNangAi::query()->find($event->usageId);
        if (!$usage) {
            return;
        }

        $auditLogService->logModelAction(
            actor: null,
            action: 'billing_ai_feature_used',
            description: "Tính năng AI {$usage->feature_code} đã được ghi nhận billing.",
            target: $usage,
            after: $usage->only(['feature_code', 'billing_mode', 'trang_thai', 'so_tien_thuc_te']),
            metadata: [
                'scope' => 'billing',
                'user_id' => $event->userId,
                'feature_code' => $event->featureCode,
                'billing_mode' => $event->billingMode,
            ],
        );
    }
}
