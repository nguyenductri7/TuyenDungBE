<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tái cấu trúc: Chuyển so_chung_chi, hinh_anh từ ky_nangs → nguoi_dung_ky_nangs
     * và thêm mo_ta, icon vào ky_nangs.
     */
    public function up(): void
    {
        // 1) Thêm mo_ta, icon vào ky_nangs
        Schema::table('ky_nangs', function (Blueprint $table) {
            $table->text('mo_ta')->nullable()->after('ten_ky_nang');
            $table->string('icon', 100)->nullable()->after('mo_ta');
        });

        // 2) Xoá so_chung_chi, hinh_anh khỏi ky_nangs
        Schema::table('ky_nangs', function (Blueprint $table) {
            $table->dropColumn(['so_chung_chi', 'hinh_anh']);
        });

        // 3) Thêm so_chung_chi, hinh_anh vào nguoi_dung_ky_nangs
        Schema::table('nguoi_dung_ky_nangs', function (Blueprint $table) {
            $table->integer('so_chung_chi')->default(0)->after('nam_kinh_nghiem')
                ->comment('Số chứng chỉ cá nhân cho kỹ năng này');
            $table->string('hinh_anh', 255)->nullable()->after('so_chung_chi')
                ->comment('Đường dẫn hình ảnh chứng chỉ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xoá cột mới ở nguoi_dung_ky_nangs
        Schema::table('nguoi_dung_ky_nangs', function (Blueprint $table) {
            $table->dropColumn(['so_chung_chi', 'hinh_anh']);
        });

        // Xoá cột mới ở ky_nangs
        Schema::table('ky_nangs', function (Blueprint $table) {
            $table->dropColumn(['mo_ta', 'icon']);
        });

        // Thêm lại cột cũ vào ky_nangs
        Schema::table('ky_nangs', function (Blueprint $table) {
            $table->integer('so_chung_chi')->default(0)->after('ten_ky_nang');
            $table->string('hinh_anh', 150)->nullable()->after('so_chung_chi');
        });
    }
};
