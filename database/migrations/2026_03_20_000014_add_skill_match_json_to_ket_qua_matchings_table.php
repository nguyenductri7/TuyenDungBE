<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ket_qua_matchings', function (Blueprint $table) {
            $table->json('matched_skills_json')->nullable()->after('chi_tiet_diem');
            $table->json('missing_skills_json')->nullable()->after('matched_skills_json');
        });
    }

    public function down(): void
    {
        Schema::table('ket_qua_matchings', function (Blueprint $table) {
            $table->dropColumn(['matched_skills_json', 'missing_skills_json']);
        });
    }
};
