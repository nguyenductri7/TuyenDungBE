<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->unsignedTinyInteger('trang_thai_tham_gia_phong_van')
                ->nullable()
                ->after('ngay_hen_phong_van');
            $table->timestamp('thoi_gian_phan_hoi_phong_van')
                ->nullable()
                ->after('trang_thai_tham_gia_phong_van');
        });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->dropColumn([
                'trang_thai_tham_gia_phong_van',
                'thoi_gian_phan_hoi_phong_van',
            ]);
        });
    }
};
