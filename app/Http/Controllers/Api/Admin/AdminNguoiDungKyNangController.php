<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NguoiDungKyNang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminNguoiDungKyNangController - Admin xem kỹ năng người dùng
 *
 * Vai trò: Admin (vai_tro = 2)
 *
 * Routes:
 *   GET  /api/v1/admin/nguoi-dung-ky-nangs              - Danh sách tất cả
 *   GET  /api/v1/admin/nguoi-dung-ky-nangs/thong-ke     - Thống kê
 *   GET  /api/v1/admin/nguoi-dung-ky-nangs/nguoi-dung/{nguoiDungId}  - Kỹ năng của 1 user
 */
class AdminNguoiDungKyNangController extends Controller
{
    /**
     * GET /api/v1/admin/nguoi-dung-ky-nangs
     * Danh sách tất cả bản ghi người dùng — kỹ năng.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NguoiDungKyNang::with([
            'nguoiDung:id,ho_ten,email',
            'kyNang:id,ten_ky_nang',
        ]);

        if ($request->filled('nguoi_dung_id')) {
            $query->where('nguoi_dung_id', $request->nguoi_dung_id);
        }

        if ($request->filled('ky_nang_id')) {
            $query->where('ky_nang_id', $request->ky_nang_id);
        }

        if ($request->filled('muc_do')) {
            $query->where('muc_do', $request->muc_do);
        }

        $query->orderBy('created_at', 'desc');

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
     * GET /api/v1/admin/nguoi-dung-ky-nangs/nguoi-dung/{nguoiDungId}
     * Xem kỹ năng của 1 người dùng cụ thể.
     */
    public function kyNangCuaNguoiDung(int $nguoiDungId): JsonResponse
    {
        $kyNangs = NguoiDungKyNang::with('kyNang:id,ten_ky_nang,icon')
            ->where('nguoi_dung_id', $nguoiDungId)
            ->orderBy('muc_do', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $kyNangs,
        ]);
    }

    /**
     * GET /api/v1/admin/nguoi-dung-ky-nangs/thong-ke
     * Thống kê.
     */
    public function thongKe(): JsonResponse
    {
        $thongKe = [
            'tong_ban_ghi' => NguoiDungKyNang::count(),
            'so_nguoi_co_ky_nang' => NguoiDungKyNang::distinct('nguoi_dung_id')->count('nguoi_dung_id'),
            'theo_muc_do' => [
                'co_ban' => NguoiDungKyNang::where('muc_do', 1)->count(),
                'trung_binh' => NguoiDungKyNang::where('muc_do', 2)->count(),
                'kha' => NguoiDungKyNang::where('muc_do', 3)->count(),
                'gioi' => NguoiDungKyNang::where('muc_do', 4)->count(),
                'chuyen_gia' => NguoiDungKyNang::where('muc_do', 5)->count(),
            ],
            'top_ky_nang' => NguoiDungKyNang::select('ky_nang_id')
                ->selectRaw('COUNT(*) as so_nguoi')
                ->with('kyNang:id,ten_ky_nang')
                ->groupBy('ky_nang_id')
                ->orderByDesc('so_nguoi')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $thongKe,
        ]);
    }
}
