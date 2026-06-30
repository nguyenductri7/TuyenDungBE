<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KetQuaMatching;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UngVienKetQuaMatchingController extends Controller
{
    /**
     * Xem danh sách các công việc được Hệ thống AI gợi ý cho Ứng viên
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        // Ứng viên chỉ xem được các kết quả so khớp liên quan đến Hồ sơ (CV) của MÌNH
        $query = KetQuaMatching::whereHas('hoSo', function ($q) use ($userId) {
            $q->where('nguoi_dung_id', $userId);
        })
        ->with([
            'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,hinh_thuc_lam_viec,trang_thai',
            'tinTuyenDung.congTy:id,ten_cong_ty,logo',
            'hoSo:id,tieu_de_ho_so' // Chỉ lấy tiêu đề CV để ứng viên xem "công việc này hợp với CV nào của mình"
        ]);

        // Lọc theo CV cụ thể nếu ứng viên chọn (vd muốn xem AI gợi ý việc làm cho CV Backend)
        if ($request->has('ho_so_id') && $request->ho_so_id !== '') {
            $query->where('ho_so_id', $request->ho_so_id);
        }

        // Ưu tiên điểm cao nhất lên đầu, sau đó mới tới bài mới nhất
        $query->orderBy('diem_phu_hop', 'desc');
        $query->orderBy('thoi_gian_match', 'desc');

        // Chỉ hiển thị những tin đang Open (trang_thai = 1)
        $query->whereHas('tinTuyenDung', function ($q) {
            $q->where('trang_thai', 1)->where(function ($subQ) {
                $subQ->whereNull('ngay_het_han')->orWhere('ngay_het_han', '>=', now());
            });
        });

        $ketQuas = $query->paginate((int) $request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu Việc làm Gợi ý bởi AI thành công.',
            'data' => $ketQuas
        ]);
    }
}
