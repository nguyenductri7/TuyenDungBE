<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tin_tuyen_dungs', function (Blueprint $table): void {
            if (!Schema::hasColumn('tin_tuyen_dungs', 'hr_phu_trach_id')) {
                $table->foreignId('hr_phu_trach_id')
                    ->nullable()
                    ->after('cong_ty_id')
                    ->constrained('nguoi_dungs')
                    ->nullOnDelete();
            }
        });

        Schema::table('ung_tuyens', function (Blueprint $table): void {
            if (!Schema::hasColumn('ung_tuyens', 'hr_phu_trach_id')) {
                $table->foreignId('hr_phu_trach_id')
                    ->nullable()
                    ->after('ho_so_id')
                    ->constrained('nguoi_dungs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            if (Schema::hasColumn('ung_tuyens', 'hr_phu_trach_id')) {
                $table->dropConstrainedForeignId('hr_phu_trach_id');
            }
        });

        Schema::table('tin_tuyen_dungs', function (Blueprint $table): void {
            if (Schema::hasColumn('tin_tuyen_dungs', 'hr_phu_trach_id')) {
                $table->dropConstrainedForeignId('hr_phu_trach_id');
            }
        });
    }
};
