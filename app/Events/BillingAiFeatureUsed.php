<?php

namespace App\Events;

use App\Models\SuDungTinhNangAi;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillingAiFeatureUsed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $usageId,
        public readonly int $userId,
        public readonly string $featureCode,
        public readonly string $billingMode,
        public readonly string $status,
        public readonly int $amount,
    ) {
    }

    public static function fromUsage(SuDungTinhNangAi $usage): self
    {
        return new self(
            usageId: (int) $usage->id,
            userId: (int) $usage->nguoi_dung_id,
            featureCode: (string) $usage->feature_code,
            billingMode: (string) $usage->billing_mode,
            status: (string) $usage->trang_thai,
            amount: (int) $usage->so_tien_thuc_te,
        );
    }
}
