<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->string('che_do_anh_cv', 20)->nullable()->after('ten_nganh_nghe_muc_tieu');
            $table->string('anh_cv')->nullable()->after('che_do_anh_cv');
        });
    }

    public function down(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->dropColumn(['che_do_anh_cv', 'anh_cv']);
        });
    }
};
