<?php

use App\Models\NguoiDung;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_audit_logs') || !Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('hr_audit_logs')
            ->orderBy('id')
            ->chunk(200, function ($logs): void {
                foreach ($logs as $log) {
                    $exists = DB::table('audit_logs')
                        ->where('metadata_json->migrated_from', 'hr_audit_logs')
                        ->where('metadata_json->legacy_id', $log->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $metadata = json_decode((string) $log->du_lieu_bo_sung, true) ?: [];
                    $metadata['scope'] = 'hr';
                    $metadata['target_user_id'] = $log->nguoi_bi_tac_dong_id;
                    $metadata['migrated_from'] = 'hr_audit_logs';
                    $metadata['legacy_id'] = $log->id;

                    DB::table('audit_logs')->insert([
                        'actor_id' => $log->nguoi_thuc_hien_id,
                        'actor_role' => 'nha_tuyen_dung',
                        'company_id' => $log->cong_ty_id,
                        'target_type' => $log->nguoi_bi_tac_dong_id ? NguoiDung::class : null,
                        'target_id' => $log->nguoi_bi_tac_dong_id,
                        'action' => $log->loai_su_kien,
                        'description' => $log->mo_ta,
                        'before_json' => null,
                        'after_json' => null,
                        'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                        'ip_address' => null,
                        'user_agent' => null,
                        'created_at' => $log->created_at,
                        'updated_at' => $log->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')
            ->where('metadata_json->migrated_from', 'hr_audit_logs')
            ->delete();
    }
};
