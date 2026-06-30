<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tin_tuyen_dungs', function (Blueprint $table) {
            $table->integer('muc_luong_tu')->nullable()->after('so_luong_tuyen');
            $table->integer('muc_luong_den')->nullable()->after('muc_luong_tu');
            $table->string('don_vi_luong', 20)->default('VND')->after('muc_luong_den');
            $table->string('trinh_do_yeu_cau', 100)->nullable()->after('kinh_nghiem_yeu_cau');
        });
    }

    public function down(): void
    {
        Schema::table('tin_tuyen_dungs', function (Blueprint $table) {
            $table->dropColumn(['muc_luong_tu', 'muc_luong_den', 'don_vi_luong', 'trinh_do_yeu_cau']);
        });
    }
};
