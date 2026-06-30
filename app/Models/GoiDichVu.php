<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoiDichVu extends Model
{
    use HasFactory;

    public const CHU_KY_FREE = 'free';
    public const CHU_KY_THANG = 'monthly';
    public const CHU_KY_NAM = 'yearly';

    public const TRANG_THAI_HOAT_DONG = 'active';
    public const TRANG_THAI_NGUNG_HOAT_DONG = 'inactive';

    protected $table = 'goi_dich_vus';

    protected $fillable = [
        'ma_goi',
        'ten_goi',
        'mo_ta',
        'gia',
        'chu_ky',
        'trang_thai',
        'is_free',
    ];

    protected $casts = [
        'gia' => 'integer',
        'is_free' => 'boolean',
    ];

    public function tinhNangs()
    {
        return $this->hasMany(GoiDichVuTinhNang::class, 'goi_dich_vu_id');
    }

    public function nguoiDungGoiDichVus()
    {
        return $this->hasMany(NguoiDungGoiDichVu::class, 'goi_dich_vu_id');
    }
}
