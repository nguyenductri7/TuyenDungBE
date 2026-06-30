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
        Schema::create('ho_sos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->string('tieu_de_ho_so', 200);
            $table->text('muc_tieu_nghe_nghiep')->nullable();
            $table->string('trinh_do', 100)->nullable();
            $table->integer('kinh_nghiem_nam')->default(0);
            $table->text('mo_ta_ban_than')->nullable();
            $table->string('file_cv', 255)->nullable();
            // trang_thai: 1 = công khai, 0 = ẩn
            $table->tinyInteger('trang_thai')->default(1)
                ->comment('1: cong_khai, 0: an');
            $table->timestamps();

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->index('nguoi_dung_id');
            $table->index('trang_thai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ho_sos');
    }
};
