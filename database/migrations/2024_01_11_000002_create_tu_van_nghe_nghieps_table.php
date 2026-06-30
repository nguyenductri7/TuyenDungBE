<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tu_van_nghe_nghieps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->unsignedBigInteger('ho_so_id')->nullable()->comment('Dựa trên hồ sơ nòng cốt nào để phân tích');
            $table->string('nghe_de_xuat', 150)->comment('Nghề nghiệp AI gợi ý. VD: Backend Developer');
            $table->float('muc_do_phu_hop')->comment('Phần trăm phù hợp với định hướng này');
            $table->text('goi_y_ky_nang_bo_sung')->nullable()->comment('List skill cần học thêm để đạt nghề này');
            $table->timestamps();

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->foreign('ho_so_id')
                ->references('id')
                ->on('ho_sos')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tu_van_nghe_nghieps');
    }
};
