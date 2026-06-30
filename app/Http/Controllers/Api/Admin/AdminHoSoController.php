<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoSo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminHoSoController - Quản lý hồ sơ ứng viên (Admin)
 *
 * Vai trò được phép: Admin (vai_tro = 2)
 *
 * Routes:
 *   GET    /api/v1/admin/ho-sos                    - Danh sách (có lọc + tìm kiếm + phân trang)
 *   GET    /api/v1/admin/ho-sos/thong-ke           - Thống kê
 *   GET    /api/v1/admin/ho-sos/da-xoa             - Danh sách hồ sơ lưu trữ
 *   GET    /api/v1/admin/ho-sos/{id}               - Chi tiết
 *   PATCH  /api/v1/admin/ho-sos/{id}/trang-thai    - Đổi trạng thái (công khai/ẩn)
 *   DELETE /api/v1/admin/ho-sos/{id}               - Lưu trữ hồ sơ (soft delete)
 *   PATCH  /api/v1/admin/ho-sos/{id}/khoi-phuc     - Khôi phục hồ sơ đã lưu trữ
 *   DELETE /api/v1/admin/ho-sos/{id}/xoa-vinh-vien - Xóa vĩnh viễn hồ sơ đã lưu trữ
 */
class AdminHoSoController extends Controller
{
    /**
     * GET /api/v1/admin/ho-sos
     * Danh sách hồ sơ với bộ lọc, tìm kiếm, phân trang.
     *
     * Query params:
     *   ?nguoi_dung_id=1       Lọc theo người dùng
     *   ?trang_thai=0|1        Lọc theo trạng thái
     *   ?trinh_do=Đại học      Lọc theo trình độ
     *   ?search=keyword        Tìm theo tiêu đề/mục tiêu
     *   ?sort_by=created_at    Sắp xếp theo trường
     *   ?sort_dir=asc|desc     Chiều sắp xếp
     *   ?per_page=15           Số bản ghi mỗi trang
     */
    public function index(Request $request): JsonResponse
    {
        $query = HoSo::with('nguoiDung:id,ho_ten,email');

        if ($request->filled('nguoi_dung_id')) {
            $query->where('nguoi_dung_id', $request->nguoi_dung_id);
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('trinh_do')) {
            $query->whereIn('trinh_do', HoSo::trinhDoQueryValues($request->trinh_do));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tieu_de_ho_so', 'like', "%{$search}%")
                    ->orWhere('muc_tieu_nghe_nghiep', 'like', "%{$search}%")
                    ->orWhere('mo_ta_ban_than', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'tieu_de_ho_so', 'trinh_do', 'kinh_nghiem_nam', 'trang_thai', 'created_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts)
            ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->get('per_page', 15), 100);
        $hoSos = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $hoSos,
        ]);
    }

    /**
     * GET /api/v1/admin/ho-sos/{id}
     * Chi tiết một hồ sơ.
     */
    public function show(int $id): JsonResponse
    {
        $hoSo = HoSo::withTrashed()
            ->with('nguoiDung:id,ho_ten,email,so_dien_thoai')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $hoSo,
        ]);
    }

    /**
     * DELETE /api/v1/admin/ho-sos/{id}
     * Admin lưu trữ hồ sơ (soft delete).
     * Hồ sơ không bị xoá khỏi database, chỉ đánh dấu deleted_at.
     */
    public function destroy(int $id): JsonResponse
    {
        $hoSo = HoSo::findOrFail($id);

        $hoSo->delete(); // Soft delete — chỉ set deleted_at

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu trữ hồ sơ. Có thể khôi phục sau.',
        ]);
    }

    /**
     * PATCH /api/v1/admin/ho-sos/{id}/khoi-phuc
     * Admin khôi phục hồ sơ đã lưu trữ.
     */
    public function khoiPhuc(int $id): JsonResponse
    {
        $hoSo = HoSo::onlyTrashed()->findOrFail($id);

        $hoSo->restore();

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục hồ sơ thành công.',
            'data' => $hoSo,
        ]);
    }

    /**
     * DELETE /api/v1/admin/ho-sos/{id}/xoa-vinh-vien
     * Xóa vĩnh viễn hồ sơ đã lưu trữ.
     */
    public function xoaVinhVien(int $id): JsonResponse
    {
        $hoSo = HoSo::onlyTrashed()->findOrFail($id);

        $hoSo->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn hồ sơ.',
        ]);
    }

    /**
     * GET /api/v1/admin/ho-sos/da-xoa
     * Danh sách hồ sơ đang lưu trữ.
     */
    public function danhSachDaXoa(Request $request): JsonResponse
    {
        $query = HoSo::onlyTrashed()->with('nguoiDung:id,ho_ten,email');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tieu_de_ho_so', 'like', "%{$search}%")
                    ->orWhere('muc_tieu_nghe_nghiep', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $hoSos = $query->orderBy('deleted_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $hoSos,
        ]);
    }

    /**
     * PATCH /api/v1/admin/ho-sos/{id}/trang-thai
     * Admin đổi trạng thái hồ sơ (công khai/ẩn).
     */
    public function doiTrangThai(int $id): JsonResponse
    {
        $hoSo = HoSo::findOrFail($id);

        $hoSo->trang_thai = $hoSo->trang_thai ? 0 : 1;
        $hoSo->save();

        $action = $hoSo->trang_thai ? 'Công khai' : 'Ẩn';

        return response()->json([
            'success' => true,
            'message' => "{$action} hồ sơ thành công.",
            'data' => $hoSo,
        ]);
    }

    /**
     * GET /api/v1/admin/ho-sos/thong-ke
     * Thống kê hồ sơ.
     */
    public function thongKe(): JsonResponse
    {
        $thongKe = [
            'tong' => HoSo::count(),
            'cong_khai' => HoSo::where('trang_thai', HoSo::TRANG_THAI_CONG_KHAI)->count(),
            'an' => HoSo::where('trang_thai', HoSo::TRANG_THAI_AN)->count(),
            'da_xoa_mem' => HoSo::onlyTrashed()->count(),
            'theo_trinh_do' => [
                'Trung học' => HoSo::whereIn('trinh_do', HoSo::trinhDoQueryValues('Trung học'))->count(),
                'Trung cấp' => HoSo::whereIn('trinh_do', HoSo::trinhDoQueryValues('Trung cấp'))->count(),
                'Cao đẳng' => HoSo::whereIn('trinh_do', HoSo::trinhDoQueryValues('Cao đẳng'))->count(),
                'Đại học' => HoSo::whereIn('trinh_do', HoSo::trinhDoQueryValues('Đại học'))->count(),
                'Thạc sĩ' => HoSo::whereIn('trinh_do', HoSo::trinhDoQueryValues('Thạc sĩ'))->count(),
                'Tiến sĩ' => HoSo::whereIn('trinh_do', HoSo::trinhDoQueryValues('Tiến sĩ'))->count(),
                'Khác' => HoSo::whereIn('trinh_do', HoSo::trinhDoQueryValues('Khác'))->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $thongKe,
        ]);
    }
}
