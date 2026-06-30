<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\NguoiDung;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiUsageLogger
{
    public function logSuccess(string $feature, string $endpoint, array $payload, mixed $responsePayload, float $startedAt, ?Response $response = null): void
    {
        $responseArray = is_array($responsePayload) ? $responsePayload : [];
        $data = is_array($responseArray['data'] ?? null) ? $responseArray['data'] : $responseArray;

        $this->persist([
            ...$this->baseAttributes($feature, $endpoint, $payload),
            'provider' => $this->stringOrNull(Arr::get($data, 'provider') ?? Arr::get($responseArray, 'provider')),
            'model' => $this->stringOrNull(Arr::get($data, 'model') ?? Arr::get($responseArray, 'model')),
            'model_version' => $this->stringOrNull(Arr::get($data, 'model_version') ?? Arr::get($responseArray, 'model_version')),
            'status' => AiUsageLog::STATUS_SUCCESS,
            'used_fallback' => false,
            'duration_ms' => $this->durationMs($startedAt),
            'http_status' => $response?->status(),
            'metadata_json' => [
                'request_refs' => $this->extractRequestRefs($payload),
                'response_keys' => is_array($data) ? array_slice(array_keys($data), 0, 20) : [],
            ],
        ]);
    }

    public function logError(string $feature, string $endpoint, array $payload, Throwable $exception, float $startedAt, ?int $httpStatus = null): void
    {
        $this->persist([
            ...$this->baseAttributes($feature, $endpoint, $payload),
            'status' => AiUsageLog::STATUS_ERROR,
            'used_fallback' => false,
            'duration_ms' => $this->durationMs($startedAt),
            'http_status' => $httpStatus,
            'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            'metadata_json' => [
                'request_refs' => $this->extractRequestRefs($payload),
                'exception' => class_basename($exception),
            ],
        ]);
    }

    public function logFallback(string $feature, ?string $reason = null, array $payload = [], array $metadata = []): void
    {
        $this->persist([
            ...$this->baseAttributes($feature, 'local_fallback', $payload),
            'status' => AiUsageLog::STATUS_FALLBACK,
            'used_fallback' => true,
            'duration_ms' => null,
            'error_message' => $reason ? mb_substr($reason, 0, 2000) : null,
            'metadata_json' => [
                'request_refs' => $this->extractRequestRefs($payload),
                ...$metadata,
            ],
        ]);
    }

    private function baseAttributes(string $feature, string $endpoint, array $payload): array
    {
        $user = Auth::user();
        $companyId = $this->resolveCompanyId($payload, $user);
        [$requestRefType, $requestRefId] = $this->primaryRequestRef($payload);

        return [
            'feature' => $feature,
            'endpoint' => $endpoint,
            'user_id' => $user?->id,
            'company_id' => $companyId,
            'request_ref_type' => $requestRefType,
            'request_ref_id' => $requestRefId,
        ];
    }

    private function persist(array $attributes): void
    {
        try {
            AiUsageLog::create($attributes);
        } catch (Throwable $exception) {
            Log::warning('Không ghi được AI usage log.', [
                'error' => $exception->getMessage(),
                'feature' => $attributes['feature'] ?? null,
            ]);
        }
    }

    private function resolveCompanyId(array $payload, mixed $user): ?int
    {
        if (isset($payload['company_id']) && is_numeric($payload['company_id'])) {
            return (int) $payload['company_id'];
        }

        if (!$user instanceof NguoiDung || !$user->isNhaTuyenDung()) {
            return null;
        }

        try {
            return $user->congTyHienTai()?->id;
        } catch (Throwable) {
            return null;
        }
    }

    private function primaryRequestRef(array $payload): array
    {
        $priority = [
            'ung_tuyen_id' => 'ung_tuyen',
            'tin_tuyen_dung_id' => 'tin_tuyen_dung',
            'ho_so_id' => 'ho_so',
            'session_id' => 'ai_chat_session',
        ];

        foreach ($priority as $key => $type) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return [$type, (int) $payload[$key]];
            }
        }

        return [null, null];
    }

    private function extractRequestRefs(array $payload): array
    {
        $keys = ['ho_so_id', 'tin_tuyen_dung_id', 'ung_tuyen_id', 'session_id'];
        $refs = [];

        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                $refs[$key] = $payload[$key];
            }
        }

        if (isset($payload['application_context']['interview_round']['id'])) {
            $refs['interview_round_id'] = $payload['application_context']['interview_round']['id'];
        }

        return $refs;
    }

    private function durationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr((string) $value, 0, 160);
    }
}
