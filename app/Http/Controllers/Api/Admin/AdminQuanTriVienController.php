<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NguoiDung\AdminCapNhatQuanTriVienRequest;
use App\Http\Requests\NguoiDung\AdminCapNhatQuyenRequest;
use App\Http\Requests\NguoiDung\AdminTaoQuanTriVienRequest;
use App\Models\NguoiDung;
use App\Models\PermissionDefinition;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminQuanTriVienController extends Controller
{
    private function adminQuery()
    {
        return NguoiDung::query()->where('vai_tro', NguoiDung::VAI_TRO_ADMIN);
    }

    private function resolveAdminOrFail(int $id): NguoiDung
    {
        return $this->adminQuery()->findOrFail($id);
    }

    private function adminAuditSnapshot(NguoiDung $nguoiDung): array
    {
        return [
            'id' => $nguoiDung->id,
            'ho_ten' => $nguoiDung->ho_ten,
            'email' => $nguoiDung->email,
            'so_dien_thoai' => $nguoiDung->so_dien_thoai,
            'vai_tro' => $nguoiDung->vai_tro,
            'cap_admin' => $nguoiDung->cap_admin,
            'ten_cap_admin' => $nguoiDung->ten_cap_admin,
            'quyen_admin' => $nguoiDung->resolved_admin_permissions,
            'trang_thai' => $nguoiDung->trang_thai,
        ];
    }

    private function mapAdminData(NguoiDung $nguoiDung): array
    {
        $data = $nguoiDung->toArray();
        $resolvedPermissions = $nguoiDung->resolved_admin_permissions;
        $data['cap_admin'] = $nguoiDung->cap_admin ?: NguoiDung::CAP_ADMIN_ADMIN;
        $data['ten_cap_admin'] = $nguoiDung->ten_cap_admin;
        $data['la_super_admin'] = $nguoiDung->isSuperAdmin();
        $data['quyen_admin'] = $resolvedPermissions;
        $data['so_quyen_admin'] = count(array_filter($resolvedPermissions));
        $data['tong_quyen_admin'] = count(NguoiDung::adminPermissionKeys());

        return $data;
    }

    private function permissionCatalog(): array
    {
        return NguoiDung::adminPermissionCatalog();
    }

    private function generatePermissionKey(string $label, string $scope): string
    {
        $baseKey = 'custom_' . Str::slug($label, '_');
        $baseKey = $baseKey === 'custom_' ? 'custom_permission' : Str::limit($baseKey, 90, '');
        $key = $baseKey;
        $index = 2;

        while (
            in_array($key, NguoiDung::adminPermissionKeys(), true)
            || PermissionDefinition::query()->where('scope', $scope)->where('key', $key)->exists()
        ) {
            $key = Str::limit($baseKey, 84, '') . '_' . $index;
            $index++;
        }

        return $key;
    }

    private function protectedSuperAdminResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Super Admin duy nhất chỉ được quản lý qua hồ sơ cá nhân, không thao tác trong module này.',
        ], 422);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->adminQuery();

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', (int) $request->trang_thai);
        }

        if ($request->filled('cap_admin')) {
            $query->where('cap_admin', $request->string('cap_admin')->toString());
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('ho_ten', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'ho_ten', 'email', 'cap_admin', 'trang_thai', 'created_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts, true)
            ? $request->get('sort_by')
            : 'created_at';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $admins = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate(min((int) $request->get('per_page', 10), 100));

        $admins->setCollection(
            $admins->getCollection()->map(fn (NguoiDung $nguoiDung) => $this->mapAdminData($nguoiDung))
        );

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    public function thongKe(): JsonResponse
    {
        $query = $this->adminQuery();

        return response()->json([
            'success' => true,
            'data' => [
                'tong_admin' => (clone $query)->count(),
                'tong_super_admin' => (clone $query)->where('cap_admin', NguoiDung::CAP_ADMIN_SUPER_ADMIN)->count(),
                'tong_admin_thuong' => (clone $query)->where('cap_admin', NguoiDung::CAP_ADMIN_ADMIN)->count(),
                'dang_hoat_dong' => (clone $query)->where('trang_thai', 1)->count(),
                'bi_khoa' => (clone $query)->where('trang_thai', 0)->count(),
                'tao_trong_30_ngay' => (clone $query)->where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->mapAdminData($this->resolveAdminOrFail($id)),
        ]);
    }

    public function store(AdminTaoQuanTriVienRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['vai_tro'] = NguoiDung::VAI_TRO_ADMIN;
        $payload['cap_admin'] = NguoiDung::CAP_ADMIN_ADMIN;
        $payload['quyen_admin'] = NguoiDung::defaultAdminPermissions();
        $payload['trang_thai'] = (int) ($payload['trang_thai'] ?? 1);

        $admin = NguoiDung::create($payload);
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_account_created',
            description: "Super Admin tạo tài khoản admin {$admin->email}.",
            target: $admin,
            after: $this->adminAuditSnapshot($admin),
            metadata: ['scope' => 'admin_account'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo tài khoản admin thường.',
            'data' => $this->mapAdminData($admin),
        ], 201);
    }

    public function update(AdminCapNhatQuanTriVienRequest $request, int $id): JsonResponse
    {
        $admin = $this->resolveAdminOrFail($id);

        if ($admin->isSuperAdmin()) {
            return $this->protectedSuperAdminResponse();
        }

        $before = $this->adminAuditSnapshot($admin);
        $validated = $request->validated();
        $admin->update($validated);

        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_account_updated',
            description: "Super Admin cập nhật tài khoản admin {$admin->email}.",
            target: $admin,
            before: $before,
            after: $this->adminAuditSnapshot($admin->fresh()),
            metadata: ['scope' => 'admin_account'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật tài khoản admin.',
            'data' => $this->mapAdminData($admin->fresh()),
        ]);
    }

    public function permissions(int $id): JsonResponse
    {
        $admin = $this->resolveAdminOrFail($id);

        if ($admin->isSuperAdmin()) {
            return $this->protectedSuperAdminResponse();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'admin' => $this->mapAdminData($admin),
                'catalog' => $this->permissionCatalog(),
                'quyen_admin' => $admin->resolved_admin_permissions,
            ],
        ]);
    }

    public function updatePermissions(AdminCapNhatQuyenRequest $request, int $id): JsonResponse
    {
        $admin = $this->resolveAdminOrFail($id);

        if ($admin->isSuperAdmin()) {
            return $this->protectedSuperAdminResponse();
        }

        $before = $this->adminAuditSnapshot($admin);
        $permissions = NguoiDung::normalizeAdminPermissions($request->validated('quyen_admin'));

        $admin->update([
            'quyen_admin' => $permissions,
        ]);

        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_account_permissions_updated',
            description: "Super Admin cập nhật quyền chức năng cho admin {$admin->email}.",
            target: $admin,
            before: $before,
            after: $this->adminAuditSnapshot($admin->fresh()),
            metadata: ['scope' => 'admin_account_permissions'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật quyền chức năng cho admin.',
            'data' => [
                'admin' => $this->mapAdminData($admin->fresh()),
                'catalog' => $this->permissionCatalog(),
                'quyen_admin' => $admin->fresh()->resolved_admin_permissions,
            ],
        ]);
    }

    public function storePermissionDefinition(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'mapped_permission_key' => ['required', 'string', Rule::in(NguoiDung::adminSystemPermissionKeys())],
        ], [
            'label.required' => 'Vui lòng nhập tên chức năng.',
            'mapped_permission_key.required' => 'Vui lòng chọn chức năng hệ thống để gắn quyền mới.',
            'mapped_permission_key.in' => 'Chức năng hệ thống được chọn không hợp lệ.',
        ]);

        $permission = PermissionDefinition::create([
            'scope' => PermissionDefinition::SCOPE_ADMIN,
            'key' => $this->generatePermissionKey($data['label'], PermissionDefinition::SCOPE_ADMIN),
            'label' => trim((string) $data['label']),
            'description' => $data['description'] ?? null,
            'mapped_permission_key' => $data['mapped_permission_key'],
            'is_system' => false,
            'default_enabled' => false,
            'created_by' => $request->user()?->id,
        ]);

        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_permission_definition_created',
            description: "Super Admin tạo chức năng admin {$permission->label}.",
            target: $permission,
            after: $permission->toArray(),
            metadata: ['scope' => 'admin_permission_definition'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo chức năng admin.',
            'data' => [
                'permission' => [
                    'key' => $permission->key,
                    'label' => $permission->label,
                    'description' => $permission->description,
                    'mapped_permission_key' => $permission->mapped_permission_key,
                ],
                'catalog' => $this->permissionCatalog(),
            ],
        ], 201);
    }

    public function khoaTaiKhoan(Request $request, int $id): JsonResponse
    {
        $admin = $this->resolveAdminOrFail($id);

        if ($admin->id === $request->user()?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khóa chính tài khoản Super Admin đang đăng nhập.',
            ], 422);
        }

        if ($admin->isSuperAdmin()) {
            return $this->protectedSuperAdminResponse();
        }

        $before = $this->adminAuditSnapshot($admin);
        $admin->trang_thai = $admin->trang_thai ? 0 : 1;
        $admin->save();

        if (!$admin->trang_thai) {
            $admin->tokens()->delete();
        }

        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: $admin->trang_thai ? 'admin_account_unlocked' : 'admin_account_locked',
            description: "Super Admin " . ($admin->trang_thai ? 'mở khóa' : 'khóa') . " tài khoản admin {$admin->email}.",
            target: $admin,
            before: $before,
            after: $this->adminAuditSnapshot($admin),
            metadata: ['scope' => 'admin_account'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => $admin->trang_thai ? 'Đã mở khóa tài khoản admin.' : 'Đã khóa tài khoản admin.',
            'data' => $this->mapAdminData($admin),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $admin = $this->resolveAdminOrFail($id);

        if ($admin->id === $request->user()?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa chính tài khoản Super Admin đang đăng nhập.',
            ], 422);
        }

        if ($admin->isSuperAdmin()) {
            return $this->protectedSuperAdminResponse();
        }

        $before = $this->adminAuditSnapshot($admin);
        $admin->tokens()->delete();
        $admin->delete();

        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_account_deleted',
            description: "Super Admin xóa tài khoản admin {$before['email']}.",
            target: $admin,
            before: $before,
            metadata: ['scope' => 'admin_account'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tài khoản admin.',
        ]);
    }
}
