<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('su_dung_tinh_nang_ais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dungs')->cascadeOnDelete();
            $table->string('feature_code', 80);
            $table->unsignedInteger('so_luong')->default(1);
            $table->unsignedBigInteger('don_gia_ap_dung')->default(0);
            $table->unsignedBigInteger('so_tien_du_kien')->default(0);
            $table->unsignedBigInteger('so_tien_thuc_te')->default(0);
            $table->string('billing_mode', 30)->default('wallet');
            $table->string('trang_thai', 30)->default('pending');
            $table->string('idempotency_key', 120);
            $table->string('tham_chieu_loai', 80)->nullable();
            $table->unsignedBigInteger('tham_chieu_id')->nullable();
            $table->foreignId('bien_dong_vi_reserve_id')->nullable()->constrained('bien_dong_vi')->nullOnDelete();
            $table->foreignId('bien_dong_vi_ket_toan_id')->nullable()->constrained('bien_dong_vi')->nullOnDelete();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['nguoi_dung_id', 'idempotency_key']);
            $table->index(['feature_code', 'created_at']);
            $table->index(['trang_thai', 'created_at']);
            $table->index(['tham_chieu_loai', 'tham_chieu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('su_dung_tinh_nang_ais');
    }
};
