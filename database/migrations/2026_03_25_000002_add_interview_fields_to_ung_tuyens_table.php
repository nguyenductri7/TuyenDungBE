<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table) {
            $table->string('hinh_thuc_phong_van', 50)->nullable()->after('ngay_hen_phong_van');
            $table->string('link_phong_van', 2048)->nullable()->after('hinh_thuc_phong_van');
        });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table) {
            $table->dropColumn(['hinh_thuc_phong_van', 'link_phong_van']);
        });
    }
};
