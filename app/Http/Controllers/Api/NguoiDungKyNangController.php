<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NguoiDungKyNang\ThemKyNangRequest;
use App\Http\Requests\NguoiDungKyNang\CapNhatKyNangCaNhanRequest;
use App\Models\NguoiDungKyNang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * NguoiDungKyNangController - Ứng viên quản lý kỹ năng cá nhân
 *
 * Vai trò: Ứng viên (vai_tro = 0)
 *
 * Ứng viên chỉ quản lý kỹ năng của chính mình.
 * Hỗ trợ upload hình ảnh chứng chỉ (jpg, png, webp, max 2MB).
 *
 * Routes:
 *   GET    /api/v1/ung-vien/ky-nangs           - Danh sách kỹ năng của mình
 *   POST   /api/v1/ung-vien/ky-nangs           - Thêm kỹ năng (kèm upload ảnh chứng chỉ)
 *   POST   /api/v1/ung-vien/ky-nangs/{id}      - Cập nhật (dùng POST + _method=PUT cho multipart)
 *   DELETE /api/v1/ung-vien/ky-nangs/{id}      - Xoá kỹ năng (xoá luôn ảnh)
 */
class NguoiDungKyNangController extends Controller
{
    private function buildSkillImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return url('/api/v1/chung-chi-ky-nang?path=' . urlencode($path));
    }

    /**
     * GET /api/v1/ung-vien/ky-nangs
     * Danh sách kỹ năng của ứng viên đang đăng nhập.
     */
    public function index(): JsonResponse
    {
        $nguoiDungId = auth()->id();

        $kyNangs = NguoiDungKyNang::with('kyNang:id,ten_ky_nang,icon')
            ->where('nguoi_dung_id', $nguoiDungId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->hinh_anh_url = $this->buildSkillImageUrl($item->hinh_anh);
                return $item;
            });

        return response()->json([
            'success' => true,
            'data' => $kyNangs,
        ]);
    }

    /**
     * POST /api/v1/ung-vien/ky-nangs
     * Thêm kỹ năng vào hồ sơ cá nhân.
     * Hỗ trợ upload hình ảnh chứng chỉ qua multipart/form-data.
     */
    public function store(ThemKyNangRequest $request): JsonResponse
    {
        $nguoiDungId = auth()->id();
        $data = $request->validated();

        // Kiểm tra đã có kỹ năng này chưa
        $exists = NguoiDungKyNang::where('nguoi_dung_id', $nguoiDungId)
            ->where('ky_nang_id', $data['ky_nang_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã thêm kỹ năng này rồi.',
            ], 422);
        }

        // Upload hình ảnh chứng chỉ nếu có
        if ($request->hasFile('hinh_anh')) {
            $path = $request->file('hinh_anh')->store(
                "chung-chi/{$nguoiDungId}",
                'public'
            );
            $data['hinh_anh'] = $path;
        } else {
            unset($data['hinh_anh']);
        }

        $data['nguoi_dung_id'] = $nguoiDungId;
        $record = NguoiDungKyNang::create($data);

        $record->hinh_anh_url = $this->buildSkillImageUrl($record->hinh_anh);

        return response()->json([
            'success' => true,
            'message' => 'Thêm kỹ năng thành công.',
            'data' => $record->load('kyNang:id,ten_ky_nang,icon'),
        ], 201);
    }

    /**
     * PUT /api/v1/ung-vien/ky-nangs/{id}
     * Cập nhật mức độ / năm kinh nghiệm / chứng chỉ / ảnh chứng chỉ.
     *
     * Lưu ý: Nếu upload ảnh mới, cần gửi qua POST với _method=PUT (multipart/form-data).
     */
    public function update(CapNhatKyNangCaNhanRequest $request, int $id): JsonResponse
    {
        $nguoiDungId = auth()->id();

        $record = NguoiDungKyNang::where('nguoi_dung_id', $nguoiDungId)
            ->findOrFail($id);

        $data = $request->validated();

        // Upload hình ảnh mới (xoá ảnh cũ nếu có)
        if ($request->hasFile('hinh_anh')) {
            // Xoá ảnh cũ
            if ($record->hinh_anh && Storage::disk('public')->exists($record->hinh_anh)) {
                Storage::disk('public')->delete($record->hinh_anh);
            }

            $path = $request->file('hinh_anh')->store(
                "chung-chi/{$nguoiDungId}",
                'public'
            );
            $data['hinh_anh'] = $path;
        } else {
            unset($data['hinh_anh']);
        }

        $record->update($data);

        $fresh = $record->fresh()->load('kyNang:id,ten_ky_nang,icon');
        $fresh->hinh_anh_url = $this->buildSkillImageUrl($fresh->hinh_anh);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật kỹ năng thành công.',
            'data' => $fresh,
        ]);
    }

    /**
     * DELETE /api/v1/ung-vien/ky-nangs/{id}
     * Xoá kỹ năng khỏi hồ sơ cá nhân (kèm xoá ảnh chứng chỉ nếu có).
     */
    public function destroy(int $id): JsonResponse
    {
        $nguoiDungId = auth()->id();

        $record = NguoiDungKyNang::where('nguoi_dung_id', $nguoiDungId)
            ->findOrFail($id);

        // Xoá ảnh chứng chỉ khỏi storage
        if ($record->hinh_anh && Storage::disk('public')->exists($record->hinh_anh)) {
            Storage::disk('public')->delete($record->hinh_anh);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoá kỹ năng thành công.',
        ]);
    }

    public function hinhAnh(Request $request)
    {
        $path = (string) $request->query('path', '');

        abort_unless(
            $path !== '' && str_starts_with($path, 'chung-chi/'),
            404
        );

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }
}
