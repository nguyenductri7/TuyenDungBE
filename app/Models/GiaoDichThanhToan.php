<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiaoDichThanhToan extends Model
{
    use HasFactory;

    public const GATEWAY_MOMO = 'momo';
    public const GATEWAY_VNPAY = 'vnpay';
    public const GATEWAY_WALLET = 'wallet';

    public const LOAI_NAP_VI = 'topup_wallet';
    public const LOAI_MUA_GOI = 'buy_subscription';

    public const TRANG_THAI_PENDING = 'pending';
    public const TRANG_THAI_THANH_CONG = 'success';
    public const TRANG_THAI_THAT_BAI = 'failed';
    public const TRANG_THAI_HUY = 'cancelled';

    protected $table = 'giao_dich_thanh_toans';

    protected $fillable = [
        'nguoi_dung_id',
        'vi_nguoi_dung_id',
        'goi_dich_vu_id',
        'gateway',
        'ma_giao_dich_noi_bo',
        'ma_yeu_cau',
        'ma_giao_dich_gateway',
        'loai_giao_dich',
        'so_tien',
        'noi_dung',
        'redirect_url',
        'trang_thai',
        'raw_request_json',
        'raw_response_json',
        'return_payload_json',
        'ipn_payload_json',
        'paid_at',
    ];

    protected $appends = [
        'payment_link_expires_at',
        'is_payment_link_expired',
    ];

    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'vi_nguoi_dung_id' => 'integer',
        'goi_dich_vu_id' => 'integer',
        'so_tien' => 'integer',
        'raw_request_json' => 'array',
        'raw_response_json' => 'array',
        'return_payload_json' => 'array',
        'ipn_payload_json' => 'array',
        'paid_at' => 'datetime',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function viNguoiDung()
    {
        return $this->belongsTo(ViNguoiDung::class, 'vi_nguoi_dung_id');
    }

    public function goiDichVu()
    {
        return $this->belongsTo(GoiDichVu::class, 'goi_dich_vu_id');
    }

    public function getPaymentLinkExpiresAtAttribute(): ?string
    {
        if (!$this->redirect_url || !$this->created_at) {
            return null;
        }

        return $this->created_at
            ->copy()
            ->addMinutes($this->paymentLinkExpireMinutes())
            ->toISOString();
    }

    public function getIsPaymentLinkExpiredAttribute(): bool
    {
        if ($this->trang_thai !== self::TRANG_THAI_PENDING || !$this->created_at) {
            return false;
        }

        return now()->greaterThanOrEqualTo(
            $this->created_at->copy()->addMinutes($this->paymentLinkExpireMinutes())
        );
    }

    private function paymentLinkExpireMinutes(): int
    {
        return match ($this->gateway) {
            self::GATEWAY_VNPAY => max(1, (int) config('services.vnpay.pending_expire_minutes', 15)),
            self::GATEWAY_MOMO => max(1, (int) config('services.momo.pending_expire_minutes', 15)),
            default => max(1, (int) config('services.momo.pending_expire_minutes', 15)),
        };
    }
}
