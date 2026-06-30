<?php

namespace App\Listeners;

use App\Events\BillingSubscriptionActivated;
use App\Models\NguoiDungGoiDichVu;
use App\Services\AppNotificationService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

class SendBillingSubscriptionActivatedNotification implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function __construct(
        private readonly AppNotificationService $notificationService,
    ) {
    }

    public function handle(BillingSubscriptionActivated $event): void
    {
        $subscription = NguoiDungGoiDichVu::query()
            ->with([
                'goiDichVu:id,ma_goi,ten_goi',
                'giaoDichThanhToan:id,ma_giao_dich_noi_bo',
            ])
            ->find($event->subscriptionId);

        if (!$subscription) {
            return;
        }

        $planName = $subscription->goiDichVu?->ten_goi ?: 'Gói Pro';
        $expiredAt = optional($subscription->ngay_het_han)?->format('H:i d/m/Y');

        $message = $planName . ' đã được kích hoạt thành công cho tài khoản của bạn.';
        if ($expiredAt) {
            $message .= ' Hiệu lực đến ' . $expiredAt . '.';
        }

        $targetPath = $subscription->giaoDichThanhToan?->ma_giao_dich_noi_bo
            ? '/payments/' . $subscription->giaoDichThanhToan->ma_giao_dich_noi_bo
            : '/plans';

        $this->notificationService->createForUser(
            $event->userId,
            'billing_subscription_activated',
            'Kích hoạt gói Pro thành công',
            $message,
            $targetPath,
            [
                'subscription_id' => $event->subscriptionId,
                'payment_id' => $event->paymentId,
                'plan_id' => $event->planId,
                'plan_code' => $event->planCode,
            ],
        );
    }
}
