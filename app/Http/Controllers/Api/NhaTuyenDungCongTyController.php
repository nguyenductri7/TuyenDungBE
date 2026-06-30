<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesEmployerCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\CongTy\TaoCongTyRequest;
use App\Http\Requests\CongTy\CapNhatCongTyRequest;
use App\Models\AuditLog;
use App\Models\CongTy;
use App\Models\NguoiDung;
use App\Models\PermissionDefinition;
use App\Services\HrAuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

/**
 * NhaTuyenDungCongTyController - NTD quản lý công ty của mình
 *
 * Vai trò: Nhà tuyển dụng (vai_tro = 1)
 * Mỗi NTD có thể tạo 1 công ty duy nhất.
 *
 * Routes:
 *   GET   /api/v1/nha-tuyen-dung/cong-ty        - Xem công ty của mình
 *   POST  /api/v1/nha-tuyen-dung/cong-ty        - Tạo công ty
 *   PUT   /api/v1/nha-tuyen-dung/cong-ty        - Cập nhật công ty
 */
class NhaTuyenDungCongTyController extends Controller
{
    use ResolvesEmployerCompany;

    public function __construct(
        private readonly HrAuditLogService $hrAuditLogService
    ) {
    }

    private function mapCompanyData(CongTy $congTy): array
    {
        $user = $this->getAuthenticatedEmployer();
        $congTy->loadMissing([
            'nganhNghe:id,ten_nganh',
            'thanhViens:id,ho_ten,email,so_dien_thoai,anh_dai_dien,trang_thai',
        ]);
        $congTy->loadCount('nguoiDungTheoDois');

        $data = $congTy->toArray();
        $data['logo_url'] = $congTy->logo
            ? url('/api/v1/cong-ty-logo?path=' . urlencode($congTy->logo))
            : null;
        $data['tong_so_hr'] = $congTy->thanhViens->count();
        $data['so_nguoi_theo_doi'] = $congTy->nguoi_dung_theo_dois_count ?? 0;
        $data['la_chu_so_huu'] = $this->isCompanyOwner($user, $congTy);
        $data['vai_tro_noi_bo_hien_tai'] = $user?->layVaiTroNoiBoCongTy($congTy);
        $data['ten_vai_tro_noi_bo_hien_tai'] = CongTy::nhanVaiTroNoiBo($data['vai_tro_noi_bo_hien_tai'], $congTy);
        $data['quyen_noi_bo'] = $user?->layQuyenNoiBoCongTy($congTy) ?? CongTy::normalizeHrPermissions(null);

        if ($this->canManageMembers($user, $congTy)) {
            $data['catalog_quyen_noi_bo'] = CongTy::hrPermissionCatalog();
            $data['vai_tro_noi_bo_options'] = $this->roleOptions();
            $data['vai_tro_noi_bo_custom'] = [];
            $data['thanh_viens'] = $congTy->thanhViens->map(function (NguoiDung $member) use ($congTy) {
                $payload = $member->toArray();
                $payload['vai_tro_noi_bo'] = CongTy::normalizeVaiTroNoiBo($member->pivot?->vai_tro_noi_bo);
                $payload['ten_vai_tro_noi_bo'] = CongTy::nhanVaiTroNoiBo($payload['vai_tro_noi_bo'], $congTy);
                $payload['quyen_noi_bo'] = $member->layQuyenNoiBoCongTy($congTy);
                $payload['so_quyen_noi_bo'] = count(array_filter($payload['quyen_noi_bo']));
                $payload['tong_quyen_noi_bo'] = count(CongTy::hrPermissionKeys());
                $payload['la_chu_so_huu'] = $payload['vai_tro_noi_bo'] === CongTy::VAI_TRO_NOI_BO_OWNER;
                $payload['avatar_url'] = $member->anh_dai_dien
                    ? url('/api/v1/anh-dai-dien?path=' . urlencode($member->anh_dai_dien))
                    : null;

                return $payload;
            })->values()->all();
        } else {
            $data['catalog_quyen_noi_bo'] = [];
            $data['vai_tro_noi_bo_options'] = [];
            $data['vai_tro_noi_bo_custom'] = [];
            $data['thanh_viens'] = [];
        }

        return $data;
    }

