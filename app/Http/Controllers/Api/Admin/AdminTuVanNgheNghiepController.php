<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TuVanNgheNghiep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTuVanNgheNghiepController extends Controller
{
    /**
     * Admin/Kỹ sư xem lịch sử toàn bộ các Phân tích Nghề nghiệp (AI) đã cấp cho ứng viên.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TuVanNgheNghiep::with([
            'nguoiDung:id,ho_ten,email',
            'hoSo:id,tieu_de_ho_so'
        ]);

        // Lọc theo keyword "Tên Nghề Đề Xuất" (VD: xem AI khuyên bao nhiêu người làm Frontend)
        if ($request->has('nghe_de_xuat') && $request->nghe_de_xuat !== '') {
            $query->where('nghe_de_xuat', 'like', '%' . $request->nghe_de_xuat . '%');
        }

        // Lọc theo độ tự tin (Confidence score) của AI
        if ($request->has('min_score')) {
            $query->where('muc_do_phu_hop', '>=', (float)$request->min_score);
        }

        $query->orderBy('created_at', 'desc');

        $tuVans = $query->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $tuVans
        ]);
    }
    
    /**
     * Thống kê xem Ngành nào đang được AI gợi ý nhiều nhất
     */
    public function thongKe(Request $request): JsonResponse
    {
        $statistics = TuVanNgheNghiep::select('nghe_de_xuat')
            ->selectRaw('COUNT(*) as total_suggestions')
            ->selectRaw('AVG(muc_do_phu_hop) as average_confidence')
            ->groupBy('nghe_de_xuat')
            ->orderByDesc('total_suggestions')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
