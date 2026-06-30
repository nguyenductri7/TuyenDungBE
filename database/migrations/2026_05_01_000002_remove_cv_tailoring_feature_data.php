<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bang_gia_tinh_nang_ai')) {
            DB::table('bang_gia_tinh_nang_ai')
                ->where('feature_code', 'cv_tailoring')
                ->delete();
        }

        if (Schema::hasTable('goi_dich_vu_tinh_nangs')) {
            DB::table('goi_dich_vu_tinh_nangs')
                ->where('feature_code', 'cv_tailoring')
                ->delete();
        }

        if (Schema::hasTable('su_dung_tinh_nang_ais')) {
            DB::table('su_dung_tinh_nang_ais')
                ->where('feature_code', 'cv_tailoring')
                ->delete();
        }

        if (Schema::hasTable('ai_usage_logs')) {
            DB::table('ai_usage_logs')
                ->where('feature', 'cv_tailoring')
                ->delete();
        }
    }

    public function down(): void
    {
        // Feature removed; rollback does not restore removed configuration rows.
    }
};
