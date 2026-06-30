<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class HoSo extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tên bảng trong database.
     */
    protected $table = 'ho_sos';

    /**
     * Các trường có thể gán hàng loạt (mass assignment).
     */
    protected $fillable = [
        'nguoi_dung_id',
        'tieu_de_ho_so',
        'muc_tieu_nghe_nghiep',
        'trinh_do',
        'kinh_nghiem_nam',
        'mo_ta_ban_than',
        'file_cv',
        'nguon_ho_so',
        'mau_cv',
        'bo_cuc_cv',
        'ten_template_cv',
        'che_do_mau_cv',
        'vi_tri_ung_tuyen_muc_tieu',
        'ten_nganh_nghe_muc_tieu',
        'che_do_anh_cv',
        'anh_cv',
        'ky_nang_json',
        'kinh_nghiem_json',
        'hoc_van_json',
        'du_an_json',
        'chung_chi_json',
        'trang_thai',
    ];

    /**
     * Cast kiểu dữ liệu.
     */
    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'kinh_nghiem_nam' => 'float',
        'trang_thai' => 'integer',
        'ky_nang_json' => 'array',
        'kinh_nghiem_json' => 'array',
        'hoc_van_json' => 'array',
        'du_an_json' => 'array',
        'chung_chi_json' => 'array',
    ];

    protected $appends = [
        'anh_cv_url',
    ];

    // ==========================================
    // CONSTANTS - Trạng thái hồ sơ
    // ==========================================
    const TRANG_THAI_AN = 0;
    const TRANG_THAI_CONG_KHAI = 1;

    // ==========================================
    // CONSTANTS - Trình độ
    // ==========================================
    const TRINH_DO_LIST = [
        'Trung học',
        'Trung cấp',
        'Cao đẳng',
        'Đại học',
        'Thạc sĩ',
        'Tiến sĩ',
        'Khác',
    ];

    const TRINH_DO_LABELS = [
        'trung_hoc' => 'Trung học',
        'trung_cap' => 'Trung cấp',
        'cao_dang' => 'Cao đẳng',
        'dai_hoc' => 'Đại học',
        'thac_si' => 'Thạc sĩ',
        'tien_si' => 'Tiến sĩ',
        'khac' => 'Khác',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Người dùng sở hữu hồ sơ này.
     */
    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    /**
     * Lịch sử ứng tuyển của hồ sơ này.
     */
    public function ungTuyens()
    {
        return $this->hasMany(\App\Models\UngTuyen::class, 'ho_so_id');
    }

    /**
     * Kết quả parse CV gần nhất của hồ sơ.
     */
    public function parsing()
    {
        return $this->hasOne(HoSoParsing::class, 'ho_so_id');
    }

    /**
     * Các kết quả matching liên quan tới hồ sơ.
     */
    public function ketQuaMatchings()
    {
        return $this->hasMany(KetQuaMatching::class, 'ho_so_id');
    }

    /**
     * Các báo cáo tư vấn nghề nghiệp sinh từ hồ sơ này.
     */
    public function tuVanNgheNghieps()
    {
        return $this->hasMany(TuVanNgheNghiep::class, 'ho_so_id');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isCongKhai(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_CONG_KHAI;
    }

    public function isAn(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_AN;
    }

    public static function acceptedTrinhDoValues(): array
    {
        return array_values(array_unique([
            ...array_keys(self::TRINH_DO_LABELS),
            ...array_values(self::TRINH_DO_LABELS),
        ]));
    }

    public static function normalizeTrinhDo(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return self::TRINH_DO_LABELS[$value] ?? (
            in_array($value, self::TRINH_DO_LIST, true) ? $value : $value
        );
    }

    public static function legacyTrinhDoKey(?string $value): ?string
    {
        $normalized = self::normalizeTrinhDo($value);

        if ($normalized === null) {
            return null;
        }

        $key = array_search($normalized, self::TRINH_DO_LABELS, true);

        return $key === false ? null : $key;
    }

    public static function trinhDoQueryValues(?string $value): array
    {
        $normalized = self::normalizeTrinhDo($value);
        $legacyKey = self::legacyTrinhDoKey($value);

        return array_values(array_filter(array_unique([$normalized, $legacyKey])));
    }

    public function getTrinhDoAttribute($value): ?string
    {
        return self::normalizeTrinhDo($value);
    }

    public function setTrinhDoAttribute($value): void
    {
        $this->attributes['trinh_do'] = self::normalizeTrinhDo($value);
    }

    /**
     * Lấy nhãn trạng thái dạng text.
     */
    public function getTenTrangThaiAttribute(): string
    {
        return match ($this->trang_thai) {
            self::TRANG_THAI_CONG_KHAI => 'Công khai',
            self::TRANG_THAI_AN => 'Ẩn',
            default => 'Không xác định',
        };
    }

    /**
     * Lấy nhãn trình độ dạng text.
     */
    public function getTenTrinhDoAttribute(): string
    {
        return self::normalizeTrinhDo($this->trinh_do) ?? 'Chưa cập nhật';
    }

    public function getAnhCvUrlAttribute(): ?string
    {
        $path = $this->attributes['anh_cv'] ?? null;

        if (!$path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
