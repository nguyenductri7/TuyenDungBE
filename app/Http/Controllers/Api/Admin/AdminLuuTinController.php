<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TinTuyenDung;
use Illuminate\Http\JsonResponse;

class AdminLuuTinController extends Controller
{
    /**
     * Thống kê xem tin tuyển dụng nào được lưu nhiều nhất hệ thống
     */
    public function topLuuTin(): JsonResponse
    {
        $tinNoiBat = TinTuyenDung::withCount('nguoiDungLuus')
            ->having('nguoi_dung_luus_count', '>', 0)
            ->orderBy('nguoi_dung_luus_count', 'desc')
            ->take(10)
            ->get(['id', 'tieu_de', 'cong_ty_id'])
            ->map(function ($tin) {
                // Xoá bớt field để tránh lộ data dư
                $tin->load('congTy:id,ten_cong_ty');
                return $tin;
            });

        return response()->json([
            'success' => true,
            'data' => $tinNoiBat
        ]);
    }
}
