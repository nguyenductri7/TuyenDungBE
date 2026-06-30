<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->string('nguoi_phong_van')->nullable()->after('hinh_thuc_phong_van');
        });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->dropColumn('nguoi_phong_van');
        });
    }
};
