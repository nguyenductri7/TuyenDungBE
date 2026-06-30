<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\HoSo;
use App\Models\KetQuaMatching;
use App\Models\KyNang;
use App\Models\NganhNghe;
use App\Models\SuDungTinhNangAi;
use App\Models\TinTuyenDung;
use App\Services\Ai\AiClientService;
use App\Services\Billing\FeatureAccessService;
use App\Support\ApiErrorMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatMessageController extends Controller
{
    public function store(
        Request $request,
        AiClientService $aiClient,
        FeatureAccessService $featureAccessService
    ): JsonResponse
    {
        /** @var SuDungTinhNangAi|null $billingUsage */
        $billingUsage = null;

        $validated = $this->validateMessagePayload($request);
        $session = $this->resolveActiveSession($request->user()->id, (int) $validated['session_id']);

        try {
            $billingUsage = $featureAccessService->beginUsage(
                $request->user(),
                'chatbot_message',
                'ai_chat_session',
                $session->id,
                [
                    'session_id' => $session->id,
                    'mode' => 'sync',
                    'message_length' => mb_strlen((string) $validated['message']),
                ],
                $this->resolveIdempotencyKey($request, $session->id),
            );

            $userMessage = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'content' => $validated['message'],
                'metadata' => null,
                'created_at' => now(),
            ]);

            $history = $this->buildHistory($session->id);
            $context = $this->buildContext($request->user()->id, $session, (string) $validated['message']);
            $response = $aiClient->careerChat(
                $session->id,
                $validated['message'],
                $history,
                $context,
                (bool) ($validated['force_model'] ?? false)
            );

            $assistantData = $response['data'] ?? [];
            $assistantMessage = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => (string) ($assistantData['answer'] ?? ''),
                'metadata' => [
                    'provider' => $assistantData['provider'] ?? null,
                    'guardrail_triggered' => $assistantData['guardrail_triggered'] ?? false,
                    'model_version' => $response['model_version'] ?? null,
                    'intent' => $assistantData['intent'] ?? null,
                ],
                'created_at' => now(),
            ]);
            $this->refreshSessionSummary(
                $session,
                $context,
                (string) $validated['message'],
                $assistantMessage->content,
                $assistantData['intent'] ?? null
            );
            $billingUsage = $featureAccessService->commitUsage($billingUsage, [
                'assistant_message_id' => $assistantMessage->id,
            ]);
        } catch (BillingException $exception) {
            return response()->json([
                'success' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                ...$exception->context,
            ], $exception->status);
        } catch (\Throwable $exception) {
            if ($billingUsage) {
                $this->safeFailUsage($featureAccessService, $billingUsage, $exception->getMessage());
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'model_version' => $response['model_version'] ?? null,
            ],
        ], 201);
    }

    public function stream(
        Request $request,
        AiClientService $aiClient,
        FeatureAccessService $featureAccessService
    ): StreamedResponse|JsonResponse
    {
        /** @var SuDungTinhNangAi|null $billingUsage */
        $billingUsage = null;

        $validated = $this->validateMessagePayload($request);
        $session = $this->resolveActiveSession($request->user()->id, (int) $validated['session_id']);

        try {
            $billingUsage = $featureAccessService->beginUsage(
                $request->user(),
                'chatbot_message',
                'ai_chat_session',
                $session->id,
                [
                    'session_id' => $session->id,
                    'mode' => 'stream',
                    'message_length' => mb_strlen((string) $validated['message']),
                ],
                $this->resolveIdempotencyKey($request, $session->id),
            );
        } catch (BillingException $exception) {
            return response()->json([
                'success' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                ...$exception->context,
            ], $exception->status);
        }

        try {
            $userMessage = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'content' => $validated['message'],
                'metadata' => null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $this->safeFailUsage($featureAccessService, $billingUsage, $exception->getMessage());
            throw $exception;
        }

        $history = $this->buildHistory($session->id);
        $context = $this->buildContext($request->user()->id, $session, (string) $validated['message']);
        $userMessagePayload = $userMessage->toArray();

        return response()->stream(function () use (
            $userMessagePayload,
            $session,
            $validated,
            $history,
            $context,
            $aiClient,
            $featureAccessService,
            $billingUsage
        ): void {
            $donePayload = null;
            $partialAnswer = '';
            $streamMeta = [];

            try {
                try {
                    $aiClient->careerChatStream(
                        $session->id,
                        $validated['message'],
                        $history,
                        $context,
                        (bool) ($validated['force_model'] ?? false),
                        function (string $event, array $payload) use (&$donePayload, &$partialAnswer, &$streamMeta, $userMessagePayload): void {
                            if ($event === 'done') {
                                $donePayload = $payload;
                                return;
                            }

                            if ($event === 'meta') {
                                $streamMeta = $payload;
                                $payload['user_message'] = $userMessagePayload;
                            }

                            if ($event === 'chunk' && !empty($payload['content'])) {
                                $partialAnswer .= (string) $payload['content'];
                            }

                            echo $this->sseEvent($event, $payload);
                            @ob_flush();
                            @flush();
                        }
                    );
                } catch (\Throwable $e) {
                    $aiClient->recordFallback(
                        'career_chat_stream',
                        $e->getMessage(),
                        ['session_id' => $session->id],
                        ['fallback_mode' => 'non_stream']
                    );
                    $donePayload = $this->fallbackToNonStream(
                        $aiClient,
                        $session->id,
                        $validated['message'],
                        $history,
                        $context,
                        (bool) ($validated['force_model'] ?? false)
                    );
                }

                if (!is_array($donePayload) || empty($donePayload['answer'])) {
                    $fallbackAnswer = $this->normalizeAssistantAnswer($partialAnswer);
                    if ($fallbackAnswer !== '') {
                        $donePayload = [
                            'answer' => $fallbackAnswer,
                            'model_version' => $streamMeta['model_version'] ?? null,
                            'provider' => $streamMeta['provider'] ?? null,
                            'guardrail_triggered' => $streamMeta['guardrail_triggered'] ?? false,
                            'intent' => $streamMeta['intent'] ?? null,
                        ];
                    } else {
                        $donePayload = $this->fallbackToNonStream(
                            $aiClient,
                            $session->id,
                            $validated['message'],
                            $history,
                            $context,
                            (bool) ($validated['force_model'] ?? false)
                        );
                        if (!is_array($donePayload) || empty($donePayload['answer'])) {
                            $donePayload = $this->buildGracefulStreamFallbackPayload(
                                (string) $validated['message'],
                                $streamMeta
                            );
                        }
                    }
                }

                $assistantMessage = AiChatMessage::create([
                    'session_id' => $session->id,
                    'role' => 'assistant',
                    'content' => (string) ($donePayload['answer'] ?? ''),
                    'metadata' => [
                        'provider' => $donePayload['provider'] ?? null,
                        'guardrail_triggered' => $donePayload['guardrail_triggered'] ?? false,
                        'model_version' => $donePayload['model_version'] ?? null,
                        'intent' => $donePayload['intent'] ?? null,
                        'stream_mode' => 'provider_sse',
                    ],
                    'created_at' => now(),
                ]);
                $this->refreshSessionSummary(
                    $session,
                    $context,
                    (string) $validated['message'],
                    $assistantMessage->content,
                    $donePayload['intent'] ?? null
                );
                $featureAccessService->commitUsage($billingUsage, [
                    'assistant_message_id' => $assistantMessage->id,
                    'stream_fallback_used' => (($donePayload['provider'] ?? null) === 'graceful_stream_fallback'),
                ]);

                echo $this->sseEvent('done', [
                    'assistant_message' => $assistantMessage->toArray(),
                    'model_version' => $donePayload['model_version'] ?? null,
                    'provider' => $donePayload['provider'] ?? null,
                    'guardrail_triggered' => $donePayload['guardrail_triggered'] ?? false,
                    'intent' => $donePayload['intent'] ?? null,
                    'answer' => $assistantMessage->content,
                ]);
                @ob_flush();
                @flush();
            } catch (\Throwable $exception) {
                $this->safeFailUsage($featureAccessService, $billingUsage, $exception->getMessage());

                echo $this->sseEvent('error', [
                    'message' => ApiErrorMessage::fromThrowable($exception),
                ]);
                @ob_flush();
                @flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function buildContext(int $nguoiDungId, AiChatSession $session, string $message): array
    {
        $baseContext = Cache::store('file')->remember(
            $this->baseContextCacheKey($nguoiDungId, $session),
            now()->addMinutes(5),
            function () use ($nguoiDungId, $session): array {
                $candidateProfile = [];
                $topMatches = [];
                $relatedJob = null;
                $ragContext = [];

                if ($session->related_ho_so_id) {
                    $hoSo = HoSo::query()
                        ->with(['parsing', 'nguoiDung:id,ho_ten'])
                        ->where('id', $session->related_ho_so_id)
                        ->where('nguoi_dung_id', $nguoiDungId)
                        ->first();

                    if ($hoSo) {
                        $candidateProfile = [
                            'ho_ten' => $hoSo->nguoiDung?->ho_ten,
                            'parsed_name' => $hoSo->parsing?->parsed_name,
                            'tieu_de_ho_so' => $hoSo->tieu_de_ho_so,
                            'vi_tri_ung_tuyen_muc_tieu' => $hoSo->vi_tri_ung_tuyen_muc_tieu,
                            'ten_nganh_nghe_muc_tieu' => $hoSo->ten_nganh_nghe_muc_tieu,
                            'kinh_nghiem_nam' => $hoSo->kinh_nghiem_nam,
                            'trinh_do' => $hoSo->trinh_do,
                            'muc_tieu_nghe_nghiep' => $hoSo->muc_tieu_nghe_nghiep,
                            'builder_skills' => collect($hoSo->ky_nang_json ?? [])
                                ->map(fn ($item) => is_array($item) ? ($item['ten'] ?? $item['name'] ?? $item['skill_name'] ?? null) : $item)
                                ->filter()
                                ->values()
                                ->all(),
                            'parsed_skills' => collect($hoSo->parsing?->parsed_skills_json ?? [])
                                ->map(fn ($item) => is_array($item) ? ($item['skill_name'] ?? null) : $item)
                                ->filter()
                                ->values()
                                ->all(),
                        ];

                        $topMatchQuery = KetQuaMatching::query()
                            ->with('tinTuyenDung:id,tieu_de')
                            ->where('ho_so_id', $hoSo->id)
                            ->orderByDesc('diem_phu_hop');

                        if ($session->related_tin_tuyen_dung_id) {
                            $topMatchQuery->where('tin_tuyen_dung_id', $session->related_tin_tuyen_dung_id);
                        }

                        $topMatches = $topMatchQuery
                            ->limit($session->related_tin_tuyen_dung_id ? 1 : 2)
                            ->get()
                            ->map(function (KetQuaMatching $item): array {
                                return [
                                    'job_title' => $item->tinTuyenDung?->tieu_de ?? 'Vị trí phù hợp',
                                    'score' => $item->diem_phu_hop,
                                    'matched_skills' => collect($item->matched_skills_json ?? [])
                                        ->map(fn ($skill) => is_array($skill) ? ($skill['skill_name'] ?? null) : $skill)
                                        ->filter()
                                        ->values()
                                        ->all(),
                                    'missing_skills' => collect($item->missing_skills_json ?? [])
                                        ->map(fn ($skill) => is_array($skill) ? ($skill['skill_name'] ?? null) : $skill)
                                        ->filter()
                                        ->values()
                                        ->all(),
                                    'explanation' => $item->explanation,
                                ];
                            })
                            ->all();
                    }
                }

                if ($session->related_tin_tuyen_dung_id) {
                    $job = TinTuyenDung::query()->with(['parsing', 'kyNangYeuCaus.kyNang:id,ten_ky_nang'])->find($session->related_tin_tuyen_dung_id);
                    if ($job) {
                        $relatedJob = [
                            'title' => $job->tieu_de,
                            'location' => $job->dia_diem_lam_viec,
                            'level' => $job->cap_bac,
                            'salary_from' => $job->muc_luong_tu,
                            'salary_to' => $job->muc_luong_den,
                            'salary_unit' => $job->don_vi_luong,
                            'work_mode' => $job->hinh_thuc_lam_viec,
                            'description_excerpt' => $this->shortenText((string) $job->mo_ta_cong_viec, 500),
                            'skills' => $job->kyNangYeuCaus
                                ->pluck('kyNang.ten_ky_nang')
                                ->filter()
                                ->values()
                                ->all(),
                        ];
                    }
                }

                $ragContext = $this->buildLightRagContext($candidateProfile, $relatedJob, $topMatches);

                return [
                    'candidate_profile' => $candidateProfile,
                    'top_matching_jobs' => $topMatches,
                    'related_job' => $relatedJob,
                    'rag_context' => $ragContext,
                ];
            }
        );

        $candidateProfile = $baseContext['candidate_profile'] ?? [];
        $topMatches = $baseContext['top_matching_jobs'] ?? [];
        $relatedJob = $baseContext['related_job'] ?? null;
        $ragContext = $baseContext['rag_context'] ?? [];

        return [
            'candidate_profile' => $candidateProfile,
            'top_matching_jobs' => $topMatches,
            'related_job' => $relatedJob,
            'rag_context' => $ragContext,
            'conversation_summary' => $session->summary,
        ];
    }

    private function buildLightRagContext(array $candidateProfile, ?array $relatedJob, array $topMatches): array
    {
        $skillHints = collect([
                ...($candidateProfile['parsed_skills'] ?? []),
                ...($candidateProfile['builder_skills'] ?? []),
                ...collect($topMatches)->flatMap(fn ($item) => [
                    ...($item['matched_skills'] ?? []),
                    ...($item['missing_skills'] ?? []),
                ])->all(),
                ...($relatedJob['skills'] ?? []),
            ])
            ->filter()
            ->unique(fn ($value) => Str::lower((string) $value))
            ->take(10)
            ->values()
            ->all();

        $jobQuery = TinTuyenDung::query()
            ->with(['congTy:id,ten_cong_ty', 'kyNangYeuCaus.kyNang:id,ten_ky_nang'])
            ->where('trang_thai', TinTuyenDung::TRANG_THAI_HOAT_DONG)
            ->latest('updated_at')
            ->limit(8);

        if ($skillHints !== []) {
            $jobQuery->where(function ($query) use ($skillHints) {
                foreach (array_slice($skillHints, 0, 5) as $skill) {
                    $query->orWhere('mo_ta_cong_viec', 'like', '%' . $skill . '%')
                        ->orWhere('tieu_de', 'like', '%' . $skill . '%');
                }
            });
        }

        $jobs = $jobQuery->get()
            ->map(fn (TinTuyenDung $job) => [
                'id' => $job->id,
                'title' => $job->tieu_de,
                'company' => $job->congTy?->ten_cong_ty,
                'location' => $job->dia_diem_lam_viec,
                'work_mode' => $job->hinh_thuc_lam_viec,
                'salary_range' => array_values(array_filter([$job->muc_luong_tu, $job->muc_luong_den])),
                'skills' => $job->kyNangYeuCaus
                    ->pluck('kyNang.ten_ky_nang')
                    ->filter()
                    ->take(6)
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return [
            'source' => 'database_light_context',
            'candidate_skill_hints' => $skillHints,
            'job_snippets' => $jobs,
            'industry_hints' => NganhNghe::query()
                ->select('id', 'ten_nganh')
                ->orderBy('ten_nganh')
                ->limit(12)
                ->get()
                ->map(fn (NganhNghe $item) => ['id' => $item->id, 'name' => $item->ten_nganh])
                ->values()
                ->all(),
            'skill_catalog_hints' => KyNang::query()
                ->select('id', 'ten_ky_nang')
                ->when($skillHints !== [], function ($query) use ($skillHints) {
                    $query->where(function ($inner) use ($skillHints) {
                        foreach (array_slice($skillHints, 0, 6) as $skill) {
                            $inner->orWhere('ten_ky_nang', 'like', '%' . $skill . '%');
                        }
                    });
                })
                ->orderBy('ten_ky_nang')
                ->limit(20)
                ->get()
                ->map(fn (KyNang $item) => ['id' => $item->id, 'name' => $item->ten_ky_nang])
                ->values()
                ->all(),
        ];
    }

    private function validateMessagePayload(Request $request): array
    {
        return $request->validate([
            'session_id' => ['required', 'integer'],
            'message' => ['required', 'string', 'min:2'],
            'force_model' => ['nullable', 'boolean'],
        ]);
    }

    private function resolveActiveSession(int $nguoiDungId, int $sessionId): AiChatSession
    {
        return AiChatSession::query()
            ->where('id', $sessionId)
            ->where('nguoi_dung_id', $nguoiDungId)
            ->where('session_type', 'career_consultant')
            ->where('status', 1)
            ->firstOrFail();
    }

    private function buildHistory(int $sessionId): array
    {
        return AiChatMessage::query()
            ->where('session_id', $sessionId)
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (AiChatMessage $item) => [
                'role' => $item->role,
                'content' => $item->content,
                'intent' => $item->metadata['intent'] ?? null,
            ])
            ->all();
    }

    private function resolveIdempotencyKey(Request $request, int $sessionId): string
    {
        $headerKey = trim((string) $request->header('X-Idempotency-Key', ''));
        if ($headerKey !== '') {
            return $headerKey;
        }

        return 'chatbot:' . $request->user()->id . ':' . $sessionId . ':' . Str::uuid();
    }

    public function index(Request $request, int $sessionId): JsonResponse
    {
        $session = $this->resolveActiveSession($request->user()->id, $sessionId);

        $messages = AiChatMessage::query()
            ->where('session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    private function sseEvent(string $event, array $payload): string
    {
        return "event: {$event}\n" .
            'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    }

    private function normalizeAssistantAnswer(string $text): string
    {
        $cleaned = trim(str_replace(['**', '__', '`', '#'], '', $text));
        $lines = preg_split('/\R/u', $cleaned) ?: [];
        $normalizedLines = [];
        $previousBlank = false;

        foreach ($lines as $rawLine) {
            $line = preg_replace('/[ \t]+/u', ' ', trim($rawLine)) ?? '';
            if ($line === '') {
                if (!$previousBlank && !empty($normalizedLines)) {
                    $normalizedLines[] = '';
                }
                $previousBlank = true;
                continue;
            }

            $normalizedLines[] = $line;
            $previousBlank = false;
        }

        $cleaned = trim(implode("\n", $normalizedLines));

        if ($cleaned === '') {
            return '';
        }

        if (preg_match('/[.!?]$/u', $cleaned)) {
            return $cleaned;
        }

        $lastStop = max(
            strrpos($cleaned, '.'),
            strrpos($cleaned, '!'),
            strrpos($cleaned, '?')
        );

        if ($lastStop !== false && $lastStop >= (int) (strlen($cleaned) * 0.6)) {
            return trim(substr($cleaned, 0, $lastStop + 1));
        }

        return $cleaned . '.';
    }

    private function fallbackToNonStream(
        AiClientService $aiClient,
        int $sessionId,
        string $message,
        array $history,
        array $context,
        bool $forceModel
    ): ?array {
        try {
            $response = $aiClient->careerChat($sessionId, $message, $history, $context, $forceModel);
            $data = $response['data'] ?? [];
            $answer = $this->normalizeAssistantAnswer((string) ($data['answer'] ?? ''));

            if ($answer === '') {
                return null;
            }

            return [
                'answer' => $answer,
                'model_version' => $response['model_version'] ?? null,
                'provider' => $data['provider'] ?? null,
                'guardrail_triggered' => $data['guardrail_triggered'] ?? false,
                'intent' => $data['intent'] ?? null,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildGracefulStreamFallbackPayload(string $message, array $streamMeta = []): array
    {
        $trimmedMessage = trim($message);
        $fallbackAnswer = 'Mình chưa thể hoàn tất phản hồi AI ở chế độ stream lúc này. ';

        if ($trimmedMessage !== '') {
            $fallbackAnswer .= 'Bạn có thể gửi lại câu hỏi';

            if (mb_strlen($trimmedMessage) <= 80) {
                $fallbackAnswer .= ' "' . $trimmedMessage . '"';
            }

            $fallbackAnswer .= ' hoặc tắt SSE để nhận phản hồi ổn định hơn.';
        } else {
            $fallbackAnswer .= 'Bạn có thể gửi lại câu hỏi hoặc tắt SSE để nhận phản hồi ổn định hơn.';
        }

        return [
            'answer' => $fallbackAnswer,
            'model_version' => $streamMeta['model_version'] ?? null,
            'provider' => $streamMeta['provider'] ?? 'graceful_stream_fallback',
            'guardrail_triggered' => false,
            'intent' => $streamMeta['intent'] ?? null,
        ];
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

    private function refreshSessionSummary(
        AiChatSession $session,
        array $context,
        string $latestQuestion,
        string $latestAnswer,
        ?string $latestIntent = null
    ): void
    {
        $summary = $this->buildSessionSummary($context, $latestQuestion, $latestAnswer, $latestIntent);
        if ($summary === '') {
            return;
        }

        $session->forceFill([
            'summary' => $summary,
        ])->save();
    }

    private function buildSessionSummary(array $context, string $latestQuestion, string $latestAnswer, ?string $latestIntent = null): string
    {
        $relatedJob = $context['related_job'] ?? [];
        $topMatches = $context['top_matching_jobs'] ?? [];
        $candidateProfile = $context['candidate_profile'] ?? [];

        $parts = [];

        $intentLabel = $latestIntent ? $this->intentCodeToLabel($latestIntent) : $this->summarizeIntentLabel($latestQuestion);
        if ($intentLabel !== null) {
            $parts[] = 'Chủ đề gần nhất: ' . $intentLabel . '.';
        }

        if (!empty($relatedJob['title'])) {
            $parts[] = 'Job đang tham chiếu: ' . $relatedJob['title'] . '.';
        } elseif (!empty($candidateProfile['vi_tri_ung_tuyen_muc_tieu'])) {
            $parts[] = 'Vị trí mục tiêu từ CV: ' . $candidateProfile['vi_tri_ung_tuyen_muc_tieu'] . '.';
        }

        $skills = array_slice($candidateProfile['parsed_skills'] ?? [], 0, 4);
        if (!empty($skills)) {
            $parts[] = 'Kỹ năng nền hiện có: ' . implode(', ', $skills) . '.';
        }

        $missingSkills = [];
        foreach ($topMatches as $match) {
            foreach (($match['missing_skills'] ?? []) as $skill) {
                if (is_string($skill) && $skill !== '' && !in_array($skill, $missingSkills, true)) {
                    $missingSkills[] = $skill;
                }
            }
        }
        if (!empty($missingSkills)) {
            $parts[] = 'Kỹ năng còn thiếu nổi bật: ' . implode(', ', array_slice($missingSkills, 0, 4)) . '.';
        }

        $answerSummary = $this->shortenText($latestAnswer, 220);
        if ($answerSummary !== '') {
            $parts[] = 'Kết luận gần nhất: ' . $answerSummary;
        }

        return trim(implode(' ', $parts));
    }

    private function safeFailUsage(
        FeatureAccessService $featureAccessService,
        SuDungTinhNangAi $usage,
        string $reason
    ): void {
        try {
            $featureAccessService->failUsage($usage, $reason);
        } catch (\Throwable) {
            // Không làm hỏng response chính nếu rollback billing gặp lỗi.
        }
    }

    private function summarizeIntentLabel(string $message): ?string
    {
        $normalized = mb_strtolower($message);

        $map = [
            'giải thích matching' => ['matching', 'điểm', 'vì sao'],
            'thiếu kỹ năng' => ['thiếu kỹ năng', 'thieu ky nang', 'học gì', 'hoc gi', 'bổ sung'],
            'gợi ý công việc' => ['job nào', 'job nao', 'công việc', 'cong viec', 'ứng tuyển'],
            'định hướng nghề nghiệp' => ['nên theo', 'hướng khác', 'định hướng', 'hướng chính', 'hướng thay thế'],
            'lộ trình học' => ['kế hoạch', 'lộ trình', '3 tháng', '6 tháng', 'giai đoạn'],
            'cải thiện CV' => ['cv', 'hồ sơ', 'ho so', 'sửa', 'chỉnh'],
            'chuẩn bị phỏng vấn' => ['phỏng vấn', 'phong van', 'interview'],
        ];

        foreach ($map as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $label;
                }
            }
        }

        return null;
    }

    private function intentCodeToLabel(string $intent): ?string
    {
        $map = [
            'matching_explanation' => 'giải thích matching',
            'skill_gap' => 'thiếu kỹ năng',
            'job_recommendation' => 'gợi ý công việc',
            'career_direction' => 'định hướng nghề nghiệp',
            'learning_plan' => 'lộ trình học',
            'cv_improvement' => 'cải thiện CV',
            'interview_prep' => 'chuẩn bị phỏng vấn',
            'cover_letter' => 'thư xin việc',
            'next_step_action' => 'nên làm gì trước',
            'general_career' => 'tư vấn nghề nghiệp',
        ];

        return $map[$intent] ?? null;
    }

    private function shortenText(string $text, int $maxLength): string
    {
        $cleaned = trim(preg_replace('/\s+/u', ' ', str_replace(['**', '__', '`', '#'], '', $text)) ?? '');
        if ($cleaned === '') {
            return '';
        }

        if (mb_strlen($cleaned) <= $maxLength) {
            return $cleaned;
        }

        return rtrim(mb_substr($cleaned, 0, $maxLength - 1)) . '…';
    }
}
