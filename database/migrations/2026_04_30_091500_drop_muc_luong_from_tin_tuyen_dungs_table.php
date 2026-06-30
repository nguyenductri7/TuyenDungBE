<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tin_tuyen_dungs', 'muc_luong')) {
            return;
        }

        Schema::table('tin_tuyen_dungs', function (Blueprint $table) {
            $table->dropColumn('muc_luong');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tin_tuyen_dungs', 'muc_luong')) {
            return;
        }

        Schema::table('tin_tuyen_dungs', function (Blueprint $table) {
            $table->integer('muc_luong')->nullable()->after('so_luong_tuyen')->comment('Mức lương ước tính (VNĐ)');
        });
    }
};
