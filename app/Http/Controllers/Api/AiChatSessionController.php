<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\HoSo;
use App\Models\TinTuyenDung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AiChatSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = AiChatSession::query()
            ->with(['hoSo:id,tieu_de_ho_so', 'tinTuyenDung:id,tieu_de'])
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('session_type', 'career_consultant')
            ->latest('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'related_ho_so_id' => ['nullable', 'integer'],
            'related_tin_tuyen_dung_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $hoSoId = $validated['related_ho_so_id'] ?? null;
        $jobId = $validated['related_tin_tuyen_dung_id'] ?? null;

        if ($hoSoId) {
            HoSo::query()
                ->where('id', $hoSoId)
                ->where('nguoi_dung_id', $request->user()->id)
                ->firstOrFail();
        }

        if ($jobId) {
            TinTuyenDung::findOrFail($jobId);
        }

        $session = AiChatSession::create([
            'nguoi_dung_id' => $request->user()->id,
            'session_type' => 'career_consultant',
            'related_ho_so_id' => $hoSoId,
            'related_tin_tuyen_dung_id' => $jobId,
            'title' => $validated['title'] ?? 'Tư vấn nghề nghiệp',
            'status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $session,
        ], 201);
    }

    public function messages(Request $request, int $id): JsonResponse
    {
        $session = AiChatSession::query()
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->where('id', $id)
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('session_type', 'career_consultant')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $session->messages,
        ]);
    }

    public function clearMessages(Request $request, int $id): JsonResponse
    {
        $session = AiChatSession::query()
            ->where('id', $id)
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('session_type', 'career_consultant')
            ->firstOrFail();

        $deletedCount = AiChatMessage::query()
            ->where('session_id', $session->id)
            ->delete();

        $sessionId = $session->id;
        Cache::store('file')->forget($this->baseContextCacheKey($request->user()->id, $session));
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa phiên chat thành công.',
            'data' => [
                'session_id' => $sessionId,
                'deleted_count' => $deletedCount,
                'session_deleted' => true,
            ],
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', 'in:0,1,2'],
            'summary' => ['nullable', 'string'],
        ]);

        $session = AiChatSession::query()
            ->where('id', $id)
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('session_type', 'career_consultant')
            ->firstOrFail();

        $session->update([
            'status' => (int) $validated['status'],
            'summary' => $validated['summary'] ?? $session->summary,
        ]);
        Cache::store('file')->forget($this->baseContextCacheKey($request->user()->id, $session));

        return response()->json([
            'success' => true,
            'data' => $session->fresh(['hoSo:id,tieu_de_ho_so', 'tinTuyenDung:id,tieu_de']),
        ]);
    }

    private function baseContextCacheKey(int $nguoiDungId, AiChatSession $session): string
    {
        return implode(':', [
            'ai-chat-context',
            $nguoiDungId,
            $session->id,
            $session->related_ho_so_id ?: 'none',
            $session->related_tin_tuyen_dung_id ?: 'none',
        ]);
    }
}
