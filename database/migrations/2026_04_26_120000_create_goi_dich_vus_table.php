<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goi_dich_vus', function (Blueprint $table) {
            $table->id();
            $table->string('ma_goi')->unique();
            $table->string('ten_goi');
            $table->text('mo_ta')->nullable();
            $table->unsignedBigInteger('gia')->default(0);
            $table->string('chu_ky', 32)->default('monthly');
            $table->string('trang_thai', 32)->default('active');
            $table->boolean('is_free')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goi_dich_vus');
    }
};
