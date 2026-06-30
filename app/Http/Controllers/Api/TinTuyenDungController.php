<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Models\TinTuyenDung;
use App\Support\EncodedId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TinTuyenDungController - Public / Ứng viên
 *
 * Routes:
 *   GET /api/v1/tin-tuyen-dungs
 *   GET /api/v1/tin-tuyen-dungs/{id}
 */
class TinTuyenDungController extends Controller
{
    private function buildLocationSearchTerms(string $location): array
    {
        $location = trim($location);
        if ($location === '') {
            return [];
        }

        $terms = [$location];
        $withoutCityPrefix = preg_replace('/^(Thành phố|TP\\.?|Tp\\.?)\\s+/iu', '', $location);
        if (is_string($withoutCityPrefix) && $withoutCityPrefix !== $location) {
            $terms[] = trim($withoutCityPrefix);
        }

        $cityAliases = [
            'Thành phố Hồ Chí Minh' => ['Hồ Chí Minh', 'TP. Hồ Chí Minh', 'TP.HCM', 'TP HCM', 'HCM', 'Sài Gòn', 'Sai Gon'],
            'Thành phố Hà Nội' => ['Hà Nội'],
            'Thành phố Hải Phòng' => ['Hải Phòng'],
            'Thành phố Đà Nẵng' => ['Đà Nẵng'],
            'Thành phố Huế' => ['Huế'],
            'Thành phố Cần Thơ' => ['Cần Thơ'],
        ];

        foreach ($cityAliases[$location] ?? [] as $alias) {
            $terms[] = $alias;
        }

        return array_values(array_unique(array_filter($terms)));
    }

    private function buildBaseDetailQuery()
    {
        return TinTuyenDung::with([
                'congTy:id,ten_cong_ty,ma_so_thue,logo,dia_chi,website,mo_ta,quy_mo,trang_thai',
                'nganhNghes:id,ten_nganh'
            ])
            ->withCount([
                'acceptedApplications as so_luong_da_nhan',
            ]);
    }

    private function candidateCanViewRestrictedJob(?NguoiDung $nguoiDung, int $jobId): bool
    {
        if (!$nguoiDung || !$nguoiDung->isUngVien()) {
            return false;
        }

        $hasSavedJob = $nguoiDung->tinDaLuus()
            ->where('tin_tuyen_dung_id', $jobId)
            ->exists();

        if ($hasSavedJob) {
            return true;
        }

        return $nguoiDung->hoSos()
            ->whereHas('ungTuyens', function ($query) use ($jobId) {
                $query->where('tin_tuyen_dung_id', $jobId)
                    ->whereNotNull('thoi_gian_ung_tuyen');
            })
            ->exists();
    }

    /**
     * Danh sách tin tuyển dụng đang hoạt động, công ty hoạt động, còn hạn.
     */
    public function index(Request $request): JsonResponse
    {
        // Lọc các tin có trạng thái = 1, ngay_het_han >= hiện tại, và công ty hoạt động
        $query = TinTuyenDung::with([
                'congTy:id,ten_cong_ty,ma_so_thue,logo,dia_chi',
                'nganhNghes:id,ten_nganh'
            ])
            ->withCount([
                'acceptedApplications as so_luong_da_nhan',
            ])
            ->where('trang_thai', TinTuyenDung::TRANG_THAI_HOAT_DONG)
            ->where(function ($q) {
                $q->whereNull('ngay_het_han')
                  ->orWhere('ngay_het_han', '>=', now());
            })
            ->whereHas('congTy', function ($q) {
                $q->where('trang_thai', \App\Models\CongTy::TRANG_THAI_HOAT_DONG);
            });

        // Tìm kiếm chung (tiêu đề, công ty, địa điểm, kỹ năng/mô tả)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tieu_de', 'like', "%{$search}%")
                  ->orWhere('dia_diem_lam_viec', 'like', "%{$search}%")
                  ->orWhere('mo_ta_cong_viec', 'like', "%{$search}%")
                  ->orWhereHas('congTy', function ($q2) use ($search) {
                      $q2->where('ten_cong_ty', 'like', "%{$search}%");
                  });
            });
        }

        // Lọc theo ngành nghề
        if ($request->filled('nganh_nghe_id')) {
            $query->whereHas('nganhNghes', function ($q) use ($request) {
                $q->where('nganh_nghes.id', $request->nganh_nghe_id);
            });
        }

        // Lọc theo tỉnh/thành phố hoặc địa điểm
        if ($request->filled('dia_diem')) {
            $locationTerms = $this->buildLocationSearchTerms((string) $request->dia_diem);
            $query->where(function ($q) use ($locationTerms) {
                foreach ($locationTerms as $term) {
                    $q->orWhere('dia_diem_lam_viec', 'like', '%' . $term . '%');
                }
            });
        }

        $query
            ->orderFeaturedFirst()
            ->orderBy('created_at', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Chi tiết tin tuyển dụng.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $decodedId = EncodedId::decodeOrFail($id);
        $tinTuyenDung = $this->buildBaseDetailQuery()
            ->where('trang_thai', TinTuyenDung::TRANG_THAI_HOAT_DONG)
            ->whereHas('congTy', function ($q) {
                $q->where('trang_thai', \App\Models\CongTy::TRANG_THAI_HOAT_DONG);
            })
            ->find($decodedId);

        if (!$tinTuyenDung) {
            $nguoiDung = auth('sanctum')->user();

            if (!$this->candidateCanViewRestrictedJob($nguoiDung, $decodedId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tin tuyển dụng.',
                ], 404);
            }

            $tinTuyenDung = $this->buildBaseDetailQuery()->findOrFail($decodedId);
        }

        // Tăng lượt xem
        $tinTuyenDung->increment('luot_xem');

        return response()->json([
            'success' => true,
            'data' => $tinTuyenDung,
        ]);
    }
}
