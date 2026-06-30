<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->string('bo_cuc_cv', 100)->nullable()->after('mau_cv');
            $table->string('ten_template_cv', 150)->nullable()->after('bo_cuc_cv');
        });
    }

    public function down(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->dropColumn(['bo_cuc_cv', 'ten_template_cv']);
        });
    }
};
