<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NganhNghe\TaoNganhNgheRequest;
use App\Http\Requests\NganhNghe\CapNhatNganhNgheRequest;
use App\Models\NganhNghe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminNganhNgheController - Admin CRUD ngành nghề
 *
 * Vai trò: Admin (vai_tro = 2)
 *
 * Routes:
 *   GET    /api/v1/admin/nganh-nghes              - Danh sách (có lọc/tìm kiếm/phân trang)
 *   GET    /api/v1/admin/nganh-nghes/thong-ke     - Thống kê
 *   POST   /api/v1/admin/nganh-nghes              - Tạo mới
 *   GET    /api/v1/admin/nganh-nghes/{id}         - Chi tiết
 *   PUT    /api/v1/admin/nganh-nghes/{id}         - Cập nhật
 *   PATCH  /api/v1/admin/nganh-nghes/{id}/trang-thai - Đổi trạng thái
 *   DELETE /api/v1/admin/nganh-nghes/{id}         - Xoá
 */
class AdminNganhNgheController extends Controller
{
    /**
     * GET /api/v1/admin/nganh-nghes
     * Danh sách tất cả ngành nghề (kể cả ẩn).
     */
    public function index(Request $request): JsonResponse
    {
        $query = NganhNghe::with('danhMucCha:id,ten_nganh');

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('danh_muc_cha_id')) {
            if ($request->danh_muc_cha_id === 'null' || $request->danh_muc_cha_id === '0') {
                $query->whereNull('danh_muc_cha_id');
            } else {
                $query->where('danh_muc_cha_id', $request->danh_muc_cha_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ten_nganh', 'like', "%{$search}%")
                    ->orWhere('mo_ta', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'ten_nganh', 'slug', 'trang_thai', 'created_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts)
            ? $request->get('sort_by') : 'ten_nganh';
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
     * POST /api/v1/admin/nganh-nghes
     * Admin tạo ngành nghề mới.
     */
    public function store(TaoNganhNgheRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Tự động tạo slug
        $data['slug'] = NganhNghe::taoSlug($data['ten_nganh']);

        $nganhNghe = NganhNghe::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo ngành nghề thành công.',
            'data' => $nganhNghe->load('danhMucCha:id,ten_nganh'),
        ], 201);
    }

    /**
     * GET /api/v1/admin/nganh-nghes/{id}
     * Chi tiết ngành nghề.
     */
    public function show(int $id): JsonResponse
    {
        $nganhNghe = NganhNghe::with([
            'danhMucCha:id,ten_nganh,slug',
            'danhMucCon:id,ten_nganh,slug,icon,trang_thai,danh_muc_cha_id',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $nganhNghe,
        ]);
    }

    /**
     * PUT /api/v1/admin/nganh-nghes/{id}
     * Admin cập nhật ngành nghề.
     */
    public function update(CapNhatNganhNgheRequest $request, int $id): JsonResponse
    {
        $nganhNghe = NganhNghe::findOrFail($id);
        $data = $request->validated();

        // Không cho phép đặt cha là chính nó
        if (isset($data['danh_muc_cha_id']) && $data['danh_muc_cha_id'] == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể đặt ngành nghề làm danh mục cha của chính nó.',
            ], 422);
        }

        // Cập nhật slug nếu đổi tên
        if (isset($data['ten_nganh']) && $data['ten_nganh'] !== $nganhNghe->ten_nganh) {
            $data['slug'] = NganhNghe::taoSlug($data['ten_nganh'], $id);
        }

        $nganhNghe->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ngành nghề thành công.',
            'data' => $nganhNghe->fresh()->load('danhMucCha:id,ten_nganh'),
        ]);
    }

    /**
     * DELETE /api/v1/admin/nganh-nghes/{id}
     * Xoá ngành nghề.
     *
     * Lưu ý: Nếu ngành nghề có danh mục con, cần xác nhận.
     */
    public function destroy(int $id): JsonResponse
    {
        $nganhNghe = NganhNghe::findOrFail($id);

        // Kiểm tra có danh mục con không
        $soCon = $nganhNghe->danhMucCon()->count();
        if ($soCon > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xoá. Ngành nghề này có {$soCon} danh mục con. Hãy xoá hoặc chuyển danh mục con trước.",
            ], 422);
        }

        $nganhNghe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoá ngành nghề thành công.',
        ]);
    }

    /**
     * PATCH /api/v1/admin/nganh-nghes/{id}/trang-thai
     * Toggle trạng thái (hiển thị/ẩn).
     */
    public function doiTrangThai(int $id): JsonResponse
    {
        $nganhNghe = NganhNghe::findOrFail($id);

        $nganhNghe->trang_thai = $nganhNghe->trang_thai ? 0 : 1;
        $nganhNghe->save();

        $action = $nganhNghe->trang_thai ? 'Hiển thị' : 'Ẩn';

        return response()->json([
            'success' => true,
            'message' => "{$action} ngành nghề thành công.",
            'data' => $nganhNghe,
        ]);
    }

    /**
     * GET /api/v1/admin/nganh-nghes/thong-ke
     * Thống kê ngành nghề.
     */
    public function thongKe(): JsonResponse
    {
        $thongKe = [
            'tong' => NganhNghe::count(),
            'hien_thi' => NganhNghe::where('trang_thai', NganhNghe::TRANG_THAI_HIEN_THI)->count(),
            'an' => NganhNghe::where('trang_thai', NganhNghe::TRANG_THAI_AN)->count(),
            'nganh_goc' => NganhNghe::whereNull('danh_muc_cha_id')->count(),
            'nganh_con' => NganhNghe::whereNotNull('danh_muc_cha_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $thongKe,
        ]);
    }
}
