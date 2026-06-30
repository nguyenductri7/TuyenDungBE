<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xóa cột nguoi_phong_van khỏi interview_rounds.
     * Tên người phỏng vấn giờ được lấy qua interviewer_user_id FK → nguoi_dungs.ho_ten
     */
    public function up(): void
    {
        Schema::table('interview_rounds', function (Blueprint $table) {
            $table->dropColumn('nguoi_phong_van');
        });
    }

    /**
     * Rollback: thêm lại cột để có thể revert migration.
     */
    public function down(): void
    {
        Schema::table('interview_rounds', function (Blueprint $table) {
            $table->string('nguoi_phong_van', 255)->nullable()->after('hinh_thuc_phong_van');
        });
    }
};
