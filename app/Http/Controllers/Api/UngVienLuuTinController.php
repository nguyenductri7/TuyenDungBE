<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TinTuyenDung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UngVienLuuTinController - Ứng viên (vai_tro = 0)
 * 
 * Lưu tin và xem danh sách tin đã lưu.
 */
class UngVienLuuTinController extends Controller
{
    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Phiên đăng nhập không còn hợp lệ.',
        ], 401);
    }

    /**
     * Danh sách các tin tuyển dụng đã lưu
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }
        
        // Lấy tin đã lưu kèm thông tin hiển thị cơ bản
        $query = clone $user->tinDaLuus()
            ->with([
                'congTy:id,ten_cong_ty,ma_so_thue,logo,dia_chi',
                'nganhNghes:id,ten_nganh'
            ]);

        // Có thể sort theo thời gian LƯU tin (created_at của bảng pivot luu_tins)
        $query->orderBy('luu_tins.created_at', 'desc');

        $data = $query->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Bật / Tắt lưu 1 tin tuyển dụng (Toggle Action)
     * POST /api/v1/ung-vien/tin-da-luu/{tin_id}/toggle
     */
    public function toggle(int $tin_id): JsonResponse
    {
        // Kiểm tra xem tin có tồn tại không
        $tin = TinTuyenDung::findOrFail($tin_id);
        
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Hàm toggle() trả về array chứa 'attached' và 'detached' IDS
        $changes = $user->tinDaLuus()->toggle($tin_id);

        $daLuu = count($changes['attached']) > 0;

        return response()->json([
            'success' => true,
            'message' => $daLuu ? 'Đã lưu tin tuyển dụng' : 'Đã bỏ lưu tin tuyển dụng',
            'data' => [
                'tin_id' => $tin_id,
                'trang_thai_luu' => $daLuu
            ]
        ], $daLuu ? 201 : 200);
    }
}
