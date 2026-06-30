<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NguoiDungGoiDichVu extends Model
{
    use HasFactory;

    public const TRANG_THAI_PENDING = 'pending';
    public const TRANG_THAI_HOAT_DONG = 'active';
    public const TRANG_THAI_HUY = 'cancelled';
    public const TRANG_THAI_HET_HAN = 'expired';

    protected $table = 'nguoi_dung_goi_dich_vus';

    protected $fillable = [
        'nguoi_dung_id',
        'goi_dich_vu_id',
        'giao_dich_thanh_toan_id',
        'ngay_bat_dau',
        'ngay_het_han',
        'trang_thai',
        'auto_renew',
    ];

    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'goi_dich_vu_id' => 'integer',
        'giao_dich_thanh_toan_id' => 'integer',
        'ngay_bat_dau' => 'datetime',
        'ngay_het_han' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    protected $appends = [
        'is_active_now',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function goiDichVu()
    {
        return $this->belongsTo(GoiDichVu::class, 'goi_dich_vu_id');
    }

    public function giaoDichThanhToan()
    {
        return $this->belongsTo(GiaoDichThanhToan::class, 'giao_dich_thanh_toan_id');
    }

    public function getIsActiveNowAttribute(): bool
    {
        if ($this->trang_thai !== self::TRANG_THAI_HOAT_DONG) {
            return false;
        }

        $now = now();
        if ($this->ngay_bat_dau && $this->ngay_bat_dau->gt($now)) {
            return false;
        }

        if ($this->ngay_het_han && $this->ngay_het_han->lt($now)) {
            return false;
        }

        return true;
    }
}
