<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table) {
            $table->longText('thu_xin_viec_ai')->nullable()->after('thu_xin_viec');
        });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table) {
            $table->dropColumn('thu_xin_viec_ai');
        });
    }
};
