<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\AuditLog;

trait MapsAuditLogResponse
{
    private function mapAuditLog(AuditLog $log): array
    {
        $log->loadMissing([
            'actor:id,ho_ten,email,vai_tro',
            'company:id,ten_cong_ty,email',
        ]);

        return [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->description,
            'actor_role' => $log->actor_role,
            'actor' => $log->actor ? [
                'id' => $log->actor->id,
                'ho_ten' => $log->actor->ho_ten,
                'email' => $log->actor->email,
                'vai_tro' => $log->actor->vai_tro,
            ] : null,
            'company' => $log->company ? [
                'id' => $log->company->id,
                'ten_cong_ty' => $log->company->ten_cong_ty,
                'email' => $log->company->email,
            ] : null,
            'target_type' => $log->target_type,
            'target_id' => $log->target_id,
            'before' => $log->before_json ?: null,
            'after' => $log->after_json ?: null,
            'metadata' => $log->metadata_json ?: null,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => optional($log->created_at)?->toISOString(),
        ];
    }
}
