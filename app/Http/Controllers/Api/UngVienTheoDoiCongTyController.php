<?php

namespace App\Http\Controllers\Api;

use App\Events\CompanyFollowerCountUpdated;
use App\Http\Controllers\Controller;
use App\Models\CongTy;
use App\Models\TinTuyenDung;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UngVienTheoDoiCongTyController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    private function dispatchFollowerCountUpdate(CongTy $congTy): void
    {
        try {
            broadcast(new CompanyFollowerCountUpdated(
                (int) $congTy->id,
                (int) $congTy->nguoiDungTheoDois()->count(),
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Phiên đăng nhập không còn hợp lệ.',
        ], 401);
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $recentJobsLimit = max(1, min((int) $request->get('recent_jobs_limit', 3), 10));

        $query = $user->congTyTheoDois()
            ->with('nganhNghe:id,ten_nganh')
            ->withCount([
                'nguoiDungTheoDois as so_nguoi_theo_doi',
                'tinTuyenDungs as so_tin_dang_hoat_dong' => function ($q) {
                    $q->where('trang_thai', TinTuyenDung::TRANG_THAI_HOAT_DONG)
                        ->where(function ($subQ) {
                            $subQ->whereNull('ngay_het_han')
                                ->orWhere('ngay_het_han', '>=', now());
                        });
                },
            ])
            ->orderBy('theo_doi_cong_tys.created_at', 'desc');

        $data = $query->paginate((int) $request->get('per_page', 15));

        $userId = (int) $user->id;

        $data->getCollection()->transform(function (CongTy $congTy) use ($recentJobsLimit, $userId) {
            $payload = $congTy->toArray();
            $payload['logo_url'] = $congTy->logo
                ? url('/api/v1/cong-ty-logo?path=' . urlencode($congTy->logo))
                : null;
            $payload['da_theo_doi'] = true;
            $payload['theo_doi_luc'] = optional($congTy->pivot?->created_at)->toISOString();
            $jobs = $congTy->tinTuyenDungs()
                ->select([
                    'id',
                    'tieu_de',
                    'dia_diem_lam_viec',
                    'hinh_thuc_lam_viec',
                    'muc_luong_tu',
                    'muc_luong_den',
                    'ngay_het_han',
                    'trang_thai',
                    'cong_ty_id',
                    'created_at',
                    'published_at',
                    'reactivated_at',
                ])
                ->where('trang_thai', TinTuyenDung::TRANG_THAI_HOAT_DONG)
                ->where(function ($subQ) {
                    $subQ->whereNull('ngay_het_han')
                        ->orWhere('ngay_het_han', '>=', now());
                })
                ->orderByRaw('COALESCE(reactivated_at, published_at, created_at) DESC')
                ->limit($recentJobsLimit)
                ->get();

            $payload['tin_tuyen_dungs'] = $jobs
                ->map(fn (TinTuyenDung $job) => $job->toArray())
                ->values()
                ->all();

            return $payload;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function toggle(Request $request, int $congTyId): JsonResponse
    {
        $congTy = CongTy::where('trang_thai', CongTy::TRANG_THAI_HOAT_DONG)->findOrFail($congTyId);

        $user = auth()->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $changes = $user->congTyTheoDois()->toggle($congTy->id);
        $daTheoDoi = count($changes['attached']) > 0;
        $soNguoiTheoDoi = (int) $congTy->nguoiDungTheoDois()->count();

        $this->dispatchFollowerCountUpdate($congTy);
        $this->auditLogService->logModelAction(
            actor: $user,
            action: $daTheoDoi ? 'candidate_company_followed' : 'candidate_company_unfollowed',
            description: ($daTheoDoi ? 'Ứng viên theo dõi ' : 'Ứng viên bỏ theo dõi ') . $congTy->ten_cong_ty . '.',
            target: $congTy,
            company: $congTy,
            after: [
                'cong_ty_id' => $congTy->id,
                'da_theo_doi' => $daTheoDoi,
                'so_nguoi_theo_doi' => $soNguoiTheoDoi,
            ],
            metadata: ['scope' => 'candidate_follow'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => $daTheoDoi ? 'Đã theo dõi công ty.' : 'Đã bỏ theo dõi công ty.',
            'data' => [
                'cong_ty_id' => $congTy->id,
                'trang_thai_theo_doi' => $daTheoDoi,
                'so_nguoi_theo_doi' => $soNguoiTheoDoi,
            ],
        ], $daTheoDoi ? 201 : 200);
    }
}
