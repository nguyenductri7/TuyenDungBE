<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UngTuyen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUngTuyenController extends Controller
{
    /**
     * Tra cứu thống kê ứng tuyển toàn bộ hệ thống
     */
    public function thongKe(): JsonResponse
    {
        $tongSo = UngTuyen::count();
        $choDuyet = UngTuyen::where('trang_thai', UngTuyen::TRANG_THAI_CHO_DUYET)->count();
        $daXem = UngTuyen::where('trang_thai', UngTuyen::TRANG_THAI_DA_XEM)->count();
        $daHenPhongVan = UngTuyen::where('trang_thai', UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN)->count();
        $quaPhongVan = UngTuyen::where('trang_thai', UngTuyen::TRANG_THAI_QUA_PHONG_VAN)->count();
        $trungTuyen = UngTuyen::where('trang_thai', UngTuyen::TRANG_THAI_TRUNG_TUYEN)->count();
        $tuChoi = UngTuyen::where('trang_thai', UngTuyen::TRANG_THAI_TU_CHOI)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'tong_don_ung_tuyen' => $tongSo,
                'chi_tiet' => [
                    'cho_duyet' => $choDuyet,
                    'da_xem' => $daXem,
                    'da_hen_phong_van' => $daHenPhongVan,
                    'qua_phong_van' => $quaPhongVan,
                    'trung_tuyen' => $trungTuyen,
                    'chap_nhan' => $trungTuyen,
                    'tu_choi' => $tuChoi
                ]
            ]
        ]);
    }

    /**
     * Xem danh sách tất cả ứng tuyển trên toàn hệ thống 
     */
    public function index(Request $request): JsonResponse
    {
        $query = UngTuyen::with([
            'tinTuyenDung:id,cong_ty_id,tieu_de,trang_thai',
            'tinTuyenDung.congTy:id,ten_cong_ty',
            'hoSo' => function ($q) {
                // Lấy cả user để Admin tiện tra cứu email
                $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so')->with('nguoiDung:id,email');
            }
        ]);

        if ($request->has('trang_thai') && $request->trang_thai !== '') {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->has('cong_ty_id') && $request->cong_ty_id !== '') {
            $congTyId = $request->cong_ty_id;
            $query->whereHas('tinTuyenDung', function ($q) use ($congTyId) {
                $q->where('cong_ty_id', $congTyId);
            });
        }

        $query->orderBy('thoi_gian_ung_tuyen', 'desc');

        $ungTuyens = $query->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $ungTuyens
        ]);
    }
}
