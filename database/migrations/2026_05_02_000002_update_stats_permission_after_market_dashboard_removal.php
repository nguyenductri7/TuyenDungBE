<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permission_definitions')) {
            return;
        }

        DB::table('permission_definitions')
            ->where('scope', 'admin')
            ->where('key', 'stats')
            ->update([
                'description' => 'Xem các báo cáo tổng hợp, thống kê lưu tin và phân tích hiệu suất AI.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('permission_definitions')) {
            return;
        }

        DB::table('permission_definitions')
            ->where('scope', 'admin')
            ->where('key', 'stats')
            ->update([
                'description' => 'Xem các báo cáo tổng hợp, thống kê lưu tin, xu hướng thị trường và phân tích chuyên sâu.',
                'updated_at' => now(),
            ]);
    }
};
