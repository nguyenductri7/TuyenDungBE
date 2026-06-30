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
        Schema::create('nguoi_dungs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ho_ten', 150);
            $table->string('email', 150)->unique();
            $table->string('mat_khau', 255);
            $table->string('so_dien_thoai', 20)->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->enum('gioi_tinh', ['nam', 'nu', 'khac'])->nullable();
            $table->string('dia_chi', 255)->nullable();
            $table->string('anh_dai_dien', 255)->nullable();
            // vai_tro: 0 = ứng viên, 1 = nhà tuyển dụng, 2 = admin
            $table->tinyInteger('vai_tro')->default(0)
                ->comment('0: ung_vien, 1: nha_tuyen_dung, 2: admin');
            $table->tinyInteger('trang_thai')->default(1)
                ->comment('1: active, 0: bi_khoa');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nguoi_dungs');
    }
};
