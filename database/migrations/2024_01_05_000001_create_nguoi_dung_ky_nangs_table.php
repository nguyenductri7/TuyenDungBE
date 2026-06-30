<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nguoi_dung_ky_nangs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->unsignedBigInteger('ky_nang_id');
            // muc_do: 1 = Cơ bản, 2 = Trung bình, 3 = Khá, 4 = Giỏi, 5 = Chuyên gia
            $table->tinyInteger('muc_do')->default(1)
                ->comment('1: co_ban, 2: trung_binh, 3: kha, 4: gioi, 5: chuyen_gia');
            $table->integer('nam_kinh_nghiem')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->foreign('ky_nang_id')
                ->references('id')
                ->on('ky_nangs')
                ->onDelete('cascade');

            // Mỗi người dùng chỉ có 1 bản ghi cho 1 kỹ năng
            $table->unique(['nguoi_dung_id', 'ky_nang_id'], 'ndkn_nguoi_dung_ky_nang_unique');
            $table->index('nguoi_dung_id');
            $table->index('ky_nang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nguoi_dung_ky_nangs');
    }
};
