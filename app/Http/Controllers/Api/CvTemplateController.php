<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CvTemplate;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CvTemplateController extends Controller
{
    private function mapTemplate(CvTemplate $template): array
    {
        return [
            'id' => $template->id,
            'ma_template' => $template->ma_template,
            'ten_template' => $template->ten_template,
            'mo_ta' => $template->mo_ta,
            'bo_cuc' => $template->bo_cuc,
            'badges' => $template->badges_json ?: [],
            'trang_thai' => (int) $template->trang_thai,
            'thu_tu_hien_thi' => (int) $template->thu_tu_hien_thi,
            'created_at' => optional($template->created_at)?->toISOString(),
            'updated_at' => optional($template->updated_at)?->toISOString(),
        ];
    }

    private function templateAuditSnapshot(CvTemplate $template): array
    {
        return [
            'id' => $template->id,
            'ma_template' => $template->ma_template,
            'ten_template' => $template->ten_template,
            'bo_cuc' => $template->bo_cuc,
            'trang_thai' => (int) $template->trang_thai,
            'thu_tu_hien_thi' => (int) $template->thu_tu_hien_thi,
        ];
    }

    private function validatePayload(Request $request, ?CvTemplate $template = null): array
    {
        $templateId = $template?->id;

        return $request->validate([
            'ma_template' => ['required', 'string', 'max:100', 'unique:cv_templates,ma_template' . ($templateId ? ',' . $templateId : '')],
            'ten_template' => ['required', 'string', 'max:150'],
            'mo_ta' => ['nullable', 'string'],
            'bo_cuc' => ['required', 'string', 'in:' . implode(',', CvTemplate::BO_CUC_LIST)],
            'badges' => ['nullable', 'array'],
            'badges.*' => ['nullable', 'string', 'max:80'],
            'trang_thai' => ['nullable', 'integer', 'in:0,1'],
            'thu_tu_hien_thi' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    public function publicIndex(): JsonResponse
    {
        $templates = CvTemplate::query()
            ->where('trang_thai', CvTemplate::TRANG_THAI_HIEN)
            ->orderBy('thu_tu_hien_thi')
            ->orderBy('id')
            ->get()
            ->map(fn (CvTemplate $template) => $this->mapTemplate($template))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = CvTemplate::query()->orderBy('thu_tu_hien_thi')->orderBy('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($inner) use ($search) {
                $inner->where('ten_template', 'like', "%{$search}%")
                    ->orWhere('ma_template', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate((int) $request->get('per_page', 20));
        $templates->setCollection(
            $templates->getCollection()->map(fn (CvTemplate $template) => $this->mapTemplate($template))
        );

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        $template = CvTemplate::create([
            'ma_template' => trim((string) $data['ma_template']),
            'ten_template' => trim((string) $data['ten_template']),
            'mo_ta' => $data['mo_ta'] ?? null,
            'bo_cuc' => $data['bo_cuc'],
            'badges_json' => array_values(array_filter($data['badges'] ?? [])),
            'trang_thai' => (int) ($data['trang_thai'] ?? CvTemplate::TRANG_THAI_HIEN),
            'thu_tu_hien_thi' => (int) ($data['thu_tu_hien_thi'] ?? 0),
        ]);
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_cv_template_created',
            description: "Admin tạo template CV {$template->ten_template}.",
            target: $template,
            after: $this->templateAuditSnapshot($template),
            metadata: ['scope' => 'admin_cv_template'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo template CV thành công.',
            'data' => $this->mapTemplate($template),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = CvTemplate::findOrFail($id);
        $data = $this->validatePayload($request, $template);
        $before = $this->templateAuditSnapshot($template);

        $template->update([
            'ma_template' => trim((string) $data['ma_template']),
            'ten_template' => trim((string) $data['ten_template']),
            'mo_ta' => $data['mo_ta'] ?? null,
            'bo_cuc' => $data['bo_cuc'],
            'badges_json' => array_values(array_filter($data['badges'] ?? [])),
            'trang_thai' => (int) ($data['trang_thai'] ?? $template->trang_thai),
            'thu_tu_hien_thi' => (int) ($data['thu_tu_hien_thi'] ?? $template->thu_tu_hien_thi),
        ]);
        app(AuditLogService::class)->logModelAction(
            actor: $request->user(),
            action: 'admin_cv_template_updated',
            description: "Admin cập nhật template CV {$template->ten_template}.",
            target: $template,
            before: $before,
            after: $this->templateAuditSnapshot($template->fresh()),
            metadata: ['scope' => 'admin_cv_template'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật template CV thành công.',
            'data' => $this->mapTemplate($template->fresh()),
        ]);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $template = CvTemplate::findOrFail($id);
        $before = $this->templateAuditSnapshot($template);
        $template->trang_thai = (int) $template->trang_thai === CvTemplate::TRANG_THAI_HIEN
            ? CvTemplate::TRANG_THAI_AN
            : CvTemplate::TRANG_THAI_HIEN;
        $template->save();
        app(AuditLogService::class)->logModelAction(
            actor: auth()->user(),
            action: 'admin_cv_template_status_toggled',
            description: "Admin đổi trạng thái template CV {$template->ten_template}.",
            target: $template,
            before: $before,
            after: $this->templateAuditSnapshot($template),
            metadata: ['scope' => 'admin_cv_template'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã đổi trạng thái template CV.',
            'data' => $this->mapTemplate($template),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $template = CvTemplate::findOrFail($id);
        $before = $this->templateAuditSnapshot($template);
        $template->delete();
        app(AuditLogService::class)->logModelAction(
            actor: auth()->user(),
            action: 'admin_cv_template_deleted',
            description: "Admin xóa template CV {$before['ten_template']}.",
            target: $template,
            before: $before,
            metadata: ['scope' => 'admin_cv_template'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa template CV.',
        ]);
    }
}
