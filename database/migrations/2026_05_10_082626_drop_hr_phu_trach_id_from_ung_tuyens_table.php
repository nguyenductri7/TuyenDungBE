<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table) {
            if (Schema::hasColumn('ung_tuyens', 'hr_phu_trach_id')) {
                $table->dropConstrainedForeignId('hr_phu_trach_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table) {
            if (!Schema::hasColumn('ung_tuyens', 'hr_phu_trach_id')) {
                $table->foreignId('hr_phu_trach_id')
                    ->nullable()
                    ->after('ho_so_id')
                    ->constrained('nguoi_dungs')
                    ->nullOnDelete();
            }
        });
    }
};
