<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vi_nguoi_dungs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->unique()->constrained('nguoi_dungs')->cascadeOnDelete();
            $table->unsignedBigInteger('so_du_hien_tai')->default(0);
            $table->unsignedBigInteger('so_du_tam_giu')->default(0);
            $table->string('don_vi_tien_te', 10)->default('VND');
            $table->string('trang_thai', 30)->default('active');
            $table->timestamps();

            $table->index(['trang_thai', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vi_nguoi_dungs');
    }
};
