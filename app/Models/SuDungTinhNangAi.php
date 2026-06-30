<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuDungTinhNangAi extends Model
{
    use HasFactory;

    public const BILLING_MODE_FREE = 'free';
    public const BILLING_MODE_WALLET = 'wallet';
    public const BILLING_MODE_SUBSCRIPTION = 'subscription';

    public const TRANG_THAI_PENDING = 'pending';
    public const TRANG_THAI_THANH_CONG = 'success';
    public const TRANG_THAI_THAT_BAI = 'failed';

    protected $table = 'su_dung_tinh_nang_ais';

    protected $fillable = [
        'nguoi_dung_id',
        'feature_code',
        'so_luong',
        'don_gia_ap_dung',
        'so_tien_du_kien',
        'so_tien_thuc_te',
        'billing_mode',
        'trang_thai',
        'idempotency_key',
        'tham_chieu_loai',
        'tham_chieu_id',
        'bien_dong_vi_reserve_id',
        'bien_dong_vi_ket_toan_id',
        'metadata_json',
    ];

    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'so_luong' => 'integer',
        'don_gia_ap_dung' => 'integer',
        'so_tien_du_kien' => 'integer',
        'so_tien_thuc_te' => 'integer',
        'tham_chieu_id' => 'integer',
        'bien_dong_vi_reserve_id' => 'integer',
        'bien_dong_vi_ket_toan_id' => 'integer',
        'metadata_json' => 'array',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function reserveTransaction()
    {
        return $this->belongsTo(BienDongVi::class, 'bien_dong_vi_reserve_id');
    }

    public function settlementTransaction()
    {
        return $this->belongsTo(BienDongVi::class, 'bien_dong_vi_ket_toan_id');
    }
}
