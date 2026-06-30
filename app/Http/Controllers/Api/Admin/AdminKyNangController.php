<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KyNang\TaoKyNangRequest;
use App\Http\Requests\KyNang\CapNhatKyNangRequest;
use App\Models\KyNang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminKyNangController - Admin CRUD kỹ năng
 *
 * Vai trò: Admin (vai_tro = 2)
 *
 * Routes:
 *   GET    /api/v1/admin/ky-nangs             - Danh sách (có lọc/tìm kiếm/phân trang)
 *   GET    /api/v1/admin/ky-nangs/thong-ke    - Thống kê
 *   POST   /api/v1/admin/ky-nangs             - Tạo mới
 *   GET    /api/v1/admin/ky-nangs/{id}        - Chi tiết
 *   PUT    /api/v1/admin/ky-nangs/{id}        - Cập nhật
 *   DELETE /api/v1/admin/ky-nangs/{id}        - Xoá
 */
class AdminKyNangController extends Controller
{
    /**
     * GET /api/v1/admin/ky-nangs
     * Danh sách tất cả kỹ năng.
     *
     * Query params:
     *   ?search=keyword       Tìm theo tên
     *   ?sort_by=ten_ky_nang  Sắp xếp theo trường
     *   ?sort_dir=asc|desc    Chiều sắp xếp
     *   ?per_page=15          Số bản ghi/trang (0 = tất cả)
     */
    public function index(Request $request): JsonResponse
    {
        $query = KyNang::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ten_ky_nang', 'like', "%{$search}%")
                    ->orWhere('mo_ta', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'ten_ky_nang', 'created_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts)
            ? $request->get('sort_by') : 'ten_ky_nang';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) $request->get('per_page', 0);
        if ($perPage > 0) {
            $data = $query->paginate(min($perPage, 100));
        } else {
            $data = $query->get();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/v1/admin/ky-nangs
     * Admin tạo kỹ năng mới.
     */
    public function store(TaoKyNangRequest $request): JsonResponse
    {
        $kyNang = KyNang::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo kỹ năng thành công.',
            'data' => $kyNang,
        ], 201);
    }

    /**
     * GET /api/v1/admin/ky-nangs/{id}
     * Chi tiết kỹ năng.
     */
    public function show(int $id): JsonResponse
    {
        $kyNang = KyNang::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $kyNang,
        ]);
    }

    /**
     * PUT /api/v1/admin/ky-nangs/{id}
     * Admin cập nhật kỹ năng.
     */
    public function update(CapNhatKyNangRequest $request, int $id): JsonResponse
    {
        $kyNang = KyNang::findOrFail($id);

        $kyNang->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật kỹ năng thành công.',
            'data' => $kyNang->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/admin/ky-nangs/{id}
     * Xoá kỹ năng.
     */
    public function destroy(int $id): JsonResponse
    {
        $kyNang = KyNang::findOrFail($id);

        $kyNang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoá kỹ năng thành công.',
        ]);
    }

    /**
     * GET /api/v1/admin/ky-nangs/thong-ke
     * Thống kê kỹ năng.
     */
    public function thongKe(): JsonResponse
    {
        $thongKe = [
            'tong' => KyNang::count(),
            'co_mo_ta' => KyNang::whereNotNull('mo_ta')->where('mo_ta', '!=', '')->count(),
            'co_icon' => KyNang::whereNotNull('icon')->where('icon', '!=', '')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $thongKe,
        ]);
    }
}
