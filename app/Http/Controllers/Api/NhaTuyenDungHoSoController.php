<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HoSo;
use App\Models\NguoiDung;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NhaTuyenDungHoSoController - Nhà tuyển dụng xem hồ sơ ứng viên
 *
 * Vai trò được phép: Nhà tuyển dụng (vai_tro = 1)
 *
 * NTD chỉ có quyền XEM các hồ sơ công khai (trang_thai = 1).
 * Không có quyền tạo, sửa, xoá hồ sơ.
 *
 * Routes:
 *   GET  /api/v1/nha-tuyen-dung/ho-sos         - Danh sách hồ sơ công khai (có lọc + tìm kiếm + phân trang)
 *   GET  /api/v1/nha-tuyen-dung/ho-sos/{id}    - Chi tiết hồ sơ công khai
 */
class NhaTuyenDungHoSoController extends Controller
{
    /**
     * GET /api/v1/nha-tuyen-dung/ho-sos
     * Danh sách hồ sơ công khai của ứng viên.
     *
     * NTD chỉ thấy được hồ sơ có trang_thai = 1 (công khai).
     *
     * Query params:
     *   ?trinh_do=Đại học        Lọc theo trình độ
     *   ?kinh_nghiem_tu=2        Kinh nghiệm từ (năm)
     *   ?kinh_nghiem_den=5       Kinh nghiệm đến (năm)
     *   ?search=keyword          Tìm theo tiêu đề/mục tiêu/mô tả
     *   ?sort_by=created_at      Sắp xếp theo trường
     *   ?sort_dir=asc|desc       Chiều sắp xếp
     *   ?per_page=15             Số bản ghi mỗi trang
     */
    public function index(Request $request): JsonResponse
    {
        $query = HoSo::query()
            ->where('trang_thai', HoSo::TRANG_THAI_CONG_KHAI);

        // Lọc theo trình độ
        if ($request->filled('trinh_do')) {
            $query->whereIn('trinh_do', HoSo::trinhDoQueryValues($request->trinh_do));
        }

        // Lọc theo khoảng kinh nghiệm
        if ($request->filled('kinh_nghiem_tu')) {
            $query->where('kinh_nghiem_nam', '>=', (float) $request->kinh_nghiem_tu);
        }

        if ($request->filled('kinh_nghiem_den')) {
            $query->where('kinh_nghiem_nam', '<=', (float) $request->kinh_nghiem_den);
        }

        // Tìm kiếm theo tiêu đề, mục tiêu, mô tả
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tieu_de_ho_so', 'like', "%{$search}%")
                    ->orWhere('muc_tieu_nghe_nghiep', 'like', "%{$search}%")
                    ->orWhere('mo_ta_ban_than', 'like', "%{$search}%")
                    ->orWhereHas('nguoiDung', function ($userQuery) use ($search) {
                        $userQuery->where('ho_ten', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('so_dien_thoai', 'like', "%{$search}%");
                    });
            });
        }

        // Sắp xếp
        $allowedSorts = ['id', 'tieu_de_ho_so', 'trinh_do', 'kinh_nghiem_nam', 'created_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts)
            ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir)->orderBy('id', 'desc');

        // Phân trang theo ứng viên, không phân trang theo từng CV.
        $perPage = min((int) $request->get('per_page', 15), 100);
        $currentPage = max((int) $request->get('page', 1), 1);
        $matchingProfiles = $query
            ->with('nguoiDung:id,ho_ten,email,so_dien_thoai,anh_dai_dien')
            ->get();
        $groupedProfiles = $matchingProfiles->groupBy('nguoi_dung_id');
        $pagedUserIds = $groupedProfiles->keys()
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $users = NguoiDung::whereIn('id', $pagedUserIds)
            ->select('id', 'ho_ten', 'email', 'so_dien_thoai', 'anh_dai_dien')
            ->get()
            ->keyBy('id');
        $allPublicProfilesByUser = HoSo::where('trang_thai', HoSo::TRANG_THAI_CONG_KHAI)
            ->whereIn('nguoi_dung_id', $pagedUserIds)
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('nguoi_dung_id');

        $items = $pagedUserIds
            ->map(function ($userId) use ($allPublicProfilesByUser, $groupedProfiles, $users) {
                $profiles = $allPublicProfilesByUser->get($userId, collect())
                    ->values()
                    ->map(fn (HoSo $hoSo) => $this->mapEmployerProfile($hoSo))
                    ->all();
                $matchedDefaultProfileId = optional($groupedProfiles->get($userId, collect())->first())->id;
                $defaultProfile = collect($profiles)->firstWhere('id', $matchedDefaultProfileId) ?: ($profiles[0] ?? null);
                $user = $this->mapEmployerCandidateUser($users->get($userId));

                return [
                    'id' => (int) $userId,
                    'nguoi_dung' => $user,
                    'ho_so_mac_dinh' => $defaultProfile,
                    'ho_sos' => $profiles,
                    'so_luong_cv' => count($profiles),
                    'file_cv_url' => $defaultProfile['file_cv_url'] ?? null,
                    'tieu_de_ho_so' => $defaultProfile['tieu_de_ho_so'] ?? null,
                    'muc_tieu_nghe_nghiep' => $defaultProfile['muc_tieu_nghe_nghiep'] ?? null,
                    'trinh_do' => $defaultProfile['trinh_do'] ?? null,
                    'kinh_nghiem_nam' => $defaultProfile['kinh_nghiem_nam'] ?? null,
                    'mo_ta_ban_than' => $defaultProfile['mo_ta_ban_than'] ?? null,
                    'nguon_ho_so' => $defaultProfile['nguon_ho_so'] ?? null,
                ];
            })
            ->values();

        $hoSos = new LengthAwarePaginator(
            $items,
            $groupedProfiles->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return response()->json([
            'success' => true,
            'data' => $hoSos,
        ]);
    }

    /**
     * GET /api/v1/nha-tuyen-dung/ho-sos/{id}
     * Xem chi tiết hồ sơ công khai.
     *
     * NTD chỉ xem được hồ sơ có trang_thai = 1.
     */
    public function show(int $id): JsonResponse
    {
        $hoSo = HoSo::with('nguoiDung:id,ho_ten,email,so_dien_thoai,anh_dai_dien')
            ->where('trang_thai', HoSo::TRANG_THAI_CONG_KHAI)
            ->findOrFail($id);
        $hoSo->file_cv_url = $hoSo->file_cv
            ? route('nha-tuyen-dung.ho-sos.cv', ['id' => $hoSo->id])
            : null;
        if ($hoSo->nguoiDung) {
            $hoSo->nguoiDung->avatar_url = $this->buildAvatarUrl($hoSo->nguoiDung->anh_dai_dien);
        }

        return response()->json([
            'success' => true,
            'data' => $hoSo,
        ]);
    }

    private function mapEmployerCandidateUser(?NguoiDung $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'ho_ten' => $user->ho_ten,
            'email' => $user->email,
            'so_dien_thoai' => $user->so_dien_thoai,
            'anh_dai_dien' => $user->anh_dai_dien,
            'avatar_url' => $this->buildAvatarUrl($user->anh_dai_dien),
        ];
    }

    private function buildAvatarUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return url('/api/v1/anh-dai-dien?path=' . urlencode($path));
    }

    private function mapEmployerProfile(HoSo $hoSo): array
    {
        return [
            'id' => $hoSo->id,
            'nguoi_dung_id' => $hoSo->nguoi_dung_id,
            'tieu_de_ho_so' => $hoSo->tieu_de_ho_so,
            'muc_tieu_nghe_nghiep' => $hoSo->muc_tieu_nghe_nghiep,
            'trinh_do' => $hoSo->trinh_do,
            'kinh_nghiem_nam' => $hoSo->kinh_nghiem_nam,
            'mo_ta_ban_than' => $hoSo->mo_ta_ban_than,
            'nguon_ho_so' => $hoSo->nguon_ho_so,
            'mau_cv' => $hoSo->mau_cv,
            'bo_cuc_cv' => $hoSo->bo_cuc_cv,
            'ten_template_cv' => $hoSo->ten_template_cv,
            'che_do_mau_cv' => $hoSo->che_do_mau_cv,
            'vi_tri_ung_tuyen_muc_tieu' => $hoSo->vi_tri_ung_tuyen_muc_tieu,
            'ten_nganh_nghe_muc_tieu' => $hoSo->ten_nganh_nghe_muc_tieu,
            'che_do_anh_cv' => $hoSo->che_do_anh_cv,
            'anh_cv' => $hoSo->anh_cv,
            'anh_cv_url' => $hoSo->anh_cv_url,
            'ky_nang_json' => $hoSo->ky_nang_json,
            'kinh_nghiem_json' => $hoSo->kinh_nghiem_json,
            'hoc_van_json' => $hoSo->hoc_van_json,
            'du_an_json' => $hoSo->du_an_json,
            'chung_chi_json' => $hoSo->chung_chi_json,
            'file_cv' => $hoSo->file_cv,
            'file_cv_url' => $hoSo->file_cv
                ? route('nha-tuyen-dung.ho-sos.cv', ['id' => $hoSo->id])
                : null,
            'created_at' => optional($hoSo->created_at)?->toISOString(),
            'updated_at' => optional($hoSo->updated_at)?->toISOString(),
        ];
    }

    public function downloadCv(int $id)
    {
        $hoSo = HoSo::where('trang_thai', HoSo::TRANG_THAI_CONG_KHAI)->findOrFail($id);

        abort_unless($hoSo->file_cv, 404, 'Ứng viên này chưa có file CV.');

        $path = storage_path('app/public/' . ltrim($hoSo->file_cv, '/'));
        abort_unless(file_exists($path), 404, 'Không tìm thấy file CV.');

        return response()->file($path);
    }
}
