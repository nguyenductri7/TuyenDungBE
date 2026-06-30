<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tu_van_nghe_nghieps', function (Blueprint $table) {
            $table->longText('bao_cao_chi_tiet')->nullable()->after('goi_y_ky_nang_bo_sung');
            $table->string('model_version', 50)->nullable()->after('bao_cao_chi_tiet');
        });
    }

    public function down(): void
    {
        Schema::table('tu_van_nghe_nghieps', function (Blueprint $table) {
            $table->dropColumn(['bao_cao_chi_tiet', 'model_version']);
        });
    }
};
