<?php

namespace App\Services\Billing;

use App\Events\BillingAiFeatureUsed;
use App\Exceptions\BillingException;
use App\Models\NguoiDung;
use App\Models\SuDungTinhNangAi;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class FeatureAccessService
{
    public function __construct(
        private readonly AiFeatureBillingService $aiFeatureBillingService,
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function beginUsage(
        NguoiDung $user,
        string $featureCode,
        string $referenceType,
        ?int $referenceId = null,
        array $metadata = [],
        ?string $idempotencyKey = null,
    ): SuDungTinhNangAi {
        $key = trim((string) ($idempotencyKey ?: Str::uuid()));

        $subscriptionUsage = $this->subscriptionService->beginSubscriptionUsage(
            $user,
            $featureCode,
            $referenceType,
            $referenceId,
            $metadata,
            $key,
        );

        if ($subscriptionUsage) {
            return $subscriptionUsage;
        }

        if ($this->hasRemainingFreeQuota($user, $featureCode)) {
            return $this->beginFreeUsage(
                $user,
                $featureCode,
                $referenceType,
                $referenceId,
                $metadata,
                $key,
            );
        }

        return $this->aiFeatureBillingService->beginWalletUsage(
            $user,
            $featureCode,
            $referenceType,
            $referenceId,
            $metadata,
            $key,
        );
    }

    public function commitUsage(SuDungTinhNangAi $usage, array $metadata = []): SuDungTinhNangAi
    {
        $committed = match ($usage->billing_mode) {
            SuDungTinhNangAi::BILLING_MODE_WALLET => $this->aiFeatureBillingService->commitUsage($usage, $metadata),
            SuDungTinhNangAi::BILLING_MODE_SUBSCRIPTION => $this->subscriptionService->commitUsage($usage, $metadata),
            default => $this->commitFreeUsage($usage, $metadata),
        };

        event(BillingAiFeatureUsed::fromUsage($committed));

        return $committed;
    }

    public function failUsage(SuDungTinhNangAi $usage, ?string $reason = null, array $metadata = []): SuDungTinhNangAi
    {
        return match ($usage->billing_mode) {
            SuDungTinhNangAi::BILLING_MODE_WALLET => $this->aiFeatureBillingService->failUsage($usage, $reason, $metadata),
            SuDungTinhNangAi::BILLING_MODE_SUBSCRIPTION => $this->subscriptionService->failUsage($usage, $reason, $metadata),
            default => $this->failFreeUsage($usage, $reason, $metadata),
        };
    }

    private function beginFreeUsage(
        NguoiDung $user,
        string $featureCode,
        string $referenceType,
        ?int $referenceId,
        array $metadata,
        string $idempotencyKey,
    ): SuDungTinhNangAi {
        try {
            return SuDungTinhNangAi::create([
                'nguoi_dung_id' => $user->id,
                'feature_code' => $featureCode,
                'so_luong' => 1,
                'don_gia_ap_dung' => 0,
                'so_tien_du_kien' => 0,
                'so_tien_thuc_te' => 0,
                'billing_mode' => SuDungTinhNangAi::BILLING_MODE_FREE,
                'trang_thai' => SuDungTinhNangAi::TRANG_THAI_PENDING,
                'idempotency_key' => $idempotencyKey,
                'tham_chieu_loai' => $referenceType,
                'tham_chieu_id' => $referenceId,
                'metadata_json' => [
                    ...$metadata,
                    'free_quota' => true,
                    'free_quota_limit' => $this->freeQuotaFor($featureCode),
                ],
            ]);
        } catch (QueryException) {
            throw BillingException::duplicateRequest($idempotencyKey);
        }
    }

    private function commitFreeUsage(SuDungTinhNangAi $usage, array $metadata = []): SuDungTinhNangAi
    {
        if ($usage->trang_thai === SuDungTinhNangAi::TRANG_THAI_THANH_CONG) {
            return $usage;
        }

        $usage->forceFill([
            'trang_thai' => SuDungTinhNangAi::TRANG_THAI_THANH_CONG,
            'so_tien_thuc_te' => 0,
            'metadata_json' => [
                ...($usage->metadata_json ?? []),
                ...$metadata,
            ],
        ])->save();

        return $usage->fresh();
    }

    private function failFreeUsage(SuDungTinhNangAi $usage, ?string $reason = null, array $metadata = []): SuDungTinhNangAi
    {
        if ($usage->trang_thai === SuDungTinhNangAi::TRANG_THAI_THAT_BAI) {
            return $usage;
        }

        $usage->forceFill([
            'trang_thai' => SuDungTinhNangAi::TRANG_THAI_THAT_BAI,
            'so_tien_thuc_te' => 0,
            'metadata_json' => array_filter([
                ...($usage->metadata_json ?? []),
                ...$metadata,
                'failure_reason' => $reason,
            ], static fn ($value) => $value !== null && $value !== ''),
        ])->save();

        return $usage->fresh();
    }

    private function hasRemainingFreeQuota(NguoiDung $user, string $featureCode): bool
    {
        $quota = $this->freeQuotaFor($featureCode);
        if ($quota <= 0) {
            return false;
        }

        $used = SuDungTinhNangAi::query()
            ->where('nguoi_dung_id', $user->id)
            ->where('feature_code', $featureCode)
            ->where('billing_mode', SuDungTinhNangAi::BILLING_MODE_FREE)
            ->where('trang_thai', '!=', SuDungTinhNangAi::TRANG_THAI_THAT_BAI)
            ->count();

        return $used < $quota;
    }

    private function freeQuotaFor(string $featureCode): int
    {
        return (int) config('billing.free_quota.' . $featureCode, 0);
    }
}
