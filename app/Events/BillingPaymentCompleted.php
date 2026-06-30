<?php

namespace App\Events;

use App\Models\GiaoDichThanhToan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillingPaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $paymentId,
        public readonly int $userId,
        public readonly string $gateway,
        public readonly string $transactionType,
        public readonly int $amount,
        public readonly ?int $planId = null,
    ) {
    }

    public static function fromPayment(GiaoDichThanhToan $payment): self
    {
        return new self(
            paymentId: (int) $payment->id,
            userId: (int) $payment->nguoi_dung_id,
            gateway: (string) $payment->gateway,
            transactionType: (string) $payment->loai_giao_dich,
            amount: (int) $payment->so_tien,
            planId: $payment->goi_dich_vu_id ? (int) $payment->goi_dich_vu_id : null,
        );
    }
}
