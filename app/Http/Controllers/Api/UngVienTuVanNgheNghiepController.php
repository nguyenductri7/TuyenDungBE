<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TuVanNgheNghiep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UngVienTuVanNgheNghiepController extends Controller
{
    /**
     * Ứng viên xem danh sách các báo cáo Tư vấn định hướng do AI phân tích.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $query = TuVanNgheNghiep::where('nguoi_dung_id', $userId)
            ->with(['hoSo:id,tieu_de_ho_so']); // Lấy thêm tiêu đề hồ sơ (nếu có)

        // Nếu ứng viên muốn xem lời khuyên dành riêng cho 1 CV nào đó
        if ($request->has('ho_so_id') && $request->ho_so_id !== '') {
            $query->where('ho_so_id', $request->ho_so_id);
        }

        // Ưu tiên xem các báo cáo có độ phù hợp cao nhất trước
        $query->orderBy('muc_do_phu_hop', 'desc');
        $query->orderBy('created_at', 'desc');

        $tuVans = $query->paginate((int) $request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Lấy Báo cáo Tư Vấn Nghề Nghiệp (AI) thành công.',
            'data' => $tuVans
        ]);
    }
}
