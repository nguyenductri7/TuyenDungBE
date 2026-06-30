<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CongTy;
use App\Models\NguoiDung;
use App\Support\EncodedId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * CongTyController - Xem công ty (Public)
 *
 * Không yêu cầu đăng nhập.
 *
 * Routes:
 *   GET  /api/v1/cong-tys          - Danh sách công ty (hoạt động)
 *   GET  /api/v1/cong-tys/{id}     - Chi tiết công ty
 */
class CongTyController extends Controller
{
    private function mapCompanyData(CongTy $congTy, array $followedCompanyIds = []): array
    {
        $data = $congTy->toArray();
        $data['logo_url'] = $congTy->logo
            ? url('/api/v1/cong-ty-logo?path=' . urlencode($congTy->logo))
            : null;
        $data['so_nguoi_theo_doi'] = (int) ($data['so_nguoi_theo_doi'] ?? 0);
        $data['da_theo_doi'] = in_array((int) $congTy->id, $followedCompanyIds, true);

        return $data;
    }

    private function getFollowedCompanyIds(?NguoiDung $nguoiDung): array
    {
        if (!$nguoiDung?->isUngVien()) {
            return [];
        }

        return $nguoiDung->congTyTheoDois()
            ->pluck('cong_tys.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * GET /api/v1/cong-tys
     * Danh sách công ty đang hoạt động.
     */
    public function index(Request $request): JsonResponse
    {
        $nguoiDung = auth('sanctum')->user();
        $followedCompanyIds = $this->getFollowedCompanyIds($nguoiDung);

        $query = CongTy::with('nganhNghe:id,ten_nganh')
            ->withCount([
                'nguoiDungTheoDois as so_nguoi_theo_doi',
                'tinTuyenDungs as so_tin_dang_hoat_dong' => function ($q) {
                    $q->where('trang_thai', \App\Models\TinTuyenDung::TRANG_THAI_HOAT_DONG)
                        ->where(function ($subQ) {
                            $subQ->whereNull('ngay_het_han')
                                ->orWhere('ngay_het_han', '>=', now());
                        });
                }
            ])
            ->where('trang_thai', CongTy::TRANG_THAI_HOAT_DONG);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ten_cong_ty', 'like', "%{$search}%")
                    ->orWhere('dia_chi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('nganh_nghe_id')) {
            $query->where('nganh_nghe_id', $request->nganh_nghe_id);
        }

        if ($request->filled('quy_mo')) {
            $query->where('quy_mo', $request->quy_mo);
        }

        $query->orderBy('ten_cong_ty', 'asc');

        $perPage = (int) $request->get('per_page', 0);
        if ($perPage > 0) {
            $data = $query->paginate(min($perPage, 100));
        } else {
            $data = $query->get();
        }

        return response()->json([
            'success' => true,
            'data' => $data instanceof \Illuminate\Pagination\LengthAwarePaginator
                ? $data->through(fn ($item) => $this->mapCompanyData($item, $followedCompanyIds))
                : $data->map(fn ($item) => $this->mapCompanyData($item, $followedCompanyIds)),
        ]);
    }

    /**
     * GET /api/v1/cong-tys/{id}
     * Chi tiết công ty.
     */
    public function show(string $id): JsonResponse
    {
        $decodedId = EncodedId::decodeOrFail($id);
        $nguoiDung = auth('sanctum')->user();
        $followedCompanyIds = $this->getFollowedCompanyIds($nguoiDung);

        $congTy = CongTy::with([
            'nganhNghe:id,ten_nganh',
            'nguoiDung:id,ho_ten,email',
            'tinTuyenDungs' => function ($query) {
                $query->where('trang_thai', \App\Models\TinTuyenDung::TRANG_THAI_HOAT_DONG)
                    ->where(function ($subQ) {
                        $subQ->whereNull('ngay_het_han')
                            ->orWhere('ngay_het_han', '>=', now());
                    })
                    ->withCount([
                        'acceptedApplications as so_luong_da_nhan',
                    ])
                    ->orderByDesc('created_at')
                    ->limit(6);
            },
        ])
            ->withCount([
                'nguoiDungTheoDois as so_nguoi_theo_doi',
                'tinTuyenDungs as so_tin_dang_hoat_dong' => function ($query) {
                    $query->where('trang_thai', \App\Models\TinTuyenDung::TRANG_THAI_HOAT_DONG)
                        ->where(function ($subQ) {
                            $subQ->whereNull('ngay_het_han')
                                ->orWhere('ngay_het_han', '>=', now());
                        });
                }
            ])
            ->where('trang_thai', CongTy::TRANG_THAI_HOAT_DONG)
            ->findOrFail($decodedId);

        return response()->json([
            'success' => true,
            'data' => $this->mapCompanyData($congTy, $followedCompanyIds),
        ]);
    }

    public function logo(Request $request)
    {
        $path = (string) $request->query('path', '');

        abort_unless(
            $path !== '' && str_starts_with($path, 'cong_ty_logos/'),
            404
        );

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }
}
