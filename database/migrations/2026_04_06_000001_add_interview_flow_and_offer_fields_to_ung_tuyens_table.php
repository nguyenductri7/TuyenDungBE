<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->string('vong_phong_van_hien_tai', 32)->nullable()->after('ngay_hen_phong_van');
            $table->timestamp('thoi_gian_gui_nhac_lich')->nullable()->after('thoi_gian_phan_hoi_phong_van');
            $table->text('rubric_danh_gia_phong_van')->nullable()->after('ket_qua_phong_van');
            $table->timestamp('thoi_gian_gui_offer')->nullable()->after('thoi_gian_gui_nhac_lich');
            $table->timestamp('thoi_gian_phan_hoi_offer')->nullable()->after('thoi_gian_gui_offer');
            $table->text('ghi_chu_offer')->nullable()->after('thoi_gian_phan_hoi_offer');
            $table->string('link_offer', 2048)->nullable()->after('ghi_chu_offer');
            $table->json('lich_su_xu_ly')->nullable()->after('ghi_chu');
        });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->dropColumn([
                'vong_phong_van_hien_tai',
                'thoi_gian_gui_nhac_lich',
                'rubric_danh_gia_phong_van',
                'thoi_gian_gui_offer',
                'thoi_gian_phan_hoi_offer',
                'ghi_chu_offer',
                'link_offer',
                'lich_su_xu_ly',
            ]);
        });
    }
};
