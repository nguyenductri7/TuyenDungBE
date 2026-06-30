<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ket_qua_matchings', function (Blueprint $table) {
            $table->decimal('diem_ky_nang', 5, 2)->nullable()->after('diem_phu_hop');
            $table->decimal('diem_kinh_nghiem', 5, 2)->nullable()->after('diem_ky_nang');
            $table->decimal('diem_hoc_van', 5, 2)->nullable()->after('diem_kinh_nghiem');
            $table->longText('explanation')->nullable()->after('chi_tiet_diem');
        });
    }

    public function down(): void
    {
        Schema::table('ket_qua_matchings', function (Blueprint $table) {
            $table->dropColumn(['diem_ky_nang', 'diem_kinh_nghiem', 'diem_hoc_van', 'explanation']);
        });
    }
};