    private function roleOptions(): array
    {
        return CongTy::VAI_TRO_NOI_BO_LABELS;
    }

    private function mapInternalRoles(CongTy $congTy): array
    {
        return [];
    }

    private function validAssignableRoles(CongTy $congTy): array
    {
        return [CongTy::VAI_TRO_NOI_BO_MEMBER];
    }

    private function canManageMembers(NguoiDung $user, CongTy $congTy): bool
    {
        return $user->coQuyenNoiBoCongTy('members', $congTy);
    }

    private function generateHrPermissionKey(string $label): string
    {
        $baseKey = 'custom_' . Str::slug($label, '_');
        $baseKey = $baseKey === 'custom_' ? 'custom_permission' : Str::limit($baseKey, 90, '');
        $key = $baseKey;
        $index = 2;

        while (
            in_array($key, CongTy::hrPermissionKeys(), true)
            || PermissionDefinition::query()
                ->where('scope', PermissionDefinition::SCOPE_EMPLOYER)
                ->where('key', $key)
                ->exists()
        ) {
            $key = Str::limit($baseKey, 84, '') . '_' . $index;
            $index++;
        }

        return $key;
    }

    private function mapHrAuditLogData(AuditLog $log): array
    {
        $log->loadMissing([
            'actor:id,ho_ten,email',
        ]);
        $targetUser = null;

        if ($log->target_type === NguoiDung::class && $log->target_id) {
            $targetUser = NguoiDung::select('id', 'ho_ten', 'email')->find($log->target_id);
        }

        return [
            'id' => $log->id,
            'loai_su_kien' => $log->action,
            'mo_ta' => $log->description,
            'du_lieu_bo_sung' => $log->metadata_json,
            'nguoi_thuc_hien' => $log->actor ? [
                'id' => $log->actor->id,
                'ho_ten' => $log->actor->ho_ten,
                'email' => $log->actor->email,
            ] : null,
            'nguoi_bi_tac_dong' => $targetUser ? [
                'id' => $targetUser->id,
                'ho_ten' => $targetUser->ho_ten,
                'email' => $targetUser->email,
            ] : null,
            'created_at' => optional($log->created_at)?->toISOString(),
        ];
    }

