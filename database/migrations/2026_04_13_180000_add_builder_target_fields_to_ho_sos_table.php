<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->string('che_do_mau_cv', 20)
                ->nullable()
                ->after('mau_cv')
                ->comment('style, position');
            $table->string('vi_tri_ung_tuyen_muc_tieu', 150)
                ->nullable()
                ->after('che_do_mau_cv');
            $table->string('ten_nganh_nghe_muc_tieu', 150)
                ->nullable()
                ->after('vi_tri_ung_tuyen_muc_tieu');
        });
    }

    public function down(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->dropColumn([
                'che_do_mau_cv',
                'vi_tri_ung_tuyen_muc_tieu',
                'ten_nganh_nghe_muc_tieu',
            ]);
        });
    }
};
