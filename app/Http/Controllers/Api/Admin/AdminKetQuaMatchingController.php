<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KetQuaMatching;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminKetQuaMatchingController extends Controller
{
    /**
     * Quản trị viên theo dõi lịch sử và hiệu suất của Thuật toán AI Matching
     */
    public function index(Request $request): JsonResponse
    {
        $query = KetQuaMatching::with([
            'hoSo:id,nguoi_dung_id,tieu_de_ho_so',
            'hoSo.nguoiDung:id,email',
            'tinTuyenDung:id,tieu_de,cong_ty_id',
            'tinTuyenDung.congTy:id,ten_cong_ty'
        ]);

        // Lọc theo Version thuật toán
        if ($request->has('model_version') && $request->model_version !== '') {
            $query->where('model_version', $request->model_version);
        }

        // Lọc theo ngưỡng điểm (Xem các Match xuất sắc > 90 hoặc Match kém < 40)
        if ($request->has('min_score')) {
            $query->where('diem_phu_hop', '>=', (float)$request->min_score);
        }
        if ($request->has('max_score')) {
            $query->where('diem_phu_hop', '<=', (float)$request->max_score);
        }

        $query->orderBy('thoi_gian_match', 'desc');

        $ketQuas = $query->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $ketQuas
        ]);
    }
    
    /**
     * Thống kê phiên bản Models
     */
    public function thongKe(Request $request): JsonResponse
    {
        // Thống kê lượng record sinh ra theo từng version thuật toán
        $statistics = KetQuaMatching::select('model_version')
            ->selectRaw('COUNT(*) as total_matches')
            ->selectRaw('AVG(diem_phu_hop) as average_score')
            ->selectRaw('MAX(diem_phu_hop) as max_score')
            ->selectRaw('MIN(diem_phu_hop) as min_score')
            ->groupBy('model_version')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
