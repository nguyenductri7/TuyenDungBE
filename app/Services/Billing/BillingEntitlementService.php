<?php

namespace App\Services\Billing;

use App\Models\BangGiaTinhNangAi;
use App\Models\NguoiDung;
use App\Models\SuDungTinhNangAi;
use Illuminate\Support\Collection;

class BillingEntitlementService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function forUser(NguoiDung $user): array
    {
        $subscription = $this->subscriptionService->getActiveSubscription($user);
        $prices = BangGiaTinhNangAi::query()
            ->where('trang_thai', BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG)
            ->get()
            ->keyBy('feature_code');

        $allowedFeatureCodes = $this->featureCodesForUser($user);
        $featureCodes = collect(array_keys((array) config('billing.free_quota', [])))
            ->merge((array) config('billing.subscription_features', []))
            ->merge($prices->keys())
            ->merge($subscription?->goiDichVu?->tinhNangs?->pluck('feature_code') ?? [])
            ->filter()
            ->filter(fn (string $featureCode) => $allowedFeatureCodes->isEmpty() || $allowedFeatureCodes->contains($featureCode))
            ->unique()
            ->values();

        $freeUsageCounts = $this->usageCountsByMode($user, SuDungTinhNangAi::BILLING_MODE_FREE);
        $subscriptionUsageCounts = $this->subscriptionUsageCounts($user, $subscription?->id, $subscription?->ngay_bat_dau, $subscription?->ngay_het_han);
        $subscriptionFeatures = $subscription?->goiDichVu?->tinhNangs?->keyBy('feature_code') ?? collect();

        $entitlements = $featureCodes->map(function (string $featureCode) use (
            $prices,
            $subscriptionFeatures,
            $freeUsageCounts,
            $subscriptionUsageCounts
        ): array {
            $price = $prices->get($featureCode);
            $subscriptionFeature = $subscriptionFeatures->get($featureCode);

            $freeTotal = (int) config('billing.free_quota.' . $featureCode, 0);
            $freeUsed = (int) ($freeUsageCounts[$featureCode] ?? 0);
            $freeRemaining = max($freeTotal - $freeUsed, 0);

            $subscriptionTotal = $subscriptionFeature?->is_unlimited
                ? null
                : (int) ($subscriptionFeature?->quota ?? 0);
            $subscriptionUsed = (int) ($subscriptionUsageCounts[$featureCode] ?? 0);
            $subscriptionRemaining = $subscriptionFeature?->is_unlimited
                ? null
                : max($subscriptionTotal - $subscriptionUsed, 0);

            return [
                'feature_code' => $featureCode,
                'feature_label' => $price?->ten_hien_thi ?: $this->featureLabel($featureCode),
                'wallet_price' => $price?->don_gia !== null ? (int) $price->don_gia : null,
                'wallet_unit' => $price?->don_vi_tinh ?: null,
                'free_quota_total' => $freeTotal,
                'free_quota_used' => $freeUsed,
                'free_quota_remaining' => $freeRemaining,
                'has_free_quota' => $freeTotal > 0,
                'subscription_quota_total' => $subscriptionTotal,
                'subscription_quota_used' => $subscriptionUsed,
                'subscription_quota_remaining' => $subscriptionRemaining,
                'subscription_is_unlimited' => (bool) ($subscriptionFeature?->is_unlimited ?? false),
                'subscription_reset_cycle' => $subscriptionFeature?->reset_cycle,
                'subscription_included' => $subscriptionFeature !== null,
            ];
        })->values()->all();

        return [
            'current_subscription' => $subscription,
            'entitlements' => $entitlements,
        ];
    }

    public function featureCodesForUser(NguoiDung $user): Collection
    {
        if ($user->isNhaTuyenDung()) {
            return collect((array) config('billing.employer_features', []))
                ->filter()
                ->values();
        }

        if ($user->isUngVien()) {
            return collect((array) config('billing.candidate_features', []))
                ->filter()
                ->values();
        }

        return collect();
    }

    private function usageCountsByMode(NguoiDung $user, string $billingMode): Collection
    {
        return SuDungTinhNangAi::query()
            ->selectRaw('feature_code, COUNT(*) as aggregate')
            ->where('nguoi_dung_id', $user->id)
            ->where('billing_mode', $billingMode)
            ->where('trang_thai', '!=', SuDungTinhNangAi::TRANG_THAI_THAT_BAI)
            ->groupBy('feature_code')
            ->pluck('aggregate', 'feature_code');
    }

    private function subscriptionUsageCounts(NguoiDung $user, ?int $subscriptionId, $startedAt, $expiredAt): Collection
    {
        if (!$subscriptionId) {
            return collect();
        }

        return SuDungTinhNangAi::query()
            ->selectRaw('feature_code, COUNT(*) as aggregate')
            ->where('nguoi_dung_id', $user->id)
            ->where('billing_mode', SuDungTinhNangAi::BILLING_MODE_SUBSCRIPTION)
            ->where('metadata_json->subscription_id', $subscriptionId)
            ->where('trang_thai', '!=', SuDungTinhNangAi::TRANG_THAI_THAT_BAI)
            ->when($startedAt, fn ($query) => $query->where('created_at', '>=', $startedAt))
            ->when($expiredAt, fn ($query) => $query->where('created_at', '<=', $expiredAt))
            ->groupBy('feature_code')
            ->pluck('aggregate', 'feature_code');
    }

    private function featureLabel(string $featureCode): string
    {
        $configuredLabel = config('billing.feature_labels.' . $featureCode);
        if (is_string($configuredLabel) && trim($configuredLabel) !== '') {
            return $configuredLabel;
        }

        return match ($featureCode) {
            'cover_letter_generation' => 'Sinh thư xin việc AI',
            'career_report_generation' => 'Sinh báo cáo định hướng nghề nghiệp',
            'chatbot_message' => 'Chatbot tư vấn nghề nghiệp',
            'mock_interview_session' => 'Mock Interview',
            default => $featureCode,
        };
    }
}
