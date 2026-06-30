<?php

namespace App\Events;

use App\Models\NguoiDungGoiDichVu;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillingSubscriptionActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $userId,
        public readonly int $planId,
        public readonly ?int $paymentId,
        public readonly ?string $planCode = null,
    ) {
    }

    public static function fromSubscription(NguoiDungGoiDichVu $subscription): self
    {
        return new self(
            subscriptionId: (int) $subscription->id,
            userId: (int) $subscription->nguoi_dung_id,
            planId: (int) $subscription->goi_dich_vu_id,
            paymentId: $subscription->giao_dich_thanh_toan_id ? (int) $subscription->giao_dich_thanh_toan_id : null,
            planCode: $subscription->goiDichVu?->ma_goi,
        );
    }
}
