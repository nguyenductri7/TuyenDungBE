<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NguoiDungKyNang extends Model
{
    /**
     * Tên bảng trong database.
     */
    protected $table = 'nguoi_dung_ky_nangs';

    protected $fillable = [
        'nguoi_dung_id',
        'ky_nang_id',
        'muc_do',
        'nam_kinh_nghiem',
        'so_chung_chi',
        'hinh_anh',
        'nguon_du_lieu',
        'do_tin_cay',
    ];

    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'ky_nang_id' => 'integer',
        'muc_do' => 'integer',
        'nam_kinh_nghiem' => 'integer',
        'so_chung_chi' => 'integer',
        'do_tin_cay' => 'float',
    ];

    // ==========================================
    // CONSTANTS — Mức độ thành thạo
    // ==========================================
    const MUC_DO_CO_BAN = 1;
    const MUC_DO_TRUNG_BINH = 2;
    const MUC_DO_KHA = 3;
    const MUC_DO_GIOI = 4;
    const MUC_DO_CHUYEN_GIA = 5;

    const MUC_DO_LIST = [1, 2, 3, 4, 5];

    const MUC_DO_LABELS = [
        1 => 'Cơ bản',
        2 => 'Trung bình',
        3 => 'Khá',
        4 => 'Giỏi',
        5 => 'Chuyên gia',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function kyNang()
    {
        return $this->belongsTo(KyNang::class, 'ky_nang_id');
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function getTenMucDoAttribute(): string
    {
        return self::MUC_DO_LABELS[$this->muc_do] ?? 'Không xác định';
    }
}
