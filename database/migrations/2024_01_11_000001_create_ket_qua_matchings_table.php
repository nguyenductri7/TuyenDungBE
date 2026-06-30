<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ket_qua_matchings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ho_so_id');
            $table->unsignedBigInteger('tin_tuyen_dung_id');
            $table->float('diem_phu_hop')->comment('Điểm phù hợp từ thuật toán (0-100)');
            $table->json('chi_tiet_diem')->nullable()->comment('Lưu lý do/cấu thành điểm');
            $table->text('danh_sach_ky_nang_thieu')->nullable()->comment('Kỹ năng còn thiếu so với JD');
            $table->string('model_version', 50)->comment('Phiên bản AI/Thuật toán tính điểm');
            $table->timestamp('thoi_gian_match')->useCurrent();
            // Thêm timestamps mặc định của Laravel để tương thích tốt với Eloquent
            $table->timestamps();

            $table->foreign('ho_so_id')
                ->references('id')
                ->on('ho_sos')
                ->onDelete('cascade');

            $table->foreign('tin_tuyen_dung_id')
                ->references('id')
                ->on('tin_tuyen_dungs')
                ->onDelete('cascade');

            // Mỗi version thuật toán chỉ cho 1 hồ sơ match 1 tin 1 lần (để tránh clone)
            $table->unique(['ho_so_id', 'tin_tuyen_dung_id', 'model_version'], 'kqm_unique_match');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ket_qua_matchings');
    }
};
