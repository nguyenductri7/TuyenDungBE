<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nguoi_dung_goi_dich_vus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dungs')->cascadeOnDelete();
            $table->foreignId('goi_dich_vu_id')->constrained('goi_dich_vus')->cascadeOnDelete();
            $table->foreignId('giao_dich_thanh_toan_id')->nullable()->constrained('giao_dich_thanh_toans')->nullOnDelete();
            $table->timestamp('ngay_bat_dau')->nullable();
            $table->timestamp('ngay_het_han')->nullable();
            $table->string('trang_thai', 32)->default('pending');
            $table->boolean('auto_renew')->default(false);
            $table->timestamps();

            $table->index(['nguoi_dung_id', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nguoi_dung_goi_dich_vus');
    }
};
