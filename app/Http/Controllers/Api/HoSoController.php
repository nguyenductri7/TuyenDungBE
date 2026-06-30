<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HoSo\TaoHoSoRequest;
use App\Http\Requests\HoSo\CapNhatHoSoUngVienRequest;
use App\Models\HoSo;
use App\Services\CvUploadValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * HoSoController - Quản lý hồ sơ dành cho Ứng viên
 *
 * Vai trò được phép: Ứng viên (vai_tro = 0)
 *
 * Routes:
 *   GET    /api/v1/ung-vien/ho-sos              - Danh sách hồ sơ của ứng viên
 *   POST   /api/v1/ung-vien/ho-sos              - Tạo hồ sơ mới
 *   GET    /api/v1/ung-vien/ho-sos/{id}         - Xem chi tiết hồ sơ
 *   PUT    /api/v1/ung-vien/ho-sos/{id}         - Cập nhật hồ sơ
 *   DELETE /api/v1/ung-vien/ho-sos/{id}         - Xoá hồ sơ
 *   PATCH  /api/v1/ung-vien/ho-sos/{id}/trang-thai - Đổi trạng thái (công khai/ẩn)
 */
class HoSoController extends Controller
{
    public function __construct(private readonly CvUploadValidationService $cvUploadValidationService)
    {
    }

    private function syncCvPhoto(Request $request, HoSo $hoSo, array &$data): void
    {
        $photoMode = (string) ($data['che_do_anh_cv'] ?? $hoSo->che_do_anh_cv ?? 'profile');
        $data['che_do_anh_cv'] = in_array($photoMode, ['profile', 'upload'], true) ? $photoMode : 'profile';

        if ($data['che_do_anh_cv'] === 'profile') {
            if ($hoSo->anh_cv) {
                Storage::disk('public')->delete($hoSo->anh_cv);
            }

            $data['anh_cv'] = null;
            return;
        }

        if ($request->hasFile('anh_cv')) {
            if ($hoSo->anh_cv) {
                Storage::disk('public')->delete($hoSo->anh_cv);
            }

            $data['anh_cv'] = $request->file('anh_cv')->store('cv_photos', 'public');
            return;
        }

        if (!$hoSo->anh_cv && empty($data['anh_cv'])) {
            throw ValidationException::withMessages([
                'anh_cv' => ['Hãy tải ảnh đại diện riêng cho CV khi chọn chế độ upload.'],
            ]);
        }
    }

    private function hasBuilderData(array $data): bool
    {
        $builderFields = [
            'mau_cv',
            'che_do_mau_cv',
            'vi_tri_ung_tuyen_muc_tieu',
            'ten_nganh_nghe_muc_tieu',
            'ky_nang_json',
            'kinh_nghiem_json',
            'hoc_van_json',
            'du_an_json',
            'chung_chi_json',
        ];

        foreach ($builderFields as $field) {
            $value = $data[$field] ?? null;

            if (is_array($value) && $value !== []) {
                return true;
            }

            if (!is_array($value) && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function resolveProfileSource(array $data, bool $hasFile): string
    {
        $source = (string) ($data['nguon_ho_so'] ?? '');
        if (in_array($source, ['upload', 'builder', 'hybrid'], true)) {
            return $source;
        }

        $hasBuilderData = $this->hasBuilderData($data);

        if ($hasFile && $hasBuilderData) {
            return 'hybrid';
        }

        if ($hasBuilderData) {
            return 'builder';
        }

        return 'upload';
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Phiên đăng nhập không còn hợp lệ.',
        ], 401);
    }

    private function mapOwnedProfile(HoSo $hoSo): array
    {
        return [
            ...$hoSo->toArray(),
            'file_cv_url' => $hoSo->file_cv
                ? route('ung-vien.ho-sos.cv', ['id' => $hoSo->id])
                : null,
        ];
    }

    /**
     * GET /api/v1/ung-vien/ho-sos
     * Danh sách hồ sơ của người dùng đang đăng nhập.
     */
    public function index(Request $request): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->unauthorizedResponse();
        }

        $query = HoSo::with([
                'parsing:id,ho_so_id,parse_status,confidence_score,parser_version,error_message,updated_at'
            ])
            ->where('nguoi_dung_id', $nguoiDung->id);

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $allowedSorts = ['id', 'tieu_de_ho_so', 'created_at', 'updated_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts)
            ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->get('per_page', 10), 50);
        $hoSos = $query->paginate($perPage);
        $hoSos->setCollection(
            $hoSos->getCollection()->map(fn (HoSo $hoSo) => $this->mapOwnedProfile($hoSo))
        );

