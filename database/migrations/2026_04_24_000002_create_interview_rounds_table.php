<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ung_tuyen_id')->constrained('ung_tuyens')->cascadeOnDelete();
            $table->unsignedSmallInteger('thu_tu')->default(1);
            $table->string('ten_vong', 255);
            $table->string('loai_vong', 50)->default('hr');
            $table->unsignedTinyInteger('trang_thai')->default(0);
            $table->timestamp('ngay_hen_phong_van')->nullable();
            $table->string('hinh_thuc_phong_van', 20)->nullable();
            $table->string('nguoi_phong_van', 255)->nullable();
            $table->foreignId('interviewer_user_id')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->string('link_phong_van', 2048)->nullable();
            $table->unsignedTinyInteger('trang_thai_tham_gia')->nullable();
            $table->timestamp('thoi_gian_phan_hoi')->nullable();
            $table->timestamp('thoi_gian_gui_nhac_lich')->nullable();
            $table->text('ket_qua')->nullable();
            $table->decimal('diem_so', 5, 2)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->longText('rubric_danh_gia_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->timestamps();

            $table->index(['ung_tuyen_id', 'thu_tu']);
            $table->index(['ung_tuyen_id', 'trang_thai']);
            $table->index('ngay_hen_phong_van');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_rounds');
    }
};
