<?php

namespace App\Models;

use App\Notifications\ResetPasswordLinkNotification;
use App\Notifications\VerifyEmailLinkNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class NguoiDung extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Tên bảng trong database.
     */
    protected $table = 'nguoi_dungs';

    /**
     * Các trường có thể gán hàng loạt (mass assignment).
     */
    protected $fillable = [
        'ho_ten',
        'email',
        'mat_khau',
        'so_dien_thoai',
        'email_verified_at',
        'ngay_sinh',
        'gioi_tinh',
        'dia_chi',
        'anh_dai_dien',
        'vai_tro',
        'cap_admin',
        'quyen_admin',
        'trang_thai',
    ];

    /**
     * Các trường ẩn khi serialize (trả về JSON).
     */
    protected $hidden = [
        'mat_khau',
    ];

    /**
     * Cast kiểu dữ liệu.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ngay_sinh' => 'date',
        'mat_khau' => 'hashed',
        'vai_tro' => 'integer',
        'cap_admin' => 'string',
        'quyen_admin' => 'array',
        'trang_thai' => 'integer',
    ];

    // ==========================================
    // CONSTANTS - Vai trò người dùng
    // ==========================================
    const VAI_TRO_UNG_VIEN = 0;
    const VAI_TRO_NHA_TUYEN_DUNG = 1;
    const VAI_TRO_ADMIN = 2;
    const CAP_ADMIN_SUPER_ADMIN = 'super_admin';
    const CAP_ADMIN_ADMIN = 'admin';
    const ADMIN_PERMISSION_CATALOG = [
        [
            'key' => 'users',
            'label' => 'Người dùng',
            'description' => 'Quản lý danh sách người dùng, cập nhật trạng thái và chỉnh sửa hồ sơ tài khoản thường.',
        ],
        [
            'key' => 'companies',
            'label' => 'Công ty',
            'description' => 'Quản lý hồ sơ công ty, trạng thái hoạt động và thông tin doanh nghiệp trên nền tảng.',
        ],
        [
            'key' => 'profiles',
            'label' => 'Hồ sơ',
            'description' => 'Xem, khôi phục hoặc dọn dẹp hồ sơ ứng viên trong toàn hệ thống.',
        ],
        [
            'key' => 'user_skills',
            'label' => 'Kỹ năng người dùng',
            'description' => 'Theo dõi dữ liệu kỹ năng mà ứng viên đã khai báo và chi tiết theo từng tài khoản.',
        ],
        [
            'key' => 'matchings',
            'label' => 'AI Matching',
            'description' => 'Xem lịch sử, điểm số và thống kê matching giữa hồ sơ và tin tuyển dụng.',
        ],
        [
            'key' => 'career_advising',
            'label' => 'AI Advising',
            'description' => 'Theo dõi báo cáo định hướng nghề nghiệp và các đề xuất nghề do AI sinh ra.',
        ],
        [
            'key' => 'ai_usage',
            'label' => 'AI Usage',
            'description' => 'Theo dõi lượng dùng AI, lỗi phát sinh và hành vi sử dụng các tính năng AI.',
        ],
        [
            'key' => 'billing',
            'label' => 'Billing',
            'description' => 'Quản lý giao dịch, gói dịch vụ, bảng giá và đối soát thanh toán.',
        ],
        [
            'key' => 'applications',
            'label' => 'Ứng tuyển',
            'description' => 'Giám sát danh sách ứng tuyển, trạng thái xử lý và dữ liệu theo công ty.',
        ],
        [
            'key' => 'skills',
            'label' => 'Kỹ năng',
            'description' => 'Quản trị danh mục kỹ năng dùng chung trên toàn hệ thống.',
        ],
        [
            'key' => 'industries',
            'label' => 'Ngành nghề',
            'description' => 'Quản trị danh mục ngành nghề và trạng thái hiển thị của từng ngành.',
        ],
        [
            'key' => 'jobs',
            'label' => 'Tin tuyển dụng',
            'description' => 'Quản lý danh sách job, cập nhật nội dung và bật tắt trạng thái tin tuyển dụng.',
        ],
        [
            'key' => 'cv_templates',
            'label' => 'Template CV',
            'description' => 'Quản trị thư viện mẫu CV đang cung cấp cho ứng viên.',
        ],
        [
            'key' => 'audit_logs',
            'label' => 'Nhật ký hệ thống',
            'description' => 'Xem toàn bộ audit log của hệ thống và các thao tác quản trị đã ghi nhận.',
        ],
        [
            'key' => 'stats',
            'label' => 'Báo cáo & phân tích',
            'description' => 'Xem các báo cáo tổng hợp, thống kê lưu tin và phân tích hiệu suất AI.',
        ],
    ];

    // ==========================================
    // HELPER METHODS - Kiểm tra vai trò
    // ==========================================

    public function isAdmin(): bool
    {
        return $this->vai_tro === self::VAI_TRO_ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && $this->cap_admin === self::CAP_ADMIN_SUPER_ADMIN;
    }

    public function isRegularAdmin(): bool
    {
        return $this->isAdmin() && $this->cap_admin === self::CAP_ADMIN_ADMIN;
    }

    public static function adminPermissionCatalog(): array
    {
        $catalog = self::ADMIN_PERMISSION_CATALOG;

        if (Schema::hasTable('permission_definitions')) {
            $hasMappedColumn = Schema::hasColumn('permission_definitions', 'mapped_permission_key');
            $customPermissions = PermissionDefinition::query()
                ->where('scope', PermissionDefinition::SCOPE_ADMIN)
                ->orderBy('id')
                ->get($hasMappedColumn ? ['key', 'label', 'description', 'mapped_permission_key'] : ['key', 'label', 'description'])
                ->map(fn (PermissionDefinition $permission) => [
                    'key' => $permission->key,
                    'label' => $permission->label,
                    'description' => $permission->description,
                    'mapped_permission_key' => $hasMappedColumn ? $permission->mapped_permission_key : null,
                    'is_custom' => true,
                ])
                ->all();

            $catalog = [
                ...$catalog,
                ...array_filter(
                    $customPermissions,
                    fn (array $permission) => !in_array($permission['key'], array_column($catalog, 'key'), true),
                ),
            ];
        }

        return array_values($catalog);
    }

    public static function adminPermissionKeys(): array
    {
        return array_column(self::adminPermissionCatalog(), 'key');
    }

    public static function adminSystemPermissionKeys(): array
    {
        return array_column(self::ADMIN_PERMISSION_CATALOG, 'key');
    }

    public static function adminPermissionLabelMap(): array
    {
        $labels = [];

        foreach (self::adminPermissionCatalog() as $permission) {
            $labels[$permission['key']] = $permission['label'];
        }

        return $labels;
    }

    public static function adminMappedPermissionKeys(): array
    {
        if (!Schema::hasTable('permission_definitions') || !Schema::hasColumn('permission_definitions', 'mapped_permission_key')) {
            return [];
        }

        return PermissionDefinition::query()
            ->where('scope', PermissionDefinition::SCOPE_ADMIN)
            ->whereNotNull('mapped_permission_key')
            ->pluck('mapped_permission_key', 'key')
            ->filter()
            ->all();
    }

    public static function defaultAdminPermissions(): array
    {
        return array_fill_keys(self::adminPermissionKeys(), false);
    }

    public static function allAdminPermissions(): array
    {
        return array_fill_keys(self::adminPermissionKeys(), true);
    }

    public static function normalizeAdminPermissions(?array $permissions): array
    {
        $defaults = self::defaultAdminPermissions();

        if (!$permissions) {
            return $defaults;
        }

        $normalized = [];
        foreach ($defaults as $key => $defaultValue) {
            $normalized[$key] = array_key_exists($key, $permissions)
                ? (bool) $permissions[$key]
                : $defaultValue;
        }

        foreach (self::adminMappedPermissionKeys() as $customKey => $mappedKey) {
            if (($normalized[$customKey] ?? false) && array_key_exists($mappedKey, $normalized)) {
                $normalized[$mappedKey] = true;
            }
        }

        return $normalized;
    }

    public function getResolvedAdminPermissionsAttribute(): array
    {
        if (!$this->isAdmin()) {
            return [];
        }

        if ($this->isSuperAdmin()) {
            return self::allAdminPermissions();
        }

        return self::normalizeAdminPermissions($this->quyen_admin);
    }

    public function hasAdminPermission(string ...$permissions): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $resolvedPermissions = $this->resolved_admin_permissions;

        foreach ($permissions as $permission) {
            if (($resolvedPermissions[$permission] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    public function isNhaTuyenDung(): bool
    {
        return $this->vai_tro === self::VAI_TRO_NHA_TUYEN_DUNG;
    }

    public function isUngVien(): bool
    {
        return $this->vai_tro === self::VAI_TRO_UNG_VIEN;
    }

    public function isActive(): bool
    {
        return $this->trang_thai === 1;
    }

    /**
     * Lấy nhãn vai trò dạng text.
     */
    public function getTenVaiTroAttribute(): string
    {
        return match ($this->vai_tro) {
            self::VAI_TRO_ADMIN => 'Admin',
            self::VAI_TRO_NHA_TUYEN_DUNG => 'Nhà tuyển dụng',
            self::VAI_TRO_UNG_VIEN => 'Ứng viên',
            default => 'Không xác định',
        };
    }

    public function getTenCapAdminAttribute(): ?string
    {
        if (!$this->isAdmin()) {
            return null;
        }

        return match ($this->cap_admin) {
            self::CAP_ADMIN_SUPER_ADMIN => 'Super Admin',
            self::CAP_ADMIN_ADMIN => 'Admin',
            default => 'Admin',
        };
    }

    /**
     * Danh sách hồ sơ của người dùng (ứng viên).
     */
    public function hoSos()
    {
        return $this->hasMany(\App\Models\HoSo::class, 'nguoi_dung_id');
    }

    /**
     * Danh sách tin tuyển dụng ứng viên ĐÃ LƯU.
     */
    public function tinDaLuus()
    {
        return $this->belongsToMany(\App\Models\TinTuyenDung::class, 'luu_tins', 'nguoi_dung_id', 'tin_tuyen_dung_id')
            ->withTimestamps();
    }

    /**
     * Công ty do nhà tuyển dụng sở hữu theo schema cũ.
     */
    public function congTy()
    {
        return $this->hasOne(\App\Models\CongTy::class, 'nguoi_dung_id');
    }

    /**
     * Danh sách công ty mà nhà tuyển dụng là thành viên.
     */
    public function congTyThanhViens()
    {
        return $this->belongsToMany(\App\Models\CongTy::class, 'cong_ty_nguoi_dungs', 'nguoi_dung_id', 'cong_ty_id')
            ->withPivot('id', 'vai_tro_noi_bo', 'quyen_noi_bo', 'duoc_tao_boi')
            ->withTimestamps();
    }

    /**
     * Danh sách công ty mà ứng viên đang theo dõi.
     */
    public function congTyTheoDois()
    {
        return $this->belongsToMany(\App\Models\CongTy::class, 'theo_doi_cong_tys', 'nguoi_dung_id', 'cong_ty_id')
            ->withTimestamps();
    }

    public function congTyHienTai(): ?\App\Models\CongTy
    {
        $company = $this->congTyThanhViens()
            ->orderByRaw("
                CASE
                    WHEN cong_ty_nguoi_dungs.vai_tro_noi_bo = ? THEN 0
                    ELSE 1
                END
            ", [CongTy::VAI_TRO_NOI_BO_OWNER])
            ->first();

        return $company ?: $this->congTy()->first();
    }

    public function laChuSoHuuCongTy(int $congTyId): bool
    {
        if ($this->congTy()->whereKey($congTyId)->exists()) {
            return true;
        }

        return $this->congTyThanhViens()
            ->where('cong_tys.id', $congTyId)
            ->wherePivot('vai_tro_noi_bo', CongTy::VAI_TRO_NOI_BO_OWNER)
            ->exists();
    }

    public function layVaiTroNoiBoCongTy(?CongTy $congTy = null): ?string
    {
        $company = $congTy ?? $this->congTyHienTai();

        if (!$company) {
            return null;
        }

        $membership = $this->congTyThanhViens()
            ->where('cong_tys.id', $company->id)
            ->first();

        if ($membership?->pivot?->vai_tro_noi_bo) {
            return CongTy::normalizeVaiTroNoiBo($membership->pivot->vai_tro_noi_bo);
        }

        if ($this->congTy()->whereKey($company->id)->exists()) {
            return CongTy::VAI_TRO_NOI_BO_OWNER;
        }

        return null;
    }

    public function coVaiTroNoiBoCongTy(array|string $roles, ?CongTy $congTy = null): bool
    {
        $currentRole = $this->layVaiTroNoiBoCongTy($congTy);
        $allowedRoles = collect(is_array($roles) ? $roles : [$roles])
            ->map(fn (string $role) => CongTy::normalizeVaiTroNoiBo($role))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $currentRole !== null && in_array($currentRole, $allowedRoles, true);
    }

    public function layQuyenNoiBoCongTy(?CongTy $congTy = null): array
    {
        $company = $congTy ?? $this->congTyHienTai();

        if (!$company) {
            return CongTy::normalizeHrPermissions(null);
        }

        $role = $this->layVaiTroNoiBoCongTy($company);

        if ($role === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return CongTy::defaultHrPermissions();
        }

        $membership = $this->congTyThanhViens()
            ->where('cong_tys.id', $company->id)
            ->first();

        $permissions = $membership?->pivot?->quyen_noi_bo;

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: null;
        }

        if (is_array($permissions)) {
            return CongTy::normalizeHrPermissions($permissions);
        }

        return CongTy::normalizeHrPermissions(CongTy::defaultHrPermissionsForRole($role, $company));
    }

    public function coQuyenNoiBoCongTy(array|string $permissions, ?CongTy $congTy = null): bool
    {
        $company = $congTy ?? $this->congTyHienTai();

        if (!$company) {
            return false;
        }

        if ($this->layVaiTroNoiBoCongTy($company) === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return true;
        }

        $requiredPermissions = is_array($permissions) ? $permissions : [$permissions];
        $resolvedPermissions = $this->layQuyenNoiBoCongTy($company);

        foreach ($requiredPermissions as $permission) {
            if (($resolvedPermissions[$permission] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Danh sách kỹ năng của người dùng (qua bảng nguoi_dung_ky_nangs).
     */
    public function kyNangs()
    {
        return $this->belongsToMany(\App\Models\KyNang::class, 'nguoi_dung_ky_nangs', 'nguoi_dung_id', 'ky_nang_id')
            ->withPivot('muc_do', 'nam_kinh_nghiem', 'so_chung_chi', 'hinh_anh')
            ->withTimestamps();
    }

    /**
     * Các phiên chat AI của người dùng.
     */
    public function aiChatSessions()
    {
        return $this->hasMany(AiChatSession::class, 'nguoi_dung_id');
    }

    /**
     * Các báo cáo mock interview của người dùng.
     */
    public function aiInterviewReports()
    {
        return $this->hasMany(AiInterviewReport::class, 'nguoi_dung_id');
    }

    /**
     * Các báo cáo tư vấn nghề nghiệp đã sinh cho người dùng.
     */
    public function tuVanNgheNghieps()
    {
        return $this->hasMany(TuVanNgheNghiep::class, 'nguoi_dung_id');
    }

    public function viNguoiDung()
    {
        return $this->hasOne(ViNguoiDung::class, 'nguoi_dung_id');
    }

    public function giaoDichThanhToans()
    {
        return $this->hasMany(GiaoDichThanhToan::class, 'nguoi_dung_id');
    }

    public function suDungTinhNangAis()
    {
        return $this->hasMany(SuDungTinhNangAi::class, 'nguoi_dung_id');
    }

    public function nguoiDungGoiDichVus()
    {
        return $this->hasMany(NguoiDungGoiDichVu::class, 'nguoi_dung_id');
    }

    /**
     * Override tên field password cho Laravel Auth.
     */
    public function getAuthPassword(): string
    {
        return $this->mat_khau;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLinkNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailLinkNotification());
    }
}
