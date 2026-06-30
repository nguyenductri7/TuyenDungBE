<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViNguoiDung extends Model
{
    use HasFactory;

    public const TRANG_THAI_HOAT_DONG = 'active';
    public const TRANG_THAI_KHOA = 'locked';

    protected $table = 'vi_nguoi_dungs';

    protected $fillable = [
        'nguoi_dung_id',
        'so_du_hien_tai',
        'so_du_tam_giu',
        'don_vi_tien_te',
        'trang_thai',
    ];

    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'so_du_hien_tai' => 'integer',
        'so_du_tam_giu' => 'integer',
    ];

    protected $appends = [
        'so_du_kha_dung',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function bienDongVis()
    {
        return $this->hasMany(BienDongVi::class, 'vi_nguoi_dung_id');
    }

    public function giaoDichThanhToans()
    {
        return $this->hasMany(GiaoDichThanhToan::class, 'vi_nguoi_dung_id');
    }

    public function getSoDuKhaDungAttribute(): int
    {
        return max(0, (int) $this->so_du_hien_tai - (int) $this->so_du_tam_giu);
    }
}
