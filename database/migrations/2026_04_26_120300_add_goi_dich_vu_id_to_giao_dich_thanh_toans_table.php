<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('giao_dich_thanh_toans', function (Blueprint $table) {
            $table->foreignId('goi_dich_vu_id')
                ->nullable()
                ->after('vi_nguoi_dung_id')
                ->constrained('goi_dich_vus')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('giao_dich_thanh_toans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('goi_dich_vu_id');
        });
    }
};
