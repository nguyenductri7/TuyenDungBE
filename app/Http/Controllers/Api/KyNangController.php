<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KyNang;
use App\Support\EncodedId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KyNangController - Xem danh mục kỹ năng (Public)
 *
 * Không yêu cầu đăng nhập. Dùng cho:
 *   - Ứng viên chọn kỹ năng khi tạo hồ sơ
 *   - NTD lọc ứng viên theo kỹ năng
 *
 * Routes:
 *   GET  /api/v1/ky-nangs          - Danh sách kỹ năng
 *   GET  /api/v1/ky-nangs/{id}     - Chi tiết kỹ năng
 */
class KyNangController extends Controller
{
    /**
     * GET /api/v1/ky-nangs
     * Danh sách kỹ năng.
     *
     * Query params:
     *   ?search=keyword    Tìm theo tên kỹ năng
     *   ?per_page=0        Số bản ghi/trang (0 = tất cả)
     */
    public function index(Request $request): JsonResponse
    {
        $query = KyNang::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('ten_ky_nang', 'like', "%{$search}%");
        }

        $query->orderBy('ten_ky_nang', 'asc');

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
     * GET /api/v1/ky-nangs/{id}
     * Chi tiết kỹ năng.
     */
    public function show(string $id): JsonResponse
    {
        $kyNang = KyNang::findOrFail(EncodedId::decodeOrFail($id));

        return response()->json([
            'success' => true,
            'data' => $kyNang,
        ]);
    }
}
