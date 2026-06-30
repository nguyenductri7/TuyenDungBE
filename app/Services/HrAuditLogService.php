<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CongTy;
use App\Models\NguoiDung;

class HrAuditLogService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function log(
        CongTy $congTy,
        ?NguoiDung $actor,
        string $eventType,
        string $description,
        ?NguoiDung $target = null,
        array $extra = [],
    ): AuditLog {
        return $this->auditLogService->logModelAction(
            actor: $actor,
            action: $eventType,
            description: $description,
            target: $target,
            company: $congTy,
            metadata: array_filter([
                ...$extra,
                'scope' => 'hr',
                'target_user_id' => $target?->id,
            ], fn ($value) => $value !== null),
        );
    }
}
