<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CongTy\TaoCongTyRequest;
use App\Http\Requests\CongTy\CapNhatCongTyRequest;
use App\Models\CongTy;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminCongTyController - Admin quản lý công ty
 *
 * Vai trò: Admin (vai_tro = 2)
 *
 * Routes:
 *   GET    /api/v1/admin/cong-tys             - Danh sách tất cả
 *   GET    /api/v1/admin/cong-tys/thong-ke    - Thống kê
 *   POST   /api/v1/admin/cong-tys             - Tạo mới
 *   GET    /api/v1/admin/cong-tys/{id}        - Chi tiết
 *   PUT    /api/v1/admin/cong-tys/{id}        - Cập nhật
 *   PATCH  /api/v1/admin/cong-tys/{id}/trang-thai - Đổi trạng thái
 *   DELETE /api/v1/admin/cong-tys/{id}        - Xoá
 */
class AdminCongTyController extends Controller
{
    private function companyAuditSnapshot(CongTy $congTy): array
    {
        return $congTy->only([
            'id',
            'nguoi_dung_id',
            'ten_cong_ty',
            'email',
            'so_dien_thoai',
            'dia_chi',
            'nganh_nghe_id',
            'quy_mo',
            'trang_thai',
        ]);
    }

    /**
     * GET /api/v1/admin/cong-tys
     * Danh sách tất cả công ty.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CongTy::with([
            'nguoiDung:id,ho_ten,email',
            'nganhNghe:id,ten_nganh',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ten_cong_ty', 'like', "%{$search}%")
                    ->orWhere('dia_chi', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('nganh_nghe_id')) {
            $query->where('nganh_nghe_id', $request->nganh_nghe_id);
        }

        if ($request->filled('quy_mo')) {
            $query->where('quy_mo', $request->quy_mo);
        }

        $allowedSorts = ['id', 'ten_cong_ty', 'created_at', 'trang_thai'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts)
            ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

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
     * POST /api/v1/admin/cong-tys
     */
    public function store(TaoCongTyRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (!isset($data['nguoi_dung_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cần chỉ định nguoi_dung_id (NTD sở hữu).',
            ], 422);
        }

        $congTy = CongTy::create($data);
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_company_created',
            description: "Admin tạo công ty {$congTy->ten_cong_ty}.",
            target: $congTy,
            company: $congTy,
            after: $this->companyAuditSnapshot($congTy),
            metadata: ['scope' => 'admin_company'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo công ty thành công.',
            'data' => $congTy,
        ], 201);
    }

    /**
     * GET /api/v1/admin/cong-tys/{id}
     */
    public function show(int $id): JsonResponse
    {
        $congTy = CongTy::with([
            'nguoiDung:id,ho_ten,email',
            'nganhNghe:id,ten_nganh',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $congTy,
        ]);
    }

    /**
     * PUT /api/v1/admin/cong-tys/{id}
     */
    public function update(CapNhatCongTyRequest $request, int $id): JsonResponse
    {
        $congTy = CongTy::findOrFail($id);
        $data = $request->validated();
        $before = $this->companyAuditSnapshot($congTy);

        $congTy->update($data);
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_company_updated',
            description: "Admin cập nhật công ty {$congTy->ten_cong_ty}.",
            target: $congTy,
            company: $congTy,
            before: $before,
            after: $this->companyAuditSnapshot($congTy->fresh()),
            metadata: ['scope' => 'admin_company'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật công ty thành công.',
            'data' => $congTy->fresh(),
        ]);
    }

    /**
     * PATCH /api/v1/admin/cong-tys/{id}/trang-thai
     * Đổi trạng thái (hoạt động ↔ tạm ngưng).
     */
    public function doiTrangThai(int $id): JsonResponse
    {
        $congTy = CongTy::findOrFail($id);
        $before = $this->companyAuditSnapshot($congTy);
        $congTy->trang_thai = $congTy->trang_thai === CongTy::TRANG_THAI_HOAT_DONG
            ? CongTy::TRANG_THAI_TAM_NGUNG
            : CongTy::TRANG_THAI_HOAT_DONG;
        $congTy->save();

        $trangThai = $congTy->trang_thai === CongTy::TRANG_THAI_HOAT_DONG ? 'hoạt động' : 'tạm ngưng';
        app(AuditLogService::class)->logModelAction(
            actor: auth()->user(),
            action: 'admin_company_status_toggled',
            description: "Admin chuyển trạng thái công ty {$congTy->ten_cong_ty} sang {$trangThai}.",
            target: $congTy,
            company: $congTy,
            before: $before,
            after: $this->companyAuditSnapshot($congTy),
            metadata: ['scope' => 'admin_company'],
        );

        return response()->json([
            'success' => true,
            'message' => "Đã chuyển trạng thái sang: {$trangThai}.",
            'data' => $congTy,
        ]);
    }

    /**
     * DELETE /api/v1/admin/cong-tys/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $congTy = CongTy::findOrFail($id);
        $before = $this->companyAuditSnapshot($congTy);
        $congTy->delete();
        app(AuditLogService::class)->logModelAction(
            actor: auth()->user(),
            action: 'admin_company_deleted',
            description: "Admin xóa công ty {$before['ten_cong_ty']}.",
            target: $congTy,
            before: $before,
            metadata: ['scope' => 'admin_company'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Xoá công ty thành công.',
        ]);
    }

    /**
     * GET /api/v1/admin/cong-tys/thong-ke
     */
    public function thongKe(): JsonResponse
    {
        $thongKe = [
            'tong' => CongTy::count(),
            'hoat_dong' => CongTy::where('trang_thai', CongTy::TRANG_THAI_HOAT_DONG)->count(),
            'tam_ngung' => CongTy::where('trang_thai', CongTy::TRANG_THAI_TAM_NGUNG)->count(),
            'theo_quy_mo' => [],
        ];

        foreach (CongTy::QUY_MO_LIST as $quyMo) {
            $thongKe['theo_quy_mo'][$quyMo] = CongTy::where('quy_mo', $quyMo)->count();
        }

        return response()->json([
            'success' => true,
            'data' => $thongKe,
        ]);
    }
}
