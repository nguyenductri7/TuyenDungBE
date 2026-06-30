<?php

namespace App\Services\Billing;

use App\Exceptions\BillingException;
use App\Models\BangGiaTinhNangAi;
use App\Models\NguoiDung;
use App\Models\SuDungTinhNangAi;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class AiFeatureBillingService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function beginWalletUsage(
        NguoiDung $user,
        string $featureCode,
        string $referenceType,
        ?int $referenceId = null,
        array $metadata = [],
        ?string $idempotencyKey = null,
    ): SuDungTinhNangAi {
        $price = BangGiaTinhNangAi::query()
            ->where('feature_code', $featureCode)
            ->where('trang_thai', BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG)
            ->first();

        if (!$price) {
            throw BillingException::featureNotPriced($featureCode);
        }

        $key = trim((string) ($idempotencyKey ?: Str::uuid()));

        try {
            $usage = SuDungTinhNangAi::create([
                'nguoi_dung_id' => $user->id,
                'feature_code' => $featureCode,
                'so_luong' => 1,
                'don_gia_ap_dung' => (int) $price->don_gia,
                'so_tien_du_kien' => (int) $price->don_gia,
                'so_tien_thuc_te' => 0,
                'billing_mode' => SuDungTinhNangAi::BILLING_MODE_WALLET,
                'trang_thai' => SuDungTinhNangAi::TRANG_THAI_PENDING,
                'idempotency_key' => $key,
                'tham_chieu_loai' => $referenceType,
                'tham_chieu_id' => $referenceId,
                'metadata_json' => $metadata,
            ]);
        } catch (QueryException $exception) {
            throw BillingException::duplicateRequest($key);
        }

        $this->walletService->reserveForUsage($usage);

        return $usage->fresh(['reserveTransaction']);
    }

    public function commitUsage(SuDungTinhNangAi $usage, array $metadata = []): SuDungTinhNangAi
    {
        $updated = $usage;
        if ($metadata !== []) {
            $updated->forceFill([
                'metadata_json' => [
                    ...($updated->metadata_json ?? []),
                    ...$metadata,
                ],
            ])->save();
        }

        $this->walletService->commitUsage($updated);

        return $updated->fresh(['reserveTransaction', 'settlementTransaction']);
    }

    public function failUsage(SuDungTinhNangAi $usage, ?string $reason = null, array $metadata = []): SuDungTinhNangAi
    {
        $updated = $usage;
        if ($metadata !== []) {
            $updated->forceFill([
                'metadata_json' => [
                    ...($updated->metadata_json ?? []),
                    ...$metadata,
                ],
            ])->save();
        }

        $this->walletService->releaseUsage($updated, $reason);

        return $updated->fresh(['reserveTransaction', 'settlementTransaction']);
    }
}
