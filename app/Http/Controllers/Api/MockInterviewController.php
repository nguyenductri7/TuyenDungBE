<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiInterviewReport;
use App\Models\HoSo;
use App\Models\KetQuaMatching;
use App\Models\SuDungTinhNangAi;
use App\Models\TinTuyenDung;
use App\Services\Ai\AiClientService;
use App\Services\Billing\FeatureAccessService;
use App\Support\ApiErrorMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MockInterviewController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $reports = AiInterviewReport::query()
            ->with(['session:id,title,status', 'tinTuyenDung:id,tieu_de'])
            ->where('nguoi_dung_id', $request->user()->id)
            ->latest('created_at')
            ->get();

        $recentReports = $reports->take(6);
        $latestReport = $reports->first();
        $previousReport = $reports->skip(1)->first();
        $rubricAverages = $this->buildAverageRubricSummary($reports->all());
        $weakestDimensionDistribution = $this->buildWeakestDimensionDistribution($reports->all());

        return response()->json([
            'success' => true,
            'data' => [
                'total_reports' => $reports->count(),
                'completed_sessions' => AiChatSession::query()
                    ->where('nguoi_dung_id', $request->user()->id)
                    ->where('session_type', 'mock_interview')
                    ->where('status', 2)
                    ->count(),
                'average_overall_score' => round((float) $reports->avg('tong_diem'), 2),
                'latest_overall_score' => $latestReport?->tong_diem,
                'score_delta_from_previous' => $latestReport && $previousReport
                    ? round(((float) $latestReport->tong_diem) - ((float) $previousReport->tong_diem), 2)
                    : null,
                'average_rubric_summary' => $rubricAverages,
                'weakest_dimension_distribution' => $weakestDimensionDistribution,
                'latest_focus' => $latestReport?->metadata['structured_improvement']['priority_actions'] ?? [],
                'timeline' => $recentReports->reverse()->values()->map(function (AiInterviewReport $report): array {
                    return [
                        'report_id' => $report->id,
                        'session_id' => $report->session_id,
                        'title' => $report->session?->title ?? 'Mock Interview',
                        'job_title' => $report->tinTuyenDung?->tieu_de,
                        'overall_score' => $report->tong_diem,
                        'created_at' => optional($report->created_at)->toISOString(),
                    ];
                })->all(),
                'recent_reports' => $recentReports->map(function (AiInterviewReport $report): array {
                    return [
                        'report_id' => $report->id,
                        'session_id' => $report->session_id,
                        'title' => $report->session?->title ?? 'Mock Interview',
                        'job_title' => $report->tinTuyenDung?->tieu_de,
                        'overall_score' => $report->tong_diem,
                        'role_family' => $report->metadata['role_family'] ?? 'general',
                        'weakest_dimension' => $report->metadata['weakest_dimension'] ?? null,
                        'created_at' => optional($report->created_at)->toISOString(),
                    ];
                })->all(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $sessions = AiChatSession::query()
            ->with(['hoSo:id,tieu_de_ho_so', 'tinTuyenDung:id,tieu_de'])
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('session_type', 'mock_interview')
            ->latest('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    public function store(
        Request $request,
        AiClientService $aiClient,
        FeatureAccessService $featureAccessService
    ): JsonResponse
    {
        /** @var SuDungTinhNangAi|null $billingUsage */
        $billingUsage = null;
        /** @var AiChatSession|null $session */
        $session = null;

        $validated = $request->validate([
            'related_ho_so_id' => ['required', 'integer'],
            'related_tin_tuyen_dung_id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'auto_generate_first_question' => ['nullable', 'boolean'],
            'question_count' => ['nullable', 'integer', 'min:2'],
        ], [
            'related_tin_tuyen_dung_id.required' => 'Vui lòng chọn tin tuyển dụng mục tiêu trước khi tạo phiên phỏng vấn.',
            'question_count.min' => 'Số câu hỏi phỏng vấn tối thiểu là 2 câu.',
        ]);

        $hoSo = HoSo::query()
            ->where('id', (int) $validated['related_ho_so_id'])
            ->where('nguoi_dung_id', $request->user()->id)
            ->firstOrFail();

        $jobId = (int) $validated['related_tin_tuyen_dung_id'];
        TinTuyenDung::findOrFail($jobId);

        try {
            $billingUsage = $featureAccessService->beginUsage(
                $request->user(),
                'mock_interview_session',
                'mock_interview_session',
                null,
                [
                    'ho_so_id' => $hoSo->id,
                    'tin_tuyen_dung_id' => $jobId,
                    'question_count' => (int) ($validated['question_count'] ?? 5),
                    'auto_generate_first_question' => (bool) ($validated['auto_generate_first_question'] ?? true),
                ],
                $this->resolveIdempotencyKey($request, $hoSo->id, $jobId),
            );

            $session = AiChatSession::create([
                'nguoi_dung_id' => $request->user()->id,
                'session_type' => 'mock_interview',
                'related_ho_so_id' => $hoSo->id,
                'related_tin_tuyen_dung_id' => $jobId,
                'title' => $validated['title'] ?? 'Mock Interview',
                'status' => 1,
                'metadata' => [
                    'question_count' => (int) ($validated['question_count'] ?? 5),
                ],
            ]);

            $firstQuestionMessage = null;
            if (($validated['auto_generate_first_question'] ?? true) === true) {
                $context = $this->buildInterviewContext($request->user()->id, $session);
                $response = $aiClient->generateMockInterviewQuestion(
                    $session->id,
                    $context,
                    [],
                    1,
                    $this->resolveQuestionCount($session)
                );
                $data = $response['data'] ?? [];

                $firstQuestionMessage = AiChatMessage::create([
                    'session_id' => $session->id,
                    'role' => 'assistant',
                    'content' => (string) ($data['question_text'] ?? ''),
                    'metadata' => [
                        'type' => 'interview_question',
                        'question_index' => $data['question_index'] ?? 1,
                        'max_questions' => $data['max_questions'] ?? 5,
                        'question_type' => $data['question_type'] ?? null,
                        'interview_stage_label' => $data['interview_stage_label'] ?? null,
                        'focus_skills' => $data['focus_skills'] ?? [],
                        'suggested_answer_points' => $data['suggested_answer_points'] ?? [],
                        'generation_provider' => $data['generation_provider'] ?? null,
                        'model_version' => $response['model_version'] ?? null,
                    ],
                    'created_at' => now(),
                ]);
            }

            $billingUsage = $featureAccessService->commitUsage($billingUsage, [
                'session_id' => $session->id,
                'first_question_message_id' => $firstQuestionMessage?->id,
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

            if ($session) {
                AiChatMessage::query()->where('session_id', $session->id)->delete();
                Cache::store('file')->forget($this->baseContextCacheKey($request->user()->id, $session));
                $session->delete();
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session->fresh(['hoSo:id,tieu_de_ho_so', 'tinTuyenDung:id,tieu_de']),
                'first_question_message' => $firstQuestionMessage,
            ],
        ], 201);
    }

    public function messages(Request $request, int $id): JsonResponse
    {
        $session = $this->resolveSession($request->user()->id, $id);
        $messages = AiChatMessage::query()
            ->where('session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function answer(Request $request, AiClientService $aiClient): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
            'answer' => ['required', 'string', 'min:2'],
        ]);
        $result = $this->processAnswer($request->user()->id, $validated, $aiClient);

        return response()->json([
            'success' => true,
            'data' => [
                'user_message' => $result['user_message'],
                'feedback_message' => $result['feedback_message'],
                'assistant_message' => $result['assistant_message'],
                'question_completed' => $result['question_completed'],
                'completed' => $result['completed'],
            ],
        ], 201);
    }

    public function stream(Request $request, AiClientService $aiClient): StreamedResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
            'answer' => ['required', 'string', 'min:2'],
        ]);

        $userId = $request->user()->id;

        return response()->stream(function () use ($userId, $validated, $aiClient): void {
            try {
                $result = $this->processAnswer($userId, $validated, $aiClient);
                $assistantMessage = $result['assistant_message'];

                if (!$assistantMessage) {
                    echo $this->sseEvent('error', ['message' => 'Mock interview chưa tạo được câu hỏi tiếp theo.']);
                    @ob_flush();
                    @flush();
                    return;
                }

                echo $this->sseEvent('meta', [
                    'provider' => $assistantMessage->metadata['generation_provider'] ?? 'mock_interview',
                    'type' => $assistantMessage->metadata['type'] ?? 'interview_question',
                    'question_index' => $assistantMessage->metadata['question_index'] ?? null,
                    'max_questions' => $assistantMessage->metadata['max_questions'] ?? null,
                    'model_version' => $assistantMessage->metadata['model_version'] ?? null,
                ]);
                @ob_flush();
                @flush();

                foreach ($this->chunkText($assistantMessage->content, 52) as $chunk) {
                    echo $this->sseEvent('chunk', [
                        'content' => $chunk,
                        'type' => $assistantMessage->metadata['type'] ?? 'interview_question',
                    ]);
                    @ob_flush();
                    @flush();
                    usleep(45000);
                }

                echo $this->sseEvent('done', [
                    'assistant_message' => $assistantMessage->toArray(),
                    'completed' => $result['completed'],
                    'question_completed' => $result['question_completed'],
                ]);
                @ob_flush();
                @flush();
            } catch (\Throwable $e) {
                echo $this->sseEvent('error', ['message' => ApiErrorMessage::fromThrowable($e)]);
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

    public function generateReport(Request $request, int $id, AiClientService $aiClient): JsonResponse
    {
        $session = $this->resolveSession($request->user()->id, $id);
        $report = $this->persistInterviewReport($request->user()->id, $session, $aiClient);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function streamReport(Request $request, int $id, AiClientService $aiClient): StreamedResponse
    {
        $session = $this->resolveSession($request->user()->id, $id);
        $userId = $request->user()->id;

        return response()->stream(function () use ($userId, $session, $aiClient): void {
            try {
                $report = $this->persistInterviewReport($userId, $session, $aiClient);
                $streamText = $this->buildReportStreamText($report);

                echo $this->sseEvent('meta', [
                    'provider' => $report->metadata['generation_provider'] ?? 'mock_interview',
                    'type' => 'interview_report',
                    'report_id' => $report->id,
                    'session_id' => $report->session_id,
                ]);
                @ob_flush();
                @flush();

                foreach ($this->chunkText($streamText, 80) as $chunk) {
                    echo $this->sseEvent('chunk', [
                        'content' => $chunk,
                        'type' => 'interview_report',
                    ]);
                    @ob_flush();
                    @flush();
                    usleep(45000);
                }

                echo $this->sseEvent('done', [
                    'report' => $report->toArray(),
                ]);
                @ob_flush();
                @flush();
            } catch (\Throwable $e) {
                echo $this->sseEvent('error', ['message' => ApiErrorMessage::fromThrowable($e)]);
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

    public function showReport(Request $request, int $id): JsonResponse
    {
        $session = $this->resolveSession($request->user()->id, $id);
        $report = AiInterviewReport::query()
            ->with(['session:id,title,status', 'tinTuyenDung:id,tieu_de'])
            ->where('session_id', $session->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function clearSession(Request $request, int $id): JsonResponse
    {
        $session = $this->resolveSession($request->user()->id, $id);
        AiInterviewReport::query()->where('session_id', $session->id)->delete();
        $deletedCount = AiChatMessage::query()->where('session_id', $session->id)->delete();

        $sessionId = $session->id;
        Cache::store('file')->forget($this->baseContextCacheKey($request->user()->id, $session));
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa phiên mock interview thành công.',
            'data' => [
                'session_id' => $sessionId,
                'deleted_count' => $deletedCount,
            ],
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', 'in:0,1,2'],
            'summary' => ['nullable', 'string'],
        ]);

        $session = $this->resolveSession($request->user()->id, $id);
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

    private function resolveIdempotencyKey(Request $request, int $hoSoId, ?int $jobId): string
    {
        $headerKey = trim((string) $request->header('X-Idempotency-Key', ''));
        if ($headerKey !== '') {
            return $headerKey;
        }

        return 'mock-interview:' . $request->user()->id . ':' . $hoSoId . ':' . ($jobId ?: 'none') . ':' . Str::uuid();
    }

    private function resolveSession(int $nguoiDungId, int $sessionId): AiChatSession
    {
        return AiChatSession::query()
            ->where('id', $sessionId)
            ->where('nguoi_dung_id', $nguoiDungId)
            ->where('session_type', 'mock_interview')
            ->firstOrFail();
    }

    private function latestInterviewQuestion(int $sessionId): ?AiChatMessage
    {
        return AiChatMessage::query()
            ->where('session_id', $sessionId)
            ->where('role', 'assistant')
            ->where('metadata->type', 'interview_question')
            ->latest('created_at')
            ->first();
    }

    private function buildTranscript(int $sessionId, bool $withMetadata = false): array
    {
        return AiChatMessage::query()
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (AiChatMessage $item) use ($withMetadata): array {
                $payload = [
                    'role' => $item->role,
                    'content' => $item->content,
                ];

                if ($withMetadata) {
                    $payload['metadata'] = $item->metadata ?? [];
                }

                return $payload;
            })
            ->all();
    }

    private function buildInterviewContext(int $nguoiDungId, AiChatSession $session): array
    {
        return Cache::store('file')->remember(
            $this->baseContextCacheKey($nguoiDungId, $session),
            now()->addMinutes(5),
            function () use ($nguoiDungId, $session): array {
                $candidateProfile = [];
                $topMatches = [];
                $relatedJob = null;

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
                            'parsed_skills' => collect($hoSo->parsing?->parsed_skills_json ?? [])
                                ->map(fn ($item) => is_array($item) ? ($item['skill_name'] ?? null) : $item)
                                ->filter()
                                ->values()
                                ->all(),
                        ];

                        $topMatches = KetQuaMatching::query()
                            ->with('tinTuyenDung:id,tieu_de')
                            ->where('ho_so_id', $hoSo->id)
                            ->where('tin_tuyen_dung_id', $session->related_tin_tuyen_dung_id)
                            ->orderByDesc('diem_phu_hop')
                            ->limit(1)
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
                    $job = TinTuyenDung::query()
                        ->with(['parsing', 'kyNangYeuCaus.kyNang:id,ten_ky_nang'])
                        ->find($session->related_tin_tuyen_dung_id);

                    if ($job) {
                        $relatedJob = [
                            'id' => $job->id,
                            'title' => $job->tieu_de,
                            'level' => $job->cap_bac,
                            'skills' => collect($job->kyNangYeuCaus)
                                ->map(fn ($item) => $item->kyNang?->ten_ky_nang)
                                ->filter()
                                ->values()
                                ->all(),
                        ];
                    }
                }

                return [
                    'candidate_profile' => $candidateProfile,
                    'top_matching_jobs' => $topMatches,
                    'related_job' => $relatedJob,
                ];
            }
        );
    }

    private function baseContextCacheKey(int $nguoiDungId, AiChatSession $session): string
    {
        return implode(':', [
            'mock-interview-context',
            $nguoiDungId,
            $session->id,
            $session->related_ho_so_id ?: 'none',
            $session->related_tin_tuyen_dung_id ?: 'none',
        ]);
    }

    private function resolveQuestionCount(AiChatSession $session): int
    {
        $questionCount = (int) (($session->metadata['question_count'] ?? null) ?: 5);

        return max(2, $questionCount);
    }

    private function processAnswer(int $userId, array $validated, AiClientService $aiClient): array
    {
        $session = $this->resolveSession($userId, (int) $validated['session_id']);
        if ((int) $session->status !== 1) {
            throw ValidationException::withMessages([
                'session_id' => 'Phiên phỏng vấn đã kết thúc, không thể gửi thêm câu trả lời.',
            ]);
        }
        $currentQuestion = $this->latestInterviewQuestion($session->id);

        $userMessage = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => (string) $validated['answer'],
            'metadata' => [
                'type' => 'interview_answer',
                'question_index' => $currentQuestion?->metadata['question_index'] ?? null,
            ],
            'created_at' => now(),
        ]);

        $context = $this->buildInterviewContext($userId, $session);
        $transcript = $this->buildTranscript($session->id, withMetadata: true);
        $response = $aiClient->evaluateMockInterviewAnswer(
            $session->id,
            $currentQuestion?->metadata ?? [],
            (string) $validated['answer'],
            $context,
            $transcript,
            $this->resolveQuestionCount($session)
        );

        $data = $response['data'] ?? [];
        $feedbackMessage = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => trim((string) ($data['feedback_text'] ?? '')),
            'metadata' => [
                'type' => 'interview_feedback',
                'hidden_in_ui' => true,
                'question_index' => $data['question_index'] ?? null,
                'max_questions' => $data['max_questions'] ?? $this->resolveQuestionCount($session),
                'technical_score' => $data['technical_score'] ?? null,
                'communication_score' => $data['communication_score'] ?? null,
                'jd_fit_score' => $data['jd_fit_score'] ?? null,
                'clarity_score' => $data['clarity_score'] ?? null,
                'specificity_score' => $data['specificity_score'] ?? null,
                'structure_score' => $data['structure_score'] ?? null,
                'total_score' => $data['total_score'] ?? null,
                'strengths' => $data['strengths'] ?? [],
                'weaknesses' => $data['weaknesses'] ?? [],
                'completed' => $data['completed'] ?? false,
                'next_question' => $data['next_question'] ?? null,
                'model_version' => $response['model_version'] ?? null,
            ],
            'created_at' => now(),
        ]);

        $nextQuestionMessage = null;
        if (!empty($data['next_question']['question_text'])) {
            $nextQuestionMessage = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => (string) $data['next_question']['question_text'],
                'metadata' => [
                    'type' => 'interview_question',
                    'question_index' => $data['next_question']['question_index'] ?? null,
                    'max_questions' => $data['next_question']['max_questions'] ?? null,
                    'question_type' => $data['next_question']['question_type'] ?? null,
                    'interview_stage_label' => $data['next_question']['interview_stage_label'] ?? null,
                    'focus_skills' => $data['next_question']['focus_skills'] ?? [],
                    'suggested_answer_points' => $data['next_question']['suggested_answer_points'] ?? [],
                    'generation_provider' => $data['next_question']['generation_provider'] ?? null,
                    'model_version' => $response['model_version'] ?? null,
                ],
                'created_at' => now(),
            ]);
        } elseif (!empty($data['completed'])) {
            $nextQuestionMessage = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => 'Phiên phỏng vấn thử đã hoàn thành. Bạn có thể bấm "Sinh báo cáo" để xem đánh giá tổng kết.',
                'metadata' => [
                    'type' => 'interview_completed',
                    'completed' => true,
                    'max_questions' => $data['max_questions'] ?? $this->resolveQuestionCount($session),
                    'generation_provider' => 'rule_based',
                    'model_version' => $response['model_version'] ?? null,
                ],
                'created_at' => now(),
            ]);
        }

        if (!empty($data['completed'])) {
            $session->forceFill([
                'status' => 2,
                'summary' => 'Mock interview đã hoàn thành, sẵn sàng sinh báo cáo tổng kết.',
            ])->save();
        } else {
            $session->touch();
        }

        return [
            'user_message' => $userMessage,
            'feedback_message' => $feedbackMessage,
            'assistant_message' => $nextQuestionMessage,
            'question_completed' => $data['question_index'] ?? null,
            'completed' => $data['completed'] ?? false,
        ];
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

    private function sseEvent(string $event, array $payload): string
    {
        return sprintf(
            "event: %s\ndata: %s\n\n",
            $event,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function chunkText(string $text, int $chunkLength = 80): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        for ($offset = 0; $offset < $length; $offset += $chunkLength) {
            $chunks[] = mb_substr($text, $offset, $chunkLength);
        }

        return $chunks;
    }

    private function persistInterviewReport(int $userId, AiChatSession $session, AiClientService $aiClient): AiInterviewReport
    {
        $existingReport = AiInterviewReport::query()
            ->where('session_id', $session->id)
            ->first();
        if ($existingReport) {
            return $existingReport->fresh(['session:id,title,status', 'tinTuyenDung:id,tieu_de']);
        }

        $context = $this->buildInterviewContext($userId, $session);
        $transcript = $this->buildTranscript($session->id, withMetadata: true);

        $response = $aiClient->generateMockInterviewReport($session->id, $context, $transcript);
        $data = $response['data'] ?? [];

        $report = AiInterviewReport::query()->updateOrCreate(
            ['session_id' => $session->id],
            [
                'nguoi_dung_id' => $userId,
                'tin_tuyen_dung_id' => $session->related_tin_tuyen_dung_id,
                'tong_diem' => $data['tong_diem'] ?? 0,
                'diem_ky_thuat' => $data['diem_ky_thuat'] ?? null,
                'diem_giao_tiep' => $data['diem_giao_tiep'] ?? null,
                'diem_phu_hop_jd' => $data['diem_phu_hop_jd'] ?? null,
                'diem_manh' => $data['diem_manh'] ?? [],
                'diem_yeu' => $data['diem_yeu'] ?? [],
                'de_xuat_cai_thien' => $data['de_xuat_cai_thien'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]
        );

        $session->forceFill([
            'status' => 2,
            'summary' => 'Đã có báo cáo tổng kết mock interview.',
        ])->save();

        return $report->fresh(['session:id,title,status', 'tinTuyenDung:id,tieu_de']);
    }

    private function buildAverageRubricSummary(array $reports): array
    {
        $keys = ['clarity_score', 'specificity_score', 'structure_score'];
        $averages = [];

        foreach ($keys as $key) {
            $values = collect($reports)
                ->map(fn (AiInterviewReport $report) => $report->metadata['rubric_summary'][$key] ?? null)
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (float) $value)
                ->values();

            $averages[$key] = $values->isNotEmpty() ? round($values->avg(), 2) : null;
        }

        return $averages;
    }

    private function buildWeakestDimensionDistribution(array $reports): array
    {
        return collect($reports)
            ->map(fn (AiInterviewReport $report) => $report->metadata['weakest_dimension'] ?? null)
            ->filter()
            ->countBy()
            ->map(fn ($count, $dimension) => [
                'dimension' => $dimension,
                'count' => $count,
            ])
            ->values()
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    private function buildReportStreamText(AiInterviewReport $report): string
    {
        $lines = [];
        $lines[] = 'Tổng điểm hiện tại: ' . $this->formatScoreForStream($report->tong_diem) . '.';

        $immediateActions = collect($report->metadata['structured_improvement']['priority_actions'] ?? [])
            ->merge($report->metadata['practice_plan'] ?? [])
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();

        if ($immediateActions !== []) {
            $lines[] = '3 việc nên làm ngay:';
            foreach ($immediateActions as $item) {
                $lines[] = '- ' . $item;
            }
        }

        if (!empty($report->metadata['weakest_answer_summary']['main_issue'])) {
            $lines[] = 'Điểm cần cải thiện nổi bật: ' . $report->metadata['weakest_answer_summary']['main_issue'];
        }

        if (!empty($report->de_xuat_cai_thien)) {
            $lines[] = '';
            $lines[] = trim((string) $report->de_xuat_cai_thien);
        }

        return trim(implode("\n", $lines));
    }

    private function formatScoreForStream(?float $score): string
    {
        if ($score === null) {
            return '--/100';
        }

        return number_format($score, 2) . '/100';
    }
}
