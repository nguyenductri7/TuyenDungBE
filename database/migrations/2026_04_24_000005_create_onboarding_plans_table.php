<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ung_tuyen_id')->unique()->constrained('ung_tuyens')->cascadeOnDelete();
            $table->foreignId('cong_ty_id')->constrained('cong_tys')->cascadeOnDelete();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dungs')->cascadeOnDelete();
            $table->foreignId('hr_phu_trach_id')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->date('ngay_bat_dau')->nullable();
            $table->string('dia_diem_lam_viec', 255)->nullable();
            $table->string('trang_thai', 40)->default('not_started');
            $table->text('loi_chao_mung')->nullable();
            $table->text('ghi_chu_noi_bo')->nullable();
            $table->text('ghi_chu_ung_vien')->nullable();
            $table->json('tai_lieu_can_chuan_bi_json')->nullable();
            $table->timestamp('hoan_tat_luc')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->timestamps();

            $table->index(['cong_ty_id', 'trang_thai']);
            $table->index(['nguoi_dung_id', 'trang_thai']);
            $table->index('ngay_bat_dau');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_plans');
    }
};
