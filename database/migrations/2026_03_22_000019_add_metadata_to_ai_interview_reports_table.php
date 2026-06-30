<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_interview_reports', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('de_xuat_cai_thien');
        });
    }

    public function down(): void
    {
        Schema::table('ai_interview_reports', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
