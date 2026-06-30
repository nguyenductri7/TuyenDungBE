<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TinTuyenDung\TaoTinTuyenDungRequest;
use App\Http\Requests\TinTuyenDung\CapNhatTinTuyenDungRequest;
use App\Models\TinTuyenDung;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminTinTuyenDungController - Admin quản lý tin tuyển dụng toàn hệ thống
 */
class AdminTinTuyenDungController extends Controller
{
    private function jobAuditSnapshot(TinTuyenDung $tin): array
    {
        return $tin->only([
            'id',
            'cong_ty_id',
            'tieu_de',
            'trang_thai',
            'hr_phu_trach_id',
            'ngay_het_han',
            'so_luong_tuyen',
            'muc_luong_tu',
            'muc_luong_den',
            'don_vi_luong',
            'dia_diem_lam_viec',
        ]);
    }

    private function normalizeSalaryPayload(array $data): array
    {
        $salaryFrom = array_key_exists('muc_luong_tu', $data) ? $data['muc_luong_tu'] : null;
        $salaryTo = array_key_exists('muc_luong_den', $data) ? $data['muc_luong_den'] : null;

        if ($salaryFrom !== null || $salaryTo !== null || (array_key_exists('muc_luong_tu', $data) && array_key_exists('muc_luong_den', $data))) {
            $data['don_vi_luong'] = $data['don_vi_luong'] ?? 'VND/tháng';
        }

        return $data;
    }

    /**
     * Danh sách tất cả tin
     */
    public function index(Request $request): JsonResponse
    {
        $query = TinTuyenDung::with([
            'congTy:id,ten_cong_ty,ma_so_thue',
            'nganhNghes:id,ten_nganh'
        ])->withCount([
            'acceptedApplications as so_luong_da_nhan',
            'ungTuyens as tong_ung_tuyen_thuc_te' => fn ($query) => $query->whereNotNull('thoi_gian_ung_tuyen'),
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tieu_de', 'like', "%{$search}%")
                  ->orWhereHas('congTy', function ($q2) use ($search) {
                      $q2->where('ten_cong_ty', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('cong_ty_id')) {
            $query->where('cong_ty_id', $request->cong_ty_id);
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $data = $query->orderBy('created_at', 'desc')->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Tạo thay NTD (Lưu ý Request cần truyền thêm cong_ty_id)
     */
    public function store(TaoTinTuyenDungRequest $request): JsonResponse
    {
        $data = $this->normalizeSalaryPayload($request->validated());
        
        $cong_ty_id = $request->input('cong_ty_id');
        if (!$cong_ty_id) {
            return response()->json([
                'message' => 'Bắt buộc chọn cong_ty_id khi admin tạo tin.'
            ], 422);
        }
        $data['cong_ty_id'] = $cong_ty_id;

        $nganhNgheIds = $data['nganh_nghes'];
        unset($data['nganh_nghes']);

        $tin = TinTuyenDung::create($data);
        $tin->nganhNghes()->attach($nganhNgheIds);
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_job_created',
            description: "Admin tạo tin tuyển dụng {$tin->tieu_de}.",
            target: $tin,
            company: $tin->congTy,
            after: [
                ...$this->jobAuditSnapshot($tin),
                'nganh_nghe_ids' => $nganhNgheIds,
            ],
            metadata: ['scope' => 'admin_job'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Admin tạo tin thành công.',
            'data' => $tin->load(['nganhNghes:id,ten_nganh', 'congTy:id,ten_cong_ty']),
        ], 201);
    }

    /**
     * Chi tiết tin (Admin)
     */
    public function show(int $id): JsonResponse
    {
        $tin = TinTuyenDung::with([
            'congTy:id,ten_cong_ty',
            'nganhNghes:id,ten_nganh'
        ])->withCount([
            'acceptedApplications as so_luong_da_nhan',
            'ungTuyens as tong_ung_tuyen_thuc_te' => fn ($query) => $query->whereNotNull('thoi_gian_ung_tuyen'),
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tin,
        ]);
    }

    /**
     * Cập nhật tin (Admin biên tập)
     */
    public function update(CapNhatTinTuyenDungRequest $request, int $id): JsonResponse
    {
        $tin = TinTuyenDung::findOrFail($id);
        $data = $this->normalizeSalaryPayload($request->validated());
        $before = [
            ...$this->jobAuditSnapshot($tin),
            'nganh_nghe_ids' => $tin->nganhNghes()->pluck('nganh_nghes.id')->all(),
        ];
        $nganhNgheIds = $data['nganh_nghes'] ?? null;

        if (isset($data['nganh_nghes'])) {
            $tin->nganhNghes()->sync($data['nganh_nghes']);
            unset($data['nganh_nghes']);
        }

        $tin->update($data);
        $tinAfter = $tin->fresh();
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_job_updated',
            description: "Admin cập nhật tin tuyển dụng {$tinAfter->tieu_de}.",
            target: $tinAfter,
            company: $tinAfter->congTy,
            before: $before,
            after: [
                ...$this->jobAuditSnapshot($tinAfter),
                'nganh_nghe_ids' => $nganhNgheIds ?? $tinAfter->nganhNghes()->pluck('nganh_nghes.id')->all(),
            ],
            metadata: ['scope' => 'admin_job'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Admin cập nhật thành công.',
            'data' => $tin->fresh()->load('nganhNghes:id,ten_nganh'),
        ]);
    }

    /**
     * Cập nhật trạng thái tin (Admin duyệt / huỷ / khóa tin)
     */
    public function doiTrangThai(int $id): JsonResponse
    {
        $tin = TinTuyenDung::findOrFail($id);
        $before = $this->jobAuditSnapshot($tin);
        
        $tin->trang_thai = $tin->trang_thai == 1 ? 0 : 1;
        $tin->save();
        app(AuditLogService::class)->logModelAction(
            actor: auth()->user(),
            action: 'admin_job_status_toggled',
            description: "Admin chuyển trạng thái tin tuyển dụng {$tin->tieu_de}.",
            target: $tin,
            company: $tin->congTy,
            before: $before,
            after: $this->jobAuditSnapshot($tin),
            metadata: ['scope' => 'admin_job'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Admin đã chuyển trạng thái tin.',
            'data' => $tin,
        ]);
    }

    /**
     * Xóa hoàn toàn
     */
    public function destroy(int $id): JsonResponse
    {
        $tin = TinTuyenDung::findOrFail($id);

        if ($tin->ungTuyens()->whereNotNull('thoi_gian_ung_tuyen')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tin tuyển dụng này đã có đơn ứng tuyển. Hãy chuyển sang tạm ngưng/lưu trữ thay vì xóa cứng.',
            ], 422);
        }

        $before = $this->jobAuditSnapshot($tin);
        $tin->delete();
        app(AuditLogService::class)->logModelAction(
            actor: auth()->user(),
            action: 'admin_job_deleted',
            description: "Admin xoá tin tuyển dụng {$before['tieu_de']}.",
            target: $tin,
            before: $before,
            metadata: ['scope' => 'admin_job'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Admin xoá tin thành công.',
        ]);
    }

    /**
     * Thống kê
     */
    public function thongKe(): JsonResponse
    {
        $total = TinTuyenDung::count();
        $hoatDong = TinTuyenDung::where('trang_thai', 1)->count();
        $tamNgung = TinTuyenDung::where('trang_thai', 0)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'tong_tin' => $total,
                'hoat_dong' => $hoatDong,
                'tam_ngung' => $tamNgung,
            ],
        ]);
    }
}
