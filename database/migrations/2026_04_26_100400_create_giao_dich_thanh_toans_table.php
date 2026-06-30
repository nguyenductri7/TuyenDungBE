<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giao_dich_thanh_toans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dungs')->cascadeOnDelete();
            $table->foreignId('vi_nguoi_dung_id')->constrained('vi_nguoi_dungs')->cascadeOnDelete();
            $table->string('gateway', 40);
            $table->string('ma_giao_dich_noi_bo', 120)->unique();
            $table->string('ma_yeu_cau', 120)->unique();
            $table->string('ma_giao_dich_gateway', 120)->nullable();
            $table->string('loai_giao_dich', 50)->default('topup_wallet');
            $table->unsignedBigInteger('so_tien');
            $table->string('noi_dung', 255)->nullable();
            $table->string('redirect_url', 2048)->nullable();
            $table->string('trang_thai', 30)->default('pending');
            $table->json('raw_request_json')->nullable();
            $table->json('raw_response_json')->nullable();
            $table->json('return_payload_json')->nullable();
            $table->json('ipn_payload_json')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'created_at']);
            $table->index(['trang_thai', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giao_dich_thanh_toans');
    }
};
