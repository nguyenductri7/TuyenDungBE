<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bien_dong_vi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vi_nguoi_dung_id')->constrained('vi_nguoi_dungs')->cascadeOnDelete();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dungs')->cascadeOnDelete();
            $table->string('loai_bien_dong', 50);
            $table->unsignedBigInteger('so_tien');
            $table->unsignedBigInteger('so_du_truoc')->default(0);
            $table->unsignedBigInteger('so_du_sau')->default(0);
            $table->unsignedBigInteger('tam_giu_truoc')->default(0);
            $table->unsignedBigInteger('tam_giu_sau')->default(0);
            $table->string('trang_thai', 30)->default('completed');
            $table->string('tham_chieu_loai', 80)->nullable();
            $table->unsignedBigInteger('tham_chieu_id')->nullable();
            $table->string('idempotency_key', 120)->nullable();
            $table->string('mo_ta', 255)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['nguoi_dung_id', 'idempotency_key']);
            $table->index(['vi_nguoi_dung_id', 'created_at']);
            $table->index(['tham_chieu_loai', 'tham_chieu_id']);
            $table->index(['loai_bien_dong', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bien_dong_vi');
    }
};
