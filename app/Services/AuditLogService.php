<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CongTy;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function log(array $payload): AuditLog
    {
        return AuditLog::create($payload);
    }

    public function logModelAction(
        ?NguoiDung $actor,
        string $action,
        string $description,
        ?Model $target = null,
        ?CongTy $company = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return $this->log([
            'actor_id' => $actor?->id,
            'actor_role' => $this->resolveActorRole($actor),
            'company_id' => $company?->id,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target?->getKey(),
            'action' => $action,
            'description' => $description,
            'before_json' => $before ?: null,
            'after_json' => $after ?: null,
            'metadata_json' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 512) : null,
        ]);
    }

    private function resolveActorRole(?NguoiDung $actor): ?string
    {
        if (!$actor) {
            return null;
        }

        return match ((int) $actor->vai_tro) {
            NguoiDung::VAI_TRO_ADMIN => 'admin',
            NguoiDung::VAI_TRO_NHA_TUYEN_DUNG => 'nha_tuyen_dung',
            NguoiDung::VAI_TRO_UNG_VIEN => 'ung_vien',
            default => 'unknown',
        };
    }
}
