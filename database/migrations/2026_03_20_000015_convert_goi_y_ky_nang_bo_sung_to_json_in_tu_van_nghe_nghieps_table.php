<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Chuyển dữ liệu text cũ sang JSON hợp lệ trước khi đổi kiểu cột.
        DB::statement("
            UPDATE tu_van_nghe_nghieps
            SET goi_y_ky_nang_bo_sung = JSON_OBJECT('raw_text', goi_y_ky_nang_bo_sung)
            WHERE goi_y_ky_nang_bo_sung IS NOT NULL
              AND JSON_VALID(goi_y_ky_nang_bo_sung) = 0
        ");

        DB::statement("
            ALTER TABLE tu_van_nghe_nghieps
            MODIFY goi_y_ky_nang_bo_sung JSON NULL
        ");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE tu_van_nghe_nghieps
            MODIFY goi_y_ky_nang_bo_sung TEXT NULL
        ");
    }
};
