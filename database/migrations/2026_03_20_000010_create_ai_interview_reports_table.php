<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_interview_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('session_id')->unique();
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->unsignedBigInteger('tin_tuyen_dung_id')->nullable();
            $table->decimal('tong_diem', 5, 2);
            $table->decimal('diem_ky_thuat', 5, 2)->nullable();
            $table->decimal('diem_giao_tiep', 5, 2)->nullable();
            $table->decimal('diem_phu_hop_jd', 5, 2)->nullable();
            $table->json('diem_manh')->nullable();
            $table->json('diem_yeu')->nullable();
            $table->longText('de_xuat_cai_thien')->nullable();
            $table->timestamps();

            $table->foreign('session_id')
                ->references('id')
                ->on('ai_chat_sessions')
                ->onDelete('cascade');

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->foreign('tin_tuyen_dung_id')
                ->references('id')
                ->on('tin_tuyen_dungs')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interview_reports');
    }
};