        return response()->json([
            'success' => true,
            'data' => $hoSos,
        ]);
    }

    /**
     * POST /api/v1/ung-vien/ho-sos
     * Ứng viên tạo hồ sơ mới.
     */
    public function store(TaoHoSoRequest $request): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->unauthorizedResponse();
        }

        $data = $request->validated();
        $data['nguoi_dung_id'] = $nguoiDung->id;
        $data['nguon_ho_so'] = $this->resolveProfileSource($data, $request->hasFile('file_cv'));
        $emptyProfile = new HoSo();
        $this->syncCvPhoto($request, $emptyProfile, $data);

        // Upload file CV nếu có
        if ($request->hasFile('file_cv')) {
            $this->cvUploadValidationService->validate($request->file('file_cv'));
            $data['file_cv'] = $request->file('file_cv')
                ->store('file_cv', 'public');
        }

        $hoSo = HoSo::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo hồ sơ thành công.',
            'data' => $hoSo,
        ], 201);
    }

    /**
     * GET /api/v1/ung-vien/ho-sos/{id}
     * Xem chi tiết hồ sơ (chỉ xem được hồ sơ của mình).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->unauthorizedResponse();
        }

        $hoSo = HoSo::with([
                'parsing:id,ho_so_id,raw_text,parsed_name,parsed_email,parsed_phone,parsed_skills_json,parsed_experience_json,parsed_education_json,parse_status,confidence_score,parser_version,error_message,updated_at'
            ])
            ->where('nguoi_dung_id', $nguoiDung->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->mapOwnedProfile($hoSo),
        ]);
    }

    public function viewCv(Request $request, int $id)
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->unauthorizedResponse();
        }

        $hoSo = HoSo::where('nguoi_dung_id', $nguoiDung->id)
            ->findOrFail($id);

        abort_unless($hoSo->file_cv, 404, 'Hồ sơ này chưa có file CV tải lên.');

        $path = storage_path('app/public/' . ltrim($hoSo->file_cv, '/'));
        abort_unless(file_exists($path), 404, 'Không tìm thấy file CV.');

        return response()->file($path);
    }

    /**
     * PUT /api/v1/ung-vien/ho-sos/{id}
     * Ứng viên cập nhật hồ sơ của mình.
     */
    public function update(CapNhatHoSoUngVienRequest $request, int $id): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->unauthorizedResponse();
        }

        $hoSo = HoSo::where('nguoi_dung_id', $nguoiDung->id)
            ->findOrFail($id);

        $data = $request->validated();
        $data['nguon_ho_so'] = $this->resolveProfileSource(
            $data + ['file_cv' => $hoSo->file_cv],
            $request->hasFile('file_cv') || !empty($hoSo->file_cv)
        );
        $this->syncCvPhoto($request, $hoSo, $data);

        // Upload file CV mới nếu có, xoá file cũ
        if ($request->hasFile('file_cv')) {
            $this->cvUploadValidationService->validate($request->file('file_cv'));
            if ($hoSo->file_cv) {
                Storage::disk('public')->delete($hoSo->file_cv);
            }
            $data['file_cv'] = $request->file('file_cv')
                ->store('file_cv', 'public');
        }

        $hoSo->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công.',
            'data' => $hoSo->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/ung-vien/ho-sos/{id}
     * Ứng viên xoá hồ sơ của mình.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->unauthorizedResponse();
        }

        $hoSo = HoSo::where('nguoi_dung_id', $nguoiDung->id)
            ->findOrFail($id);

        // Xoá file CV nếu có
        if ($hoSo->file_cv) {
            Storage::disk('public')->delete($hoSo->file_cv);
        }

        if ($hoSo->anh_cv) {
            Storage::disk('public')->delete($hoSo->anh_cv);
        }

        $hoSo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoá hồ sơ thành công.',
        ]);
    }

    /**
     * PATCH /api/v1/ung-vien/ho-sos/{id}/trang-thai
     * Ứng viên đổi trạng thái hồ sơ (công khai/ẩn).
     */
    public function doiTrangThai(Request $request, int $id): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->unauthorizedResponse();
        }

        $hoSo = HoSo::where('nguoi_dung_id', $nguoiDung->id)
            ->findOrFail($id);

        $hoSo->trang_thai = $hoSo->trang_thai ? 0 : 1;
        $hoSo->save();

        $action = $hoSo->trang_thai ? 'Công khai' : 'Ẩn';

        return response()->json([
            'success' => true,
            'message' => "{$action} hồ sơ thành công.",
            'data' => $hoSo,
        ]);
    }
}
