<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BienDongVi extends Model
{
    use HasFactory;

    public const LOAI_TOPUP_CREDIT = 'topup_credit';
    public const LOAI_SUBSCRIPTION_PURCHASE_DEBIT = 'subscription_purchase_debit';
    public const LOAI_USAGE_RESERVE = 'usage_reserve';
    public const LOAI_USAGE_CAPTURE = 'usage_capture';
    public const LOAI_USAGE_RELEASE = 'usage_release';

    public const TRANG_THAI_HOAN_TAT = 'completed';

    protected $table = 'bien_dong_vi';

    protected $fillable = [
        'vi_nguoi_dung_id',
        'nguoi_dung_id',
        'loai_bien_dong',
        'so_tien',
        'so_du_truoc',
        'so_du_sau',
        'tam_giu_truoc',
        'tam_giu_sau',
        'trang_thai',
        'tham_chieu_loai',
        'tham_chieu_id',
        'idempotency_key',
        'mo_ta',
        'metadata_json',
    ];

    protected $casts = [
        'vi_nguoi_dung_id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'so_tien' => 'integer',
        'so_du_truoc' => 'integer',
        'so_du_sau' => 'integer',
        'tam_giu_truoc' => 'integer',
        'tam_giu_sau' => 'integer',
        'tham_chieu_id' => 'integer',
        'metadata_json' => 'array',
    ];

    public function viNguoiDung()
    {
        return $this->belongsTo(ViNguoiDung::class, 'vi_nguoi_dung_id');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }
}
