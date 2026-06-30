<?php

namespace App\Services\Billing;

use App\Exceptions\BillingException;
use App\Models\BienDongVi;
use App\Models\GiaoDichThanhToan;
use App\Models\NguoiDung;
use App\Models\SuDungTinhNangAi;
use App\Models\ViNguoiDung;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getOrCreateWallet(NguoiDung $nguoiDung): ViNguoiDung
    {
        return ViNguoiDung::firstOrCreate(
            ['nguoi_dung_id' => $nguoiDung->id],
            [
                'so_du_hien_tai' => 0,
                'so_du_tam_giu' => 0,
                'don_vi_tien_te' => 'VND',
                'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
            ]
        );
    }

    public function reserveForUsage(SuDungTinhNangAi $usage): BienDongVi
    {
        return DB::transaction(function () use ($usage): BienDongVi {
            $usage = SuDungTinhNangAi::query()->lockForUpdate()->findOrFail($usage->id);

            if ($usage->bien_dong_vi_reserve_id) {
                return BienDongVi::query()->findOrFail($usage->bien_dong_vi_reserve_id);
            }

            $wallet = ViNguoiDung::query()
                ->where('nguoi_dung_id', $usage->nguoi_dung_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = $this->getOrCreateWallet($usage->nguoiDung()->firstOrFail());
                $wallet = ViNguoiDung::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $this->ensureWalletAvailable($wallet);

            $amount = (int) $usage->so_tien_du_kien;
            if ($wallet->so_du_kha_dung < $amount) {
                throw BillingException::insufficientBalance($wallet->so_du_kha_dung, $amount);
            }

            $balanceBefore = (int) $wallet->so_du_hien_tai;
            $holdBefore = (int) $wallet->so_du_tam_giu;

            $wallet->forceFill([
                'so_du_tam_giu' => $holdBefore + $amount,
            ])->save();

            $transaction = BienDongVi::create([
                'vi_nguoi_dung_id' => $wallet->id,
                'nguoi_dung_id' => $usage->nguoi_dung_id,
                'loai_bien_dong' => BienDongVi::LOAI_USAGE_RESERVE,
                'so_tien' => $amount,
                'so_du_truoc' => $balanceBefore,
                'so_du_sau' => $balanceBefore,
                'tam_giu_truoc' => $holdBefore,
                'tam_giu_sau' => $holdBefore + $amount,
                'trang_thai' => BienDongVi::TRANG_THAI_HOAN_TAT,
                'tham_chieu_loai' => 'su_dung_tinh_nang_ai',
                'tham_chieu_id' => $usage->id,
                'idempotency_key' => $usage->idempotency_key . ':reserve',
                'mo_ta' => 'Tạm giữ tiền cho tính năng AI ' . $usage->feature_code,
                'metadata_json' => [
                    'feature_code' => $usage->feature_code,
                    'billing_mode' => $usage->billing_mode,
                ],
            ]);

            $usage->forceFill([
                'bien_dong_vi_reserve_id' => $transaction->id,
            ])->save();

            return $transaction;
        });
    }

    public function commitUsage(SuDungTinhNangAi $usage): BienDongVi
    {
        return DB::transaction(function () use ($usage): BienDongVi {
            $usage = SuDungTinhNangAi::query()->lockForUpdate()->findOrFail($usage->id);

            if ($usage->trang_thai === SuDungTinhNangAi::TRANG_THAI_THANH_CONG && $usage->bien_dong_vi_ket_toan_id) {
                return BienDongVi::query()->findOrFail($usage->bien_dong_vi_ket_toan_id);
            }

            $wallet = ViNguoiDung::query()
                ->where('nguoi_dung_id', $usage->nguoi_dung_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureWalletAvailable($wallet);

            $amount = (int) $usage->so_tien_du_kien;
            $balanceBefore = (int) $wallet->so_du_hien_tai;
            $holdBefore = (int) $wallet->so_du_tam_giu;

            $wallet->forceFill([
                'so_du_hien_tai' => max(0, $balanceBefore - $amount),
                'so_du_tam_giu' => max(0, $holdBefore - $amount),
            ])->save();

            $transaction = BienDongVi::create([
                'vi_nguoi_dung_id' => $wallet->id,
                'nguoi_dung_id' => $usage->nguoi_dung_id,
                'loai_bien_dong' => BienDongVi::LOAI_USAGE_CAPTURE,
                'so_tien' => $amount,
                'so_du_truoc' => $balanceBefore,
                'so_du_sau' => $wallet->so_du_hien_tai,
                'tam_giu_truoc' => $holdBefore,
                'tam_giu_sau' => $wallet->so_du_tam_giu,
                'trang_thai' => BienDongVi::TRANG_THAI_HOAN_TAT,
                'tham_chieu_loai' => 'su_dung_tinh_nang_ai',
                'tham_chieu_id' => $usage->id,
                'idempotency_key' => $usage->idempotency_key . ':capture',
                'mo_ta' => 'Khấu trừ tiền cho tính năng AI ' . $usage->feature_code,
                'metadata_json' => [
                    'feature_code' => $usage->feature_code,
                    'billing_mode' => $usage->billing_mode,
                ],
            ]);

            $usage->forceFill([
                'trang_thai' => SuDungTinhNangAi::TRANG_THAI_THANH_CONG,
                'so_tien_thuc_te' => $amount,
                'bien_dong_vi_ket_toan_id' => $transaction->id,
            ])->save();

            return $transaction;
        });
    }

    public function releaseUsage(SuDungTinhNangAi $usage, ?string $reason = null): BienDongVi
    {
        return DB::transaction(function () use ($usage, $reason): BienDongVi {
            $usage = SuDungTinhNangAi::query()->lockForUpdate()->findOrFail($usage->id);

            if ($usage->trang_thai === SuDungTinhNangAi::TRANG_THAI_THAT_BAI && $usage->bien_dong_vi_ket_toan_id) {
                return BienDongVi::query()->findOrFail($usage->bien_dong_vi_ket_toan_id);
            }

            $wallet = ViNguoiDung::query()
                ->where('nguoi_dung_id', $usage->nguoi_dung_id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (int) $usage->so_tien_du_kien;
            $balanceBefore = (int) $wallet->so_du_hien_tai;
            $holdBefore = (int) $wallet->so_du_tam_giu;

            $wallet->forceFill([
                'so_du_tam_giu' => max(0, $holdBefore - $amount),
            ])->save();

            $transaction = BienDongVi::create([
                'vi_nguoi_dung_id' => $wallet->id,
                'nguoi_dung_id' => $usage->nguoi_dung_id,
                'loai_bien_dong' => BienDongVi::LOAI_USAGE_RELEASE,
                'so_tien' => $amount,
                'so_du_truoc' => $balanceBefore,
                'so_du_sau' => $balanceBefore,
                'tam_giu_truoc' => $holdBefore,
                'tam_giu_sau' => $wallet->so_du_tam_giu,
                'trang_thai' => BienDongVi::TRANG_THAI_HOAN_TAT,
                'tham_chieu_loai' => 'su_dung_tinh_nang_ai',
                'tham_chieu_id' => $usage->id,
                'idempotency_key' => $usage->idempotency_key . ':release',
                'mo_ta' => 'Hoàn tạm giữ cho tính năng AI ' . $usage->feature_code,
                'metadata_json' => [
                    'feature_code' => $usage->feature_code,
                    'reason' => $reason,
                ],
            ]);

            $usage->forceFill([
                'trang_thai' => SuDungTinhNangAi::TRANG_THAI_THAT_BAI,
                'so_tien_thuc_te' => 0,
                'bien_dong_vi_ket_toan_id' => $transaction->id,
                'metadata_json' => array_filter([
                    ...($usage->metadata_json ?? []),
                    'failure_reason' => $reason,
                ]),
            ])->save();

            return $transaction;
        });
    }

    public function creditFromPayment(GiaoDichThanhToan $payment): BienDongVi
    {
        return DB::transaction(function () use ($payment): BienDongVi {
            $payment = GiaoDichThanhToan::query()->lockForUpdate()->findOrFail($payment->id);

            $existing = BienDongVi::query()
                ->where('nguoi_dung_id', $payment->nguoi_dung_id)
                ->where('idempotency_key', 'payment:' . $payment->id . ':credit')
                ->first();

            if ($existing) {
                return $existing;
            }

            $wallet = ViNguoiDung::query()->whereKey($payment->vi_nguoi_dung_id)->lockForUpdate()->firstOrFail();
            $this->ensureWalletAvailable($wallet);

            $amount = (int) $payment->so_tien;
            $balanceBefore = (int) $wallet->so_du_hien_tai;
            $holdBefore = (int) $wallet->so_du_tam_giu;

            $wallet->forceFill([
                'so_du_hien_tai' => $balanceBefore + $amount,
            ])->save();

            return BienDongVi::create([
                'vi_nguoi_dung_id' => $wallet->id,
                'nguoi_dung_id' => $payment->nguoi_dung_id,
                'loai_bien_dong' => BienDongVi::LOAI_TOPUP_CREDIT,
                'so_tien' => $amount,
                'so_du_truoc' => $balanceBefore,
                'so_du_sau' => $wallet->so_du_hien_tai,
                'tam_giu_truoc' => $holdBefore,
                'tam_giu_sau' => $holdBefore,
                'trang_thai' => BienDongVi::TRANG_THAI_HOAN_TAT,
                'tham_chieu_loai' => 'giao_dich_thanh_toan',
                'tham_chieu_id' => $payment->id,
                'idempotency_key' => 'payment:' . $payment->id . ':credit',
                'mo_ta' => $payment->noi_dung ?: 'Nạp tiền qua MoMo',
                'metadata_json' => [
                    'gateway' => $payment->gateway,
                    'payment_id' => $payment->id,
                ],
            ]);
        });
    }

    public function debitForSubscriptionPurchase(GiaoDichThanhToan $payment): BienDongVi
    {
        return DB::transaction(function () use ($payment): BienDongVi {
            $payment = GiaoDichThanhToan::query()
                ->with('goiDichVu')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $existing = BienDongVi::query()
                ->where('nguoi_dung_id', $payment->nguoi_dung_id)
                ->where('idempotency_key', 'payment:' . $payment->id . ':subscription-debit')
                ->first();

            if ($existing) {
                return $existing;
            }

            $wallet = ViNguoiDung::query()->whereKey($payment->vi_nguoi_dung_id)->lockForUpdate()->firstOrFail();
            $this->ensureWalletAvailable($wallet);

            $amount = (int) $payment->so_tien;
            if ($wallet->so_du_kha_dung < $amount) {
                throw BillingException::insufficientSubscriptionBalance($wallet->so_du_kha_dung, $amount);
            }

            $balanceBefore = (int) $wallet->so_du_hien_tai;
            $holdBefore = (int) $wallet->so_du_tam_giu;

            $wallet->forceFill([
                'so_du_hien_tai' => max(0, $balanceBefore - $amount),
            ])->save();

            return BienDongVi::create([
                'vi_nguoi_dung_id' => $wallet->id,
                'nguoi_dung_id' => $payment->nguoi_dung_id,
                'loai_bien_dong' => BienDongVi::LOAI_SUBSCRIPTION_PURCHASE_DEBIT,
                'so_tien' => $amount,
                'so_du_truoc' => $balanceBefore,
                'so_du_sau' => $wallet->so_du_hien_tai,
                'tam_giu_truoc' => $holdBefore,
                'tam_giu_sau' => $holdBefore,
                'trang_thai' => BienDongVi::TRANG_THAI_HOAN_TAT,
                'tham_chieu_loai' => 'giao_dich_thanh_toan',
                'tham_chieu_id' => $payment->id,
                'idempotency_key' => 'payment:' . $payment->id . ':subscription-debit',
                'mo_ta' => $payment->noi_dung ?: 'Thanh toán gói Pro bằng ví AI',
                'metadata_json' => [
                    'gateway' => $payment->gateway,
                    'payment_id' => $payment->id,
                    'plan_id' => $payment->goi_dich_vu_id,
                    'plan_code' => $payment->goiDichVu?->ma_goi,
                ],
            ]);
        });
    }

    private function ensureWalletAvailable(ViNguoiDung $wallet): void
    {
        if ($wallet->trang_thai !== ViNguoiDung::TRANG_THAI_HOAT_DONG) {
            throw BillingException::walletLocked();
        }
    }
}
