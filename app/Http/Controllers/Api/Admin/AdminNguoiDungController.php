<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NguoiDung\AdminTaoNguoiDungRequest;
use App\Models\NguoiDung;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminNguoiDungController - Quản lý người dùng (CRUD đầy đủ)
 *
 * Vai trò được phép: Admin (vai_tro = 2)
 *
 * Routes:
 *   GET    /api/admin/nguoi-dungs              - Danh sách (có lọc + tìm kiếm + phân trang)
 *   POST   /api/admin/nguoi-dungs              - Tạo mới
 *   GET    /api/admin/nguoi-dungs/{id}         - Chi tiết
 *   PUT    /api/admin/nguoi-dungs/{id}         - Cập nhật
 *   DELETE /api/admin/nguoi-dungs/{id}         - Xoá
 *   PATCH  /api/admin/nguoi-dungs/{id}/khoa    - Khoá/mở khoá
 *   GET    /api/admin/nguoi-dungs/thong-ke     - Thống kê
 */
class AdminNguoiDungController extends Controller
{
    private function generalUserQuery()
    {
        return NguoiDung::query()->where('vai_tro', '!=', NguoiDung::VAI_TRO_ADMIN);
    }

    private function userAuditSnapshot(NguoiDung $nguoiDung): array
    {
        return $nguoiDung->only([
            'id',
            'ho_ten',
            'email',
            'so_dien_thoai',
            'vai_tro',
            'cap_admin',
            'trang_thai',
        ]);
    }

    private function adminAccountManagedSeparatelyResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Tài khoản admin phải được quản lý trong module Quản lý admin riêng.',
        ], 422);
    }

    /**
     * GET /api/admin/nguoi-dungs
     * Danh sách người dùng với bộ lọc, tìm kiếm, phân trang.
     *
     * Query params:
     *   ?vai_tro=0|1|2          Lọc theo vai trò
     *   ?trang_thai=0|1         Lọc theo trạng thái
     *   ?search=keyword         Tìm theo tên/email/SĐT
     *   ?sort_by=created_at     Sắp xếp theo trường
     *   ?sort_dir=asc|desc      Chiều sắp xếp
     *   ?per_page=15            Số bản ghi mỗi trang
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->generalUserQuery();

        if ($request->filled('vai_tro')) {
            $query->where('vai_tro', $request->vai_tro);
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ho_ten', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'ho_ten', 'email', 'vai_tro', 'trang_thai', 'created_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts)
            ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->get('per_page', 15), 100);
        $nguoiDungs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $nguoiDungs,
        ]);
    }

    /**
     * POST /api/admin/nguoi-dungs
     * Admin tạo tài khoản mới (tất cả vai trò).
     */
    public function store(AdminTaoNguoiDungRequest $request): JsonResponse
    {
        $payload = $request->validated();

        if ((int) ($payload['vai_tro'] ?? NguoiDung::VAI_TRO_UNG_VIEN) === NguoiDung::VAI_TRO_ADMIN) {
            return $this->adminAccountManagedSeparatelyResponse();
        }

        $payload['cap_admin'] = null;
        $nguoiDung = NguoiDung::create($payload);
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_user_created',
            description: "Admin tạo tài khoản {$nguoiDung->email}.",
            target: $nguoiDung,
            after: $this->userAuditSnapshot($nguoiDung),
            metadata: ['scope' => 'admin_user'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo tài khoản thành công.',
            'data' => $nguoiDung,
        ], 201);
    }

    /**
     * GET /api/admin/nguoi-dungs/{id}
     * Chi tiết một người dùng.
     */
    public function show(int $id): JsonResponse
    {
        $nguoiDung = NguoiDung::findOrFail($id);

        if ($nguoiDung->isAdmin()) {
            return $this->adminAccountManagedSeparatelyResponse();
        }

        return response()->json([
            'success' => true,
            'data' => $nguoiDung,
        ]);
    }

    /**
     * PUT /api/admin/nguoi-dungs/{id}
     * Admin cập nhật thông tin người dùng.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $nguoiDung = NguoiDung::findOrFail($id);

        if ($nguoiDung->isAdmin()) {
            return $this->adminAccountManagedSeparatelyResponse();
        }

        $validated = $request->validate([
            'ho_ten' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:150', "unique:nguoi_dungs,email,{$id}"],
            'mat_khau' => ['sometimes', 'string', 'min:6'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'ngay_sinh' => ['nullable', 'date'],
            'gioi_tinh' => ['nullable', 'in:nam,nu,khac'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'vai_tro' => ['sometimes', 'integer', 'in:0,1,2'],
            'trang_thai' => ['sometimes', 'integer', 'in:0,1'],
        ]);

        if ((int) ($validated['vai_tro'] ?? $nguoiDung->vai_tro) === NguoiDung::VAI_TRO_ADMIN) {
            return $this->adminAccountManagedSeparatelyResponse();
        }

        $validated['cap_admin'] = null;

        // Không cần hash thủ công — Model đã có cast 'mat_khau' => 'hashed'
        // nên Laravel tự động hash khi gán giá trị qua update()

        $before = $this->userAuditSnapshot($nguoiDung);
        $nguoiDung->update($validated);
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_user_updated',
            description: "Admin cập nhật tài khoản {$nguoiDung->email}.",
            target: $nguoiDung,
            before: $before,
            after: $this->userAuditSnapshot($nguoiDung->fresh()),
            metadata: ['scope' => 'admin_user'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công.',
            'data' => $nguoiDung->fresh(),
        ]);
    }

    /**
     * DELETE /api/admin/nguoi-dungs/{id}
     * Admin xoá tài khoản.
     */
    public function destroy(int $id): JsonResponse
    {
        $nguoiDung = NguoiDung::findOrFail($id);

        if ($nguoiDung->isAdmin()) {
            return $this->adminAccountManagedSeparatelyResponse();
        }

        if ($nguoiDung->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xoá tài khoản đang đăng nhập.',
            ], 422);
        }

        $before = $this->userAuditSnapshot($nguoiDung);
        // Thu hồi token trước khi xoá
        $nguoiDung->tokens()->delete();
        $nguoiDung->delete();
        app(AuditLogService::class)->logModelAction(
            actor: auth()->user(),
            action: 'admin_user_deleted',
            description: "Admin xóa tài khoản {$before['email']}.",
            target: $nguoiDung,
            before: $before,
            metadata: ['scope' => 'admin_user'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Xoá tài khoản thành công.',
        ]);
    }

    /**
     * PATCH /api/admin/nguoi-dungs/{id}/khoa
     * Admin khoá hoặc mở khoá tài khoản.
     */
    public function khoaTaiKhoan(int $id): JsonResponse
    {
        $nguoiDung = NguoiDung::findOrFail($id);

        if ($nguoiDung->isAdmin()) {
            return $this->adminAccountManagedSeparatelyResponse();
        }

        if ($nguoiDung->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khoá tài khoản đang đăng nhập.',
            ], 422);
        }

        $before = $this->userAuditSnapshot($nguoiDung);
        $nguoiDung->trang_thai = $nguoiDung->trang_thai ? 0 : 1;
        $nguoiDung->save();

        // Thu hồi tất cả token nếu bị khoá
        if (!$nguoiDung->trang_thai) {
            $nguoiDung->tokens()->delete();
        }

        $action = $nguoiDung->trang_thai ? 'Mở khoá' : 'Khoá';
        app(AuditLogService::class)->logModelAction(
            actor: auth()->user(),
            action: $nguoiDung->trang_thai ? 'admin_user_unlocked' : 'admin_user_locked',
            description: "Admin {$action} tài khoản {$nguoiDung->email}.",
            target: $nguoiDung,
            before: $before,
            after: $this->userAuditSnapshot($nguoiDung),
            metadata: ['scope' => 'admin_user'],
        );

        return response()->json([
            'success' => true,
            'message' => "{$action} tài khoản thành công.",
            'data' => $nguoiDung,
        ]);
    }

    /**
     * GET /api/admin/nguoi-dungs/thong-ke
     * Thống kê số lượng người dùng theo vai trò và trạng thái.
     */
    public function thongKe(): JsonResponse
    {
        $query = $this->generalUserQuery();

        $thongKe = [
            'tong' => (clone $query)->count(),
            'admin' => 0,
            'nha_tuyen_dung' => (clone $query)->where('vai_tro', NguoiDung::VAI_TRO_NHA_TUYEN_DUNG)->count(),
            'ung_vien' => (clone $query)->where('vai_tro', NguoiDung::VAI_TRO_UNG_VIEN)->count(),
            'dang_hoat_dong' => (clone $query)->where('trang_thai', 1)->count(),
            'bi_khoa' => (clone $query)->where('trang_thai', 0)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $thongKe,
        ]);
    }
}
