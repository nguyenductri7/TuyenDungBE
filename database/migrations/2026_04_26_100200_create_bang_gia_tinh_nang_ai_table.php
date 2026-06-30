<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bang_gia_tinh_nang_ai', function (Blueprint $table) {
            $table->id();
            $table->string('feature_code', 80)->unique();
            $table->string('ten_hien_thi', 150);
            $table->unsignedBigInteger('don_gia');
            $table->string('don_vi_tinh', 50)->default('request');
            $table->string('trang_thai', 30)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bang_gia_tinh_nang_ai');
    }
};
