<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->boolean('da_rut_don')->default(false)->after('trang_thai');
            $table->timestamp('thoi_gian_rut_don')->nullable()->after('da_rut_don');
        });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->dropColumn([
                'da_rut_don',
                'thoi_gian_rut_don',
            ]);
        });
    }
};
