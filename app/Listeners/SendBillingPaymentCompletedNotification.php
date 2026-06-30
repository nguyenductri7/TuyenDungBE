<?php

namespace App\Listeners;

use App\Events\BillingPaymentCompleted;
use App\Models\AppNotification;
use App\Models\GiaoDichThanhToan;
use App\Services\AppNotificationService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

class SendBillingPaymentCompletedNotification implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function __construct(
        private readonly AppNotificationService $notificationService,
    ) {
    }

    public function handle(BillingPaymentCompleted $event): void
    {
        if ($event->transactionType !== GiaoDichThanhToan::LOAI_NAP_VI) {
            return;
        }

        $payment = GiaoDichThanhToan::query()->find($event->paymentId);
        if (!$payment) {
            return;
        }

        $path = '/payments/' . $payment->ma_giao_dich_noi_bo;
        $alreadyNotified = AppNotification::query()
            ->where('nguoi_dung_id', $event->userId)
            ->where('loai', 'billing_payment_completed')
            ->where('duong_dan', $path)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $this->notificationService->createForUser(
            $event->userId,
            'billing_payment_completed',
            'Nạp ví AI thành công',
            'Ví AI của bạn đã được cộng ' . number_format($event->amount, 0, ',', '.') . ' đ từ giao dịch ' . $payment->ma_giao_dich_noi_bo . '.',
            $path,
            [
                'payment_id' => $event->paymentId,
                'payment_code' => $payment->ma_giao_dich_noi_bo,
                'transaction_type' => $event->transactionType,
                'amount' => $event->amount,
            ],
        );
    }
}
