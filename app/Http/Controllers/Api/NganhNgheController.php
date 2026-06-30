<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NganhNghe;
use App\Support\EncodedId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NganhNgheController - Xem danh mục ngành nghề (Public / Auth)
 *
 * Tất cả vai trò đều có quyền xem danh mục ngành nghề hiển thị.
 * Dùng cho: Ứng viên chọn ngành khi tạo hồ sơ, NTD chọn ngành khi đăng tin.
 *
 * Routes:
 *   GET  /api/v1/nganh-nghes                - Danh sách ngành nghề hiển thị
 *   GET  /api/v1/nganh-nghes/cay            - Danh sách dạng cây (cha-con)
 *   GET  /api/v1/nganh-nghes/{id}           - Chi tiết ngành nghề
 */
class NganhNgheController extends Controller
{
    /**
     * GET /api/v1/nganh-nghes
     * Danh sách ngành nghề hiển thị (flat list).
     *
     * Query params:
     *   ?search=keyword         Tìm theo tên ngành
     *   ?danh_muc_cha_id=1      Lọc ngành con theo cha
     *   ?goc=1                  Chỉ lấy ngành gốc (không có cha)
     *   ?per_page=20            Số bản ghi/trang (0 = tất cả, không phân trang)
     */
    public function index(Request $request): JsonResponse
    {
        $query = NganhNghe::where('trang_thai', NganhNghe::TRANG_THAI_HIEN_THI);

        // Tìm kiếm
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ten_nganh', 'like', "%{$search}%")
                    ->orWhere('mo_ta', 'like', "%{$search}%");
            });
        }

        // Lọc ngành con theo cha
        if ($request->filled('danh_muc_cha_id')) {
            $query->where('danh_muc_cha_id', $request->danh_muc_cha_id);
        }

        // Chỉ ngành gốc
        if ($request->filled('goc') && $request->goc == '1') {
            $query->whereNull('danh_muc_cha_id');
        }

        $query->orderBy('ten_nganh', 'asc');

        // per_page = 0 → lấy tất cả (không phân trang)
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
     * GET /api/v1/nganh-nghes/cay
     * Danh sách ngành nghề dạng cây phân cấp (tree).
     * Chỉ lấy ngành gốc, kèm danh mục con đệ quy.
     */
    public function cay(): JsonResponse
    {
        $cay = NganhNghe::where('trang_thai', NganhNghe::TRANG_THAI_HIEN_THI)
            ->whereNull('danh_muc_cha_id')
            ->with([
                'danhMucCon' => function ($q) {
                    $q->where('trang_thai', NganhNghe::TRANG_THAI_HIEN_THI)
                        ->orderBy('ten_nganh');
                }
            ])
            ->orderBy('ten_nganh')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cay,
        ]);
    }

    /**
     * GET /api/v1/nganh-nghes/{id}
     * Chi tiết ngành nghề.
     */
    public function show(string $id): JsonResponse
    {
        $decodedId = EncodedId::decodeOrFail($id);
        $nganhNghe = NganhNghe::where('trang_thai', NganhNghe::TRANG_THAI_HIEN_THI)
            ->with([
                'danhMucCha:id,ten_nganh,slug',
                'danhMucCon' => function ($q) {
                    $q->where('trang_thai', NganhNghe::TRANG_THAI_HIEN_THI)
                        ->select('id', 'ten_nganh', 'slug', 'icon', 'danh_muc_cha_id');
                },
            ])
            ->findOrFail($decodedId);

        return response()->json([
            'success' => true,
            'data' => $nganhNghe,
        ]);
    }
}
