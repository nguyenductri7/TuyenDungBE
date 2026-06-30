<?php

namespace App\Services\Billing;

use App\Events\BillingPaymentCompleted;
use App\Events\BillingSubscriptionActivated;
use App\Exceptions\BillingException;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\GoiDichVuTinhNang;
use App\Models\NguoiDung;
use App\Models\NguoiDungGoiDichVu;
use App\Models\SuDungTinhNangAi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function getActiveSubscription(NguoiDung $user): ?NguoiDungGoiDichVu
    {
        return NguoiDungGoiDichVu::query()
            ->with(['goiDichVu.tinhNangs'])
            ->where('nguoi_dung_id', $user->id)
            ->where('trang_thai', NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG)
            ->where(function ($query) {
                $query->whereNull('ngay_bat_dau')
                    ->orWhere('ngay_bat_dau', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ngay_het_han')
                    ->orWhere('ngay_het_han', '>=', now());
            })
            ->latest('id')
            ->first();
    }

    public function getActivePlans(): Collection
    {
        return GoiDichVu::query()
            ->with('tinhNangs')
            ->where('trang_thai', GoiDichVu::TRANG_THAI_HOAT_DONG)
            ->orderByRaw("CASE WHEN is_free = 1 THEN 0 ELSE 1 END")
            ->orderBy('gia')
            ->get();
    }

    public function activateFromPayment(GiaoDichThanhToan $payment): ?NguoiDungGoiDichVu
    {
        if ($payment->loai_giao_dich !== GiaoDichThanhToan::LOAI_MUA_GOI || !$payment->goi_dich_vu_id) {
            return null;
        }

        $created = false;

        $subscription = DB::transaction(function () use ($payment, &$created): NguoiDungGoiDichVu {
            $payment = GiaoDichThanhToan::query()
                ->with('goiDichVu')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $plan = $payment->goiDichVu;
            if (!$plan) {
                throw BillingException::paymentGatewayUnavailable($payment->gateway);
            }

            $existing = NguoiDungGoiDichVu::query()
                ->where('giao_dich_thanh_toan_id', $payment->id)
                ->first();

            if ($existing) {
                return $existing->fresh(['goiDichVu.tinhNangs']);
            }

            NguoiDungGoiDichVu::query()
                ->where('nguoi_dung_id', $payment->nguoi_dung_id)
                ->where('trang_thai', NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG)
                ->update([
                    'trang_thai' => NguoiDungGoiDichVu::TRANG_THAI_HET_HAN,
                ]);

            $startedAt = $payment->paid_at ?: now();
            $expiredAt = $this->resolveExpiredAt($plan, $startedAt);

            $created = true;

            return NguoiDungGoiDichVu::query()->create([
                'nguoi_dung_id' => $payment->nguoi_dung_id,
                'goi_dich_vu_id' => $plan->id,
                'giao_dich_thanh_toan_id' => $payment->id,
                'ngay_bat_dau' => $startedAt,
                'ngay_het_han' => $expiredAt,
                'trang_thai' => NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG,
                'auto_renew' => false,
            ])->fresh(['goiDichVu.tinhNangs']);
        });

        if ($created) {
            event(BillingSubscriptionActivated::fromSubscription($subscription));
        }

        return $subscription;
    }

    public function purchaseWithWallet(NguoiDung $user, GoiDichVu $plan): array
    {
        if ($plan->trang_thai !== GoiDichVu::TRANG_THAI_HOAT_DONG || $plan->is_free) {
            throw BillingException::invalidSubscriptionPlan($plan->ma_goi);
        }

        [$payment, $subscription, $walletTransaction] = DB::transaction(function () use ($user, $plan): array {
            $wallet = $this->walletService->getOrCreateWallet($user);

            $payment = GiaoDichThanhToan::query()->create([
                'nguoi_dung_id' => $user->id,
                'vi_nguoi_dung_id' => $wallet->id,
                'goi_dich_vu_id' => $plan->id,
                'gateway' => GiaoDichThanhToan::GATEWAY_WALLET,
                'ma_giao_dich_noi_bo' => $this->newWalletPurchaseTxnRef($user->id),
                'ma_yeu_cau' => $this->newWalletPurchaseRequestId($user->id),
                'loai_giao_dich' => GiaoDichThanhToan::LOAI_MUA_GOI,
                'so_tien' => (int) $plan->gia,
                'noi_dung' => 'Mua goi ' . $plan->ten_goi . ' bang vi AI',
                'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                'raw_request_json' => [
                    'payment_method' => 'wallet_balance',
                    'plan_code' => $plan->ma_goi,
                ],
                'raw_response_json' => [
                    'status' => 'captured',
                    'source' => 'wallet_balance',
                ],
                'paid_at' => now(),
            ]);

            $walletTransaction = $this->walletService->debitForSubscriptionPurchase($payment);
            $subscription = $this->activateFromPayment($payment);

            return [
                $payment->fresh(['goiDichVu', 'viNguoiDung']),
                $subscription,
                $walletTransaction,
            ];
        });

        event(BillingPaymentCompleted::fromPayment($payment));

        return [$payment, $subscription, $walletTransaction];
    }

    public function beginSubscriptionUsage(
        NguoiDung $user,
        string $featureCode,
        string $referenceType,
        ?int $referenceId = null,
        array $metadata = [],
        ?string $idempotencyKey = null,
    ): ?SuDungTinhNangAi {
        $subscription = $this->getActiveSubscription($user);
        if (!$subscription) {
            return null;
        }

        /** @var GoiDichVuTinhNang|null $feature */
        $feature = $subscription->goiDichVu?->tinhNangs
            ?->firstWhere('feature_code', $featureCode);

        if (!$feature) {
            return null;
        }

        if (!$feature->is_unlimited && !$this->hasRemainingQuota($subscription, $feature)) {
            return null;
        }

        try {
            return SuDungTinhNangAi::create([
                'nguoi_dung_id' => $user->id,
                'feature_code' => $featureCode,
                'so_luong' => 1,
                'don_gia_ap_dung' => 0,
                'so_tien_du_kien' => 0,
                'so_tien_thuc_te' => 0,
                'billing_mode' => SuDungTinhNangAi::BILLING_MODE_SUBSCRIPTION,
                'trang_thai' => SuDungTinhNangAi::TRANG_THAI_PENDING,
                'idempotency_key' => $idempotencyKey,
                'tham_chieu_loai' => $referenceType,
                'tham_chieu_id' => $referenceId,
                'metadata_json' => [
                    ...$metadata,
                    'subscription_id' => $subscription->id,
                    'plan_code' => $subscription->goiDichVu?->ma_goi,
                    'included_by_subscription' => true,
                ],
            ]);
        } catch (QueryException) {
            throw BillingException::duplicateRequest((string) $idempotencyKey);
        }
    }

    public function commitUsage(SuDungTinhNangAi $usage, array $metadata = []): SuDungTinhNangAi
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

    public function failUsage(SuDungTinhNangAi $usage, ?string $reason = null, array $metadata = []): SuDungTinhNangAi
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

    private function hasRemainingQuota(NguoiDungGoiDichVu $subscription, GoiDichVuTinhNang $feature): bool
    {
        $used = SuDungTinhNangAi::query()
            ->where('nguoi_dung_id', $subscription->nguoi_dung_id)
            ->where('feature_code', $feature->feature_code)
            ->where('billing_mode', SuDungTinhNangAi::BILLING_MODE_SUBSCRIPTION)
            ->where('metadata_json->subscription_id', $subscription->id)
            ->where('trang_thai', '!=', SuDungTinhNangAi::TRANG_THAI_THAT_BAI)
            ->where(function ($query) use ($subscription) {
                $query->when(
                    $subscription->ngay_bat_dau !== null,
                    fn ($inner) => $inner->where('created_at', '>=', $subscription->ngay_bat_dau)
                );
                $query->when(
                    $subscription->ngay_het_han !== null,
                    fn ($inner) => $inner->where('created_at', '<=', $subscription->ngay_het_han)
                );
            })
            ->count();

        return $used < (int) ($feature->quota ?? 0);
    }

    private function resolveExpiredAt(GoiDichVu $plan, $startedAt)
    {
        return match ($plan->chu_ky) {
            GoiDichVu::CHU_KY_THANG => $startedAt->copy()->addMonth(),
            GoiDichVu::CHU_KY_NAM => $startedAt->copy()->addYear(),
            default => null,
        };
    }

    private function newWalletPurchaseTxnRef(int $userId): string
    {
        return 'WALLETSUB' . $userId . Str::upper(Str::random(18));
    }

    private function newWalletPurchaseRequestId(int $userId): string
    {
        return 'WALLETREQ' . $userId . Str::upper(Str::random(18));
    }
}
