<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ung_tuyens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tin_tuyen_dung_id');
            $table->unsignedBigInteger('ho_so_id');
            $table->tinyInteger('trang_thai')->default(0)->comment('0: Chờ duyệt, 1: Đã xem, 2: Chấp nhận, 3: Từ chối');
            $table->text('thu_xin_viec')->nullable();
            $table->timestamp('ngay_hen_phong_van')->nullable();
            $table->string('ket_qua_phong_van')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamp('thoi_gian_ung_tuyen')->useCurrent();
            $table->timestamps();

            $table->foreign('tin_tuyen_dung_id')
                ->references('id')
                ->on('tin_tuyen_dungs')
                ->onDelete('cascade');

            $table->foreign('ho_so_id')
                ->references('id')
                ->on('ho_sos')
                ->onDelete('cascade');

            // Mỗi hồ sơ chỉ nộp vào 1 tin 1 lần
            $table->unique(['tin_tuyen_dung_id', 'ho_so_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ung_tuyens');
    }
};
