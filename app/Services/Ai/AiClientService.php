<?php

namespace App\Services\Ai;

use App\Support\ApiErrorMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AiClientService
{
    private string $baseUrl;
    private int $timeout;
    private int $matchingTimeout;
    private int $parseTimeout;
    private int $generationTimeout;
    private int $chatTimeout;
    private int $streamTimeout;

    public function __construct(private readonly AiUsageLogger $usageLogger)
    {
        $this->baseUrl = rtrim((string) config('services.ai_service.base_url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');
        $this->timeout = (int) config('services.ai_service.timeout', env('AI_SERVICE_TIMEOUT', 90));
        $this->matchingTimeout = (int) config('services.ai_service.matching_timeout', env('AI_MATCHING_TIMEOUT', 10));
        $this->parseTimeout = (int) config('services.ai_service.parse_timeout', env('AI_PARSE_TIMEOUT', 60));
        $this->generationTimeout = (int) config('services.ai_service.generation_timeout', env('AI_GENERATION_TIMEOUT', 75));
        $this->chatTimeout = (int) config('services.ai_service.chat_timeout', env('AI_CHAT_TIMEOUT', 60));
        $this->streamTimeout = (int) config('services.ai_service.stream_timeout', env('AI_STREAM_TIMEOUT', 90));
    }

    public function parseCv(int $hoSoId, string $filePath): array
    {
        return $this->post('/parse/cv', [
            'ho_so_id' => $hoSoId,
            'file_path' => $filePath,
        ], 'cv_parse');
    }

    public function parseCvFromRawText(int $hoSoId, string $rawText): array
    {
        return $this->post('/parse/cv', [
            'ho_so_id' => $hoSoId,
            'raw_text' => $rawText,
        ], 'cv_parse_raw_text');
    }

    public function parseJd(int $tinTuyenDungId, string $jobText): array
    {
        return $this->post('/parse/jd', [
            'tin_tuyen_dung_id' => $tinTuyenDungId,
            'job_text' => $jobText,
        ], 'jd_parse');
    }

    public function matchCvJd(
        int $hoSoId,
        int $tinTuyenDungId,
        array $cvProfile = [],
        array $jdProfile = [],
        bool $includeLlmExplanation = false
    ): array
    {
        return $this->post('/match/cv-jd', [
            'ho_so_id' => $hoSoId,
            'tin_tuyen_dung_id' => $tinTuyenDungId,
            'cv_profile' => $cvProfile,
            'jd_profile' => $jdProfile,
            'include_llm_explanation' => $includeLlmExplanation,
        ], 'cv_jd_matching');
    }

    public function matchCvJdParallel(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $this->extendExecutionTimeForAiRequest($this->matchingTimeout);

        $payloads = [];
        $startedAt = microtime(true);

        foreach ($requests as $key => $request) {
            $payloads[$key] = [
                'request_key' => (string) $key,
                'ho_so_id' => (int) ($request['ho_so_id'] ?? 0),
                'tin_tuyen_dung_id' => (int) ($request['tin_tuyen_dung_id'] ?? 0),
                'cv_profile' => $request['cv_profile'] ?? [],
                'jd_profile' => $request['jd_profile'] ?? [],
                'include_llm_explanation' => (bool) ($request['include_llm_explanation'] ?? false),
            ];
        }

        try {
            return $this->matchCvJdBatch($payloads, $startedAt);
        } catch (Throwable $exception) {
            foreach ($payloads as $payload) {
                $this->usageLogger->logError('cv_jd_matching_batch', '/match/cv-jd/batch', $payload, $exception, $startedAt);
            }

            throw new RuntimeException(
                ApiErrorMessage::fromThrowable($exception, 503, 'Không thể kết nối tới AI service.'),
                0,
                $exception
            );
        }
    }

    private function matchCvJdBatch(array $payloads, float $startedAt): array
    {
        $uri = '/match/cv-jd/batch';
        $response = Http::timeout($this->matchingTimeout)
            ->acceptJson()
            ->asJson()
            ->post($this->baseUrl . $uri, ['items' => array_values($payloads)])
            ->throw();

        $json = $response->json();
        $this->usageLogger->logSuccess('cv_jd_matching_batch', $uri, ['count' => count($payloads)], $json, $startedAt, $response);

        $items = is_array($json) ? ($json['data']['items'] ?? []) : [];
        $itemsByKey = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $requestKey = (string) ($item['request_key'] ?? '');
            if ($requestKey !== '') {
                unset($item['request_key']);
                $itemsByKey[$requestKey] = $item;
            }
        }

        $results = [];
        foreach ($payloads as $key => $payload) {
            $result = $itemsByKey[(string) $key] ?? null;
            if (!is_array($result)) {
                $exception = new RuntimeException('AI service không trả về phản hồi cho hồ sơ này.');
                $this->usageLogger->logError('cv_jd_matching_batch', $uri, $payload, $exception, $startedAt);
                $results[$key] = ['success' => false, 'error' => $exception->getMessage()];
                continue;
            }

            if (($result['success'] ?? true) === false) {
                $exception = new RuntimeException(
                    ApiErrorMessage::fromRawMessage(
                        (string) ($result['error'] ?? $result['message'] ?? 'AI service trả về lỗi.'),
                        $response->status(),
                        'AI service trả về lỗi.'
                    )
                );
                $this->usageLogger->logError('cv_jd_matching_batch', $uri, $payload, $exception, $startedAt, $response->status());
            }

            $results[$key] = $result;
        }

        return $results;
    }

    public function generateCoverLetter(
        int $hoSoId,
        int $tinTuyenDungId,
        array $cvProfile = [],
        array $jdProfile = [],
        array $matchingProfile = []
    ): array
    {
        return $this->post('/generate/cover-letter', [
            'ho_so_id' => $hoSoId,
            'tin_tuyen_dung_id' => $tinTuyenDungId,
            'cv_profile' => $cvProfile,
            'jd_profile' => $jdProfile,
            'matching_profile' => $matchingProfile,
        ], 'cover_letter');
    }

    public function generateCareerReport(int $hoSoId, array $cvProfile = [], array $matchingProfiles = []): array
    {
        return $this->post('/generate/career-report', [
            'ho_so_id' => $hoSoId,
            'cv_profile' => $cvProfile,
            'matching_profiles' => $matchingProfiles,
        ], 'career_report');
    }

    public function generateCvBuilderWriting(array $cvProfile = [], string $section = 'summary', array $options = []): array
    {
        return $this->post('/generate/cv-builder-writing', [
            'cv_profile' => $cvProfile,
            'section' => $section,
            'options' => $options,
        ], 'cv_builder_ai_writing');
    }

    public function careerChat(int $sessionId, string $message, array $history = [], array $context = [], bool $forceModel = false): array
    {
        return $this->post('/chat/career-consultant', [
            'session_id' => $sessionId,
            'message' => $message,
            'history' => $history,
            'context' => $context,
            'force_model' => $forceModel,
        ], 'career_chat');
    }

    public function careerChatStream(int $sessionId, string $message, array $history = [], array $context = [], bool $forceModel = false, ?callable $onEvent = null): void
    {
        $this->stream('/chat/career-consultant/stream', [
            'session_id' => $sessionId,
            'message' => $message,
            'history' => $history,
            'context' => $context,
            'force_model' => $forceModel,
        ], 'career_chat_stream', $onEvent);
    }

    public function generateMockInterviewQuestion(
        int $sessionId,
        array $interviewContext = [],
        array $transcript = [],
        int $questionIndex = 1,
        int $maxQuestions = 5
    ): array
    {
        return $this->post('/interview/mock/question', [
            'session_id' => $sessionId,
            'interview_context' => $interviewContext,
            'transcript' => $transcript,
            'question_index' => $questionIndex,
            'max_questions' => $maxQuestions,
        ], 'mock_interview_question');
    }

    public function evaluateMockInterviewAnswer(
        int $sessionId,
        array $questionPayload,
        string $answer,
        array $interviewContext = [],
        array $transcript = [],
        int $maxQuestions = 5
    ): array {
        return $this->post('/interview/mock/evaluate', [
            'session_id' => $sessionId,
            'question_payload' => $questionPayload,
            'answer' => $answer,
            'interview_context' => $interviewContext,
            'transcript' => $transcript,
            'max_questions' => $maxQuestions,
        ], 'mock_interview_answer_evaluation');
    }

    public function generateMockInterviewReport(int $sessionId, array $interviewContext = [], array $transcript = []): array
    {
        return $this->post('/interview/mock/report', [
            'session_id' => $sessionId,
            'interview_context' => $interviewContext,
            'transcript' => $transcript,
        ], 'mock_interview_report');
    }

    public function generateInterviewCopilot(int $ungTuyenId, array $applicationContext = []): array
    {
        return $this->post('/interview/copilot/generate', [
            'ung_tuyen_id' => $ungTuyenId,
            'application_context' => $applicationContext,
        ], 'interview_copilot_generate');
    }

    public function evaluateInterviewCopilot(int $ungTuyenId, array $applicationContext = [], array $interviewNotes = []): array
    {
        return $this->post('/interview/copilot/evaluate', [
            'ung_tuyen_id' => $ungTuyenId,
            'application_context' => $applicationContext,
            'interview_notes' => $interviewNotes,
        ], 'interview_copilot_evaluate');
    }

    public function recordFallback(string $feature, ?string $reason = null, array $payload = [], array $metadata = []): void
    {
        $this->usageLogger->logFallback($feature, $reason, $payload, $metadata);
    }

    private function post(string $uri, array $payload, string $feature): array
    {
        $timeout = $this->timeoutForFeature($feature);
        $this->extendExecutionTimeForAiRequest($timeout);

        $startedAt = microtime(true);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl . $uri, $payload)
                ->throw();
        } catch (ConnectionException $e) {
            $exception = new RuntimeException(
                ApiErrorMessage::fromThrowable($e, 503, 'Không thể kết nối tới AI service.'),
                0,
                $e
            );
            $this->usageLogger->logError($feature, $uri, $payload, $exception, $startedAt);
            throw $exception;
        } catch (RequestException $e) {
            $message = $this->resolveAiErrorMessage($e)
                ?? $e->response?->json('message')
                ?? $e->response?->body()
                ?? 'AI service trả về lỗi.';

            $exception = new RuntimeException(
                ApiErrorMessage::fromRawMessage((string) $message, $e->response?->status(), 'AI service trả về lỗi.'),
                0,
                $e
            );
            $this->usageLogger->logError($feature, $uri, $payload, $exception, $startedAt, $e->response?->status());
            throw $exception;
        }

        $json = $response->json();
        if (is_array($json) && ($json['success'] ?? true) === false) {
            $exception = new RuntimeException(
                ApiErrorMessage::fromRawMessage(
                    (string) ($json['error'] ?? $json['message'] ?? 'AI service trả về lỗi.'),
                    $response->status(),
                    'AI service trả về lỗi.'
                )
            );
            $this->usageLogger->logError($feature, $uri, $payload, $exception, $startedAt, $response->status());
            throw $exception;
        }

        $this->usageLogger->logSuccess($feature, $uri, $payload, $json, $startedAt, $response);

        return $json;
    }

    private function resolveAiErrorMessage(RequestException $exception): ?string
    {
        $detail = $exception->response?->json('detail');
        if (!is_array($detail)) {
            return null;
        }

        $messages = [];
        foreach ($detail as $item) {
            if (!is_array($item)) {
                continue;
            }

            $field = collect($item['loc'] ?? [])
                ->reject(static fn ($part) => $part === 'body')
                ->last();
            $type = (string) ($item['type'] ?? '');
            $context = is_array($item['ctx'] ?? null) ? $item['ctx'] : [];

            if ($field === 'max_questions' && $type === 'greater_than_equal') {
                $messages[] = 'Số câu hỏi phỏng vấn tối thiểu là ' . (int) ($context['ge'] ?? 2) . ' câu.';
                continue;
            }

            if ($field === 'max_questions' && $type === 'less_than_equal') {
                $messages[] = 'Số câu hỏi phỏng vấn tối đa là ' . (int) ($context['le'] ?? 7) . ' câu.';
                continue;
            }

            if (isset($item['msg'])) {
                $messages[] = ApiErrorMessage::fromRawMessage(
                    (string) $item['msg'],
                    422,
                    'Dữ liệu gửi lên chưa hợp lệ. Vui lòng kiểm tra lại.'
                );
            }
        }

        $messages = array_values(array_unique(array_filter($messages)));

        return $messages === [] ? null : implode("\n", $messages);
    }

    private function stream(string $uri, array $payload, string $feature, ?callable $onEvent = null): void
    {
        $timeout = $this->timeoutForFeature($feature);
        $this->extendExecutionTimeForAiRequest($timeout);

        $startedAt = microtime(true);
        $url = $this->baseUrl . $uri;
        $buffer = '';
        $currentEvent = 'message';
        $dataLines = [];
        $lastPayload = [];
        $dispatchEvent = function () use (&$currentEvent, &$dataLines, &$lastPayload, $onEvent): void {
            if ($dataLines === []) {
                return;
            }

            $json = implode("\n", $dataLines);
            try {
                $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $payload = ['raw' => $json];
            }

            $lastPayload = $payload;

            if ($onEvent) {
                $onEvent($currentEvent ?: 'message', $payload);
            }

            $currentEvent = 'message';
            $dataLines = [];
        };

        try {
            $curl = curl_init($url);

            if ($curl === false) {
                throw new RuntimeException(
                    ApiErrorMessage::fromRawMessage(
                        'Không thể khởi tạo kết nối stream tới AI service.',
                        503,
                        'Không thể khởi tạo kết nối stream tới AI service.'
                    )
                );
            }

            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: text/event-stream',
                ],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$buffer, &$currentEvent, &$dataLines, $dispatchEvent) {
                $buffer .= $chunk;

                while (($position = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $position);
                    $buffer = substr($buffer, $position + 1);
                    $line = rtrim($line, "\r");

                    if ($line === '') {
                        $dispatchEvent();
                        continue;
                    }

                    if (str_starts_with($line, 'event:')) {
                        $currentEvent = trim(substr($line, 6));
                        continue;
                    }

                    if (str_starts_with($line, 'data:')) {
                        $dataLines[] = trim(substr($line, 5));
                    }
                }

                return strlen($chunk);
            },
            ]);

            $result = curl_exec($curl);

            if ($result === false) {
                $error = curl_error($curl) ?: 'Không thể stream dữ liệu từ AI service.';
                curl_close($curl);
                throw new RuntimeException(ApiErrorMessage::fromRawMessage($error, 503, 'Không thể stream dữ liệu từ AI service.'));
            }

            $remaining = trim(str_replace("\r", '', $buffer));
            if ($remaining !== '') {
                foreach (explode("\n", $remaining) as $line) {
                    if (str_starts_with($line, 'event:')) {
                        $currentEvent = trim(substr($line, 6));
                        continue;
                    }

                    if (str_starts_with($line, 'data:')) {
                        $dataLines[] = trim(substr($line, 5));
                    }
                }
            }

            $dispatchEvent();

            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);

            if ($statusCode >= 400) {
                throw new RuntimeException(
                    ApiErrorMessage::fromRawMessage(
                        'AI service trả về lỗi khi stream hội thoại.',
                        $statusCode,
                        'AI service trả về lỗi khi stream hội thoại.'
                    )
                );
            }

            $this->usageLogger->logSuccess($feature, $uri, $payload, $lastPayload, $startedAt);
        } catch (Throwable $exception) {
            $this->usageLogger->logError($feature, $uri, $payload, $exception, $startedAt);
            throw $exception;
        }
    }

    private function timeoutForFeature(string $feature): int
    {
        return match ($feature) {
            'cv_parse', 'cv_parse_raw_text', 'jd_parse' => $this->parseTimeout,
            'cv_jd_matching' => $this->matchingTimeout,
            'career_chat' => $this->chatTimeout,
            'career_chat_stream' => $this->streamTimeout,
            'cover_letter',
            'career_report',
            'cv_builder_ai_writing',
            'mock_interview_question',
            'mock_interview_answer_evaluation',
            'mock_interview_report',
            'interview_copilot_generate',
            'interview_copilot_evaluate' => $this->generationTimeout,
            default => $this->timeout,
        };
    }

    private function extendExecutionTimeForAiRequest(?int $timeout = null): void
    {
        if (!function_exists('set_time_limit')) {
            return;
        }

        $executionLimit = max(60, ($timeout ?? $this->timeout) + 15);
        @set_time_limit($executionLimit);
    }
}
