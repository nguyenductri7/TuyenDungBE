<?php

namespace App\Models;

use App\Models\Concerns\HasEncodedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KyNang extends Model
{
    use HasFactory, HasEncodedId;

    /**
     * Tên bảng trong database.
     */
    protected $table = 'ky_nangs';

    /**
     * Các trường có thể gán hàng loạt (mass assignment).
     *
     * Chỉ chứa thông tin catalog (Admin quản lý):
     *   - ten_ky_nang: tên kỹ năng
     *   - mo_ta: mô tả ngắn
     *   - icon: icon/logo đại diện
     *
     * Lưu ý: so_chung_chi và hinh_anh đã chuyển sang bảng nguoi_dung_ky_nangs
     * để ứng viên tự quản lý dữ liệu cá nhân.
     */
    protected $fillable = [
        'ten_ky_nang',
        'mo_ta',
        'icon',
    ];

    protected $appends = [
        'encoded_id',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Danh sách người dùng có kỹ năng này (qua bảng nguoi_dung_ky_nangs).
     */
    public function nguoiDungs()
    {
        return $this->belongsToMany(NguoiDung::class, 'nguoi_dung_ky_nangs', 'ky_nang_id', 'nguoi_dung_id')
            ->withPivot('muc_do', 'nam_kinh_nghiem', 'so_chung_chi', 'hinh_anh')
            ->withTimestamps();
    }

    /**
     * Các kỹ năng được gắn làm yêu cầu cho tin tuyển dụng.
     */
    public function tinTuyenDungKyNangs()
    {
        return $this->hasMany(TinTuyenDungKyNang::class, 'ky_nang_id');
    }
}
