<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Thêm cột deleted_at để hỗ trợ xoá mềm (soft delete).
     */
    public function up(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