    /**
     * GET /api/v1/nha-tuyen-dung/cong-ty
     * Xem công ty của NTD đang đăng nhập.
     */
    public function show(): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa tạo thông tin công ty.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapCompanyData($congTy),
        ]);
    }

    /**
     * POST /api/v1/nha-tuyen-dung/cong-ty
     * Tạo công ty (mỗi NTD chỉ 1 công ty).
     */
    public function store(TaoCongTyRequest $request): JsonResponse
    {
        $nguoiDungId = auth()->id();

        if ($this->getCurrentEmployerCompany()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã thuộc một công ty rồi. Hãy cập nhật thay vì tạo mới.',
            ], 422);
        }

        $data = $request->validated();
        $data['nguoi_dung_id'] = $nguoiDungId;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('cong_ty_logos', 'public');
        }

        $congTy = CongTy::create($data);
        $congTy->thanhViens()->syncWithoutDetaching([
            $nguoiDungId => [
                'vai_tro_noi_bo' => 'owner',
                'quyen_noi_bo' => json_encode(CongTy::defaultHrPermissions()),
                'duoc_tao_boi' => $nguoiDungId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $this->hrAuditLogService->log(
            $congTy,
            $this->getAuthenticatedEmployer(),
            'company_created',
            'Tạo công ty và khởi tạo owner đầu tiên.',
            $this->getAuthenticatedEmployer(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo công ty thành công.',
            'data' => $this->mapCompanyData($congTy),
        ], 201);
    }

    /**
     * PUT /api/v1/nha-tuyen-dung/cong-ty
     * Cập nhật công ty của mình.
     */
    public function update(CapNhatCongTyRequest $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa tạo thông tin công ty.',
            ], 404);
        }

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($congTy->logo) {
                Storage::disk('public')->delete($congTy->logo);
            }

            $data['logo'] = $request->file('logo')->store('cong_ty_logos', 'public');
        }

        $congTy->update($data);
        $this->hrAuditLogService->log(
            $congTy,
            $this->getAuthenticatedEmployer(),
            'company_updated',
            'Cập nhật thông tin công ty.',
            null,
            ['fields' => array_keys($data)],
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật công ty thành công.',
            'data' => $this->mapCompanyData($congTy->fresh()),
        ]);
    }

    public function members(): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cong_ty_id' => $congTy->id,
                'la_chu_so_huu' => $this->isCompanyOwner($this->getAuthenticatedEmployer(), $congTy),
                'vai_tro_noi_bo_options' => $this->roleOptions(),
                'vai_tro_noi_bo_custom' => [],
                'thanh_viens' => $this->mapCompanyData($congTy)['thanh_viens'],
            ],
        ]);
    }

    public function addMember(Request $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể quản lý thành viên HR.',
            ], 403);
        }

        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'mat_khau' => ['required', 'string', 'min:6'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'vai_tro_noi_bo' => ['nullable', 'string', 'max:50'],
        ], [
            'mat_khau.required' => 'Vui lòng nhập mật khẩu cho tài khoản HR mới.',
            'mat_khau.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ]);

        $email = mb_strtolower(trim($data['email']));
        $member = NguoiDung::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($member) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã tồn tại trong hệ thống. Vui lòng dùng email khác để tạo HR mới.',
            ], 422);
        }

        $assignedRole = CongTy::VAI_TRO_NOI_BO_MEMBER;
        $assignedPermissions = CongTy::normalizeHrPermissions(
            CongTy::defaultHrPermissionsForRole($assignedRole, $congTy)
        );
        $member = NguoiDung::create([
            'ho_ten' => trim((string) $data['ho_ten']),
            'email' => $email,
            'mat_khau' => $data['mat_khau'],
            'so_dien_thoai' => $data['so_dien_thoai'] ?? null,
            'vai_tro' => NguoiDung::VAI_TRO_NHA_TUYEN_DUNG,
            'trang_thai' => 1,
            'email_verified_at' => now(),
        ]);

        $congTy->thanhViens()->attach($member->id, [
            'vai_tro_noi_bo' => $assignedRole,
            'quyen_noi_bo' => json_encode($assignedPermissions),
            'duoc_tao_boi' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'member_added',
            "Tạo mới tài khoản HR {$member->email} với mật khẩu khởi tạo và thêm vào công ty với vai trò " . CongTy::nhanVaiTroNoiBo($assignedRole, $congTy) . '.',
            $member,
            [
                'vai_tro_noi_bo' => $assignedRole,
                'created_by_owner' => true,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo tài khoản HR với mật khẩu khởi tạo.',
            'data' => [
                'hr' => $member->only(['id', 'ho_ten', 'email', 'so_dien_thoai', 'vai_tro', 'trang_thai']),
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ], 201);
    }

    public function updateMemberRole(Request $request, int $memberId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể cập nhật vai trò HR.',
            ], 403);
        }

        $data = $request->validate([
            'vai_tro_noi_bo' => ['required', 'string', Rule::in($this->validAssignableRoles($congTy))],
        ], [
            'vai_tro_noi_bo.required' => 'Vui lòng chọn vai trò nội bộ.',
            'vai_tro_noi_bo.in' => 'Vai trò nội bộ không hợp lệ.',
        ]);

        $member = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy HR trong công ty.',
            ], 404);
        }

        if (($member->pivot?->vai_tro_noi_bo ?? '') === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể thay đổi vai trò của chủ sở hữu công ty.',
            ], 422);
        }

        $congTy->thanhViens()->updateExistingPivot($memberId, [
            'vai_tro_noi_bo' => $data['vai_tro_noi_bo'],
            'updated_at' => now(),
        ]);
        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'member_role_updated',
            "Cập nhật vai trò nội bộ của {$member->email} thành " . CongTy::nhanVaiTroNoiBo($data['vai_tro_noi_bo'], $congTy) . '.',
            $member,
            [
                'vai_tro_noi_bo' => $data['vai_tro_noi_bo'],
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật vai trò nội bộ.',
            'data' => [
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ]);
    }

    public function updateMember(Request $request, int $memberId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể cập nhật tài khoản HR.',
            ], 403);
        }

        $member = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy HR trong công ty.',
            ], 404);
        }

        if (($member->pivot?->vai_tro_noi_bo ?? '') === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể chỉnh sửa tài khoản chủ sở hữu công ty tại đây.',
            ], 422);
        }

        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('nguoi_dungs', 'email')->ignore($member->id)],
            'mat_khau' => ['nullable', 'string', 'min:6'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'trang_thai' => ['required', 'integer', Rule::in([0, 1])],
        ], [
            'email.unique' => 'Email này đã được sử dụng bởi tài khoản khác.',
            'mat_khau.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'trang_thai.in' => 'Trạng thái tài khoản HR không hợp lệ.',
        ]);

        $payload = [
            'ho_ten' => trim((string) $data['ho_ten']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'so_dien_thoai' => $data['so_dien_thoai'] ?? null,
            'trang_thai' => (int) $data['trang_thai'],
        ];

        if (!empty($data['mat_khau'])) {
            $payload['mat_khau'] = $data['mat_khau'];
        }

        $member->update($payload);

        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'member_updated',
            "Cập nhật thông tin tài khoản HR {$member->email}.",
            $member,
            ['fields' => array_keys($payload)],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật tài khoản HR.',
            'data' => [
                'hr' => $member->fresh()?->only(['id', 'ho_ten', 'email', 'so_dien_thoai', 'vai_tro', 'trang_thai']),
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ]);
    }

    public function toggleMemberStatus(int $memberId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể khóa hoặc mở khóa HR.',
            ], 403);
        }

        $member = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy HR trong công ty.',
            ], 404);
        }

        if (($member->pivot?->vai_tro_noi_bo ?? '') === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khóa tài khoản chủ sở hữu công ty.',
            ], 422);
        }

        $nextStatus = (int) $member->trang_thai === 1 ? 0 : 1;
        $member->update(['trang_thai' => $nextStatus]);

        $this->hrAuditLogService->log(
            $congTy,
            $user,
            $nextStatus === 1 ? 'member_unlocked' : 'member_locked',
            ($nextStatus === 1 ? 'Mở khóa' : 'Khóa') . " tài khoản HR {$member->email}.",
            $member,
            ['trang_thai' => $nextStatus],
        );

        return response()->json([
            'success' => true,
            'message' => $nextStatus === 1 ? 'Đã mở khóa tài khoản HR.' : 'Đã khóa tài khoản HR.',
            'data' => [
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ]);
    }

    public function removeMember(int $memberId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể quản lý thành viên HR.',
            ], 403);
        }

        $member = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy HR trong công ty.',
            ], 404);
        }

        if (($member->pivot?->vai_tro_noi_bo ?? '') === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gỡ chủ sở hữu công ty khỏi danh sách thành viên.',
            ], 422);
        }

        $congTy->thanhViens()->detach($memberId);
        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'member_removed',
            "Gỡ {$member->email} khỏi công ty.",
            $member,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã gỡ HR khỏi công ty.',
            'data' => [
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ]);
    }

    public function memberPermissions(int $memberId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cấu hình quyền HR.',
            ], 403);
        }

        $member = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy HR trong công ty.',
            ], 404);
        }

        if (($member->pivot?->vai_tro_noi_bo ?? '') === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return response()->json([
                'success' => false,
                'message' => 'Owner công ty luôn có toàn quyền và không cấu hình tại đây.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'hr' => $this->mapCompanyData($congTy)['thanh_viens']
                    ? collect($this->mapCompanyData($congTy)['thanh_viens'])->firstWhere('id', $member->id)
                    : null,
                'catalog' => CongTy::hrPermissionCatalog(),
                'quyen_noi_bo' => $member->layQuyenNoiBoCongTy($congTy),
            ],
        ]);
    }

    public function updateMemberPermissions(Request $request, int $memberId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cấu hình quyền HR.',
            ], 403);
        }

        $validated = $request->validate([
            'quyen_noi_bo' => ['required', 'array'],
            'quyen_noi_bo.*' => ['boolean'],
        ]);

        $submittedKeys = array_keys((array) $validated['quyen_noi_bo']);
        $invalidKeys = array_diff($submittedKeys, CongTy::hrPermissionKeys());

        if ($invalidKeys) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách quyền HR chứa quyền không hợp lệ.',
                'errors' => [
                    'quyen_noi_bo' => ['Quyền không hợp lệ: ' . implode(', ', $invalidKeys)],
                ],
            ], 422);
        }

        $member = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy HR trong công ty.',
            ], 404);
        }

        if (($member->pivot?->vai_tro_noi_bo ?? '') === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return response()->json([
                'success' => false,
                'message' => 'Owner công ty luôn có toàn quyền và không cấu hình tại đây.',
            ], 422);
        }

        $permissions = CongTy::normalizeHrPermissions($validated['quyen_noi_bo']);

        $congTy->thanhViens()->updateExistingPivot($memberId, [
            'quyen_noi_bo' => json_encode($permissions),
            'updated_at' => now(),
        ]);

        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'member_permissions_updated',
            "Cập nhật quyền chức năng của {$member->ho_ten} ({$member->email}).",
            $member,
            ['quyen_noi_bo' => $permissions],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật quyền chức năng HR.',
            'data' => [
                'hr' => collect($this->mapCompanyData($congTy->fresh())['thanh_viens'])->firstWhere('id', $member->id),
                'catalog' => CongTy::hrPermissionCatalog(),
                'quyen_noi_bo' => $permissions,
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ]);
    }

    public function createHrPermissionDefinition(Request $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo chức năng HR.',
            ], 403);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'mapped_permission_key' => ['required', 'string', Rule::in(CongTy::hrSystemPermissionKeys())],
        ], [
            'label.required' => 'Vui lòng nhập tên chức năng.',
            'mapped_permission_key.required' => 'Vui lòng chọn chức năng hệ thống để gắn quyền mới.',
            'mapped_permission_key.in' => 'Chức năng hệ thống được chọn không hợp lệ.',
        ]);

        $permission = PermissionDefinition::create([
            'scope' => PermissionDefinition::SCOPE_EMPLOYER,
            'key' => $this->generateHrPermissionKey($data['label']),
            'label' => trim((string) $data['label']),
            'description' => $data['description'] ?? null,
            'mapped_permission_key' => $data['mapped_permission_key'],
            'is_system' => false,
            'default_enabled' => false,
            'created_by' => $user->id,
        ]);

        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'hr_permission_definition_created',
            "Tạo chức năng HR {$permission->label}.",
            null,
            ['permission_key' => $permission->key],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo chức năng HR.',
            'data' => [
                'permission' => [
                    'key' => $permission->key,
                    'label' => $permission->label,
                    'description' => $permission->description,
                    'mapped_permission_key' => $permission->mapped_permission_key,
                ],
                'catalog' => CongTy::hrPermissionCatalog(),
            ],
        ], 201);
    }

    public function internalRoles(): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }



        return response()->json([
            'success' => true,
            'data' => [
                'vai_tro_he_thong' => CongTy::VAI_TRO_NOI_BO_LABELS,
                'vai_tro_tuy_chinh' => [],
                'vai_tro_noi_bo_options' => $this->roleOptions(),
            ],
        ]);
    }

    public function createInternalRole(Request $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể tạo vai trò nội bộ.',
            ], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'Hệ thống hiện chỉ giữ 2 vai trò nội bộ: Owner và HR thường.',
        ], 422);
    }

    public function updateInternalRole(Request $request, int $roleId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể cập nhật vai trò nội bộ.',
            ], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'Hệ thống hiện chỉ giữ 2 vai trò nội bộ: Owner và HR thường.',
        ], 422);
    }

    public function deleteInternalRole(int $roleId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->canManageMembers($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể xóa vai trò nội bộ.',
            ], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'Hệ thống hiện chỉ giữ 2 vai trò nội bộ: Owner và HR thường.',
        ], 422);
    }

    public function hrAuditLogs(Request $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        $logs = AuditLog::query()
            ->where('company_id', $congTy->id)
            ->where('metadata_json->scope', 'hr')
            ->with(['actor:id,ho_ten,email'])
            ->latest()
            ->paginate((int) $request->get('per_page', 10));

        $logs->setCollection(
            $logs->getCollection()->map(fn (AuditLog $log) => $this->mapHrAuditLogData($log))
        );

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
