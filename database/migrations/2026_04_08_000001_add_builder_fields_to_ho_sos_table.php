<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->string('nguon_ho_so', 20)
                ->default('upload')
                ->after('file_cv')
                ->comment('upload, builder, hybrid');
            $table->string('mau_cv', 50)
                ->nullable()
                ->after('nguon_ho_so');
            $table->json('ky_nang_json')->nullable()->after('mau_cv');
            $table->json('kinh_nghiem_json')->nullable()->after('ky_nang_json');
            $table->json('hoc_van_json')->nullable()->after('kinh_nghiem_json');
            $table->json('du_an_json')->nullable()->after('hoc_van_json');
            $table->json('chung_chi_json')->nullable()->after('du_an_json');
        });
    }

    public function down(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->dropColumn([
                'nguon_ho_so',
                'mau_cv',
                'ky_nang_json',
                'kinh_nghiem_json',
                'hoc_van_json',
                'du_an_json',
                'chung_chi_json',
            ]);
        });
    }
};
