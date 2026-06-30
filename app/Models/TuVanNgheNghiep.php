<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TuVanNgheNghiep extends Model
{
    use HasFactory;

    protected $table = 'tu_van_nghe_nghieps';

    protected $fillable = [
        'nguoi_dung_id',
        'ho_so_id',
        'nghe_de_xuat',
        'muc_do_phu_hop',
        'goi_y_ky_nang_bo_sung',
        'bao_cao_chi_tiet',
        'model_version',
    ];

    protected $casts = [
        'muc_do_phu_hop' => 'float',
        'goi_y_ky_nang_bo_sung' => 'array',
    ];

    /**
     * Người dùng nhận được bản báo cáo/tư vấn
     */
    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    /**
     * Phân tích dựa trên CV/Hồ sơ nào
     */
    public function hoSo()
    {
        return $this->belongsTo(HoSo::class, 'ho_so_id')->withTrashed();
    }
}
