<?php

namespace App\Models;

use App\Models\Concerns\HasEncodedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NganhNghe extends Model
{
    use HasFactory, HasEncodedId;

    protected $table = 'nganh_nghes';

    protected $fillable = [
        'ten_nganh',
        'slug',
        'mo_ta',
        'danh_muc_cha_id',
        'icon',
        'trang_thai',
    ];

    protected $casts = [
        'danh_muc_cha_id' => 'integer',
        'trang_thai' => 'integer',
    ];

    protected $appends = [
        'encoded_id',
    ];

    // ==========================================
    // CONSTANTS
    // ==========================================
    const TRANG_THAI_AN = 0;
    const TRANG_THAI_HIEN_THI = 1;

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Ngành nghề cha (danh mục cấp trên).
     */
    public function danhMucCha()
    {
        return $this->belongsTo(NganhNghe::class, 'danh_muc_cha_id');
    }

    /**
     * Danh sách ngành nghề con (danh mục cấp dưới).
     */
    public function danhMucCon()
    {
        return $this->hasMany(NganhNghe::class, 'danh_muc_cha_id');
    }

    /**
     * Danh sách ngành nghề con (đệ quy — bao gồm cả cháu, chắt...).
     */
    public function danhMucConDeQuy()
    {
        return $this->hasMany(NganhNghe::class, 'danh_muc_cha_id')
            ->with('danhMucConDeQuy');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isHienThi(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_HIEN_THI;
    }

    public function isAn(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_AN;
    }

    public function isGoc(): bool
    {
        return is_null($this->danh_muc_cha_id);
    }

    /**
     * Tự động tạo slug từ tên ngành.
     */
    public static function taoSlug(string $tenNganh, ?int $excludeId = null): string
    {
        $slug = Str::slug($tenNganh);
        $original = $slug;
        $count = 1;

        $query = static::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
            $query = static::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}
