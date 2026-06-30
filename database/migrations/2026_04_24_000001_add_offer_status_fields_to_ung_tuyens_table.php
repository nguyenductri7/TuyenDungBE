<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->unsignedTinyInteger('trang_thai_offer')->default(0)->after('thoi_gian_gui_offer');
            $table->timestamp('han_phan_hoi_offer')->nullable()->after('thoi_gian_phan_hoi_offer');
            $table->text('ghi_chu_phan_hoi_offer')->nullable()->after('ghi_chu_offer');
        });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->dropColumn([
                'trang_thai_offer',
                'han_phan_hoi_offer',
                'ghi_chu_phan_hoi_offer',
            ]);
        });
    }
};
