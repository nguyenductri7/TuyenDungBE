<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('cong_ty_vai_tro_noi_bos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('cong_ty_vai_tro_noi_bos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cong_ty_id');
            $table->string('ma_vai_tro', 80);
            $table->string('ten_vai_tro', 120);
            $table->text('mo_ta')->nullable();
            $table->string('vai_tro_goc', 50)->default('viewer');
            $table->unsignedBigInteger('duoc_tao_boi')->nullable();
            $table->timestamps();

            $table->unique(['cong_ty_id', 'ma_vai_tro']);
            $table->foreign('cong_ty_id')->references('id')->on('cong_tys')->onDelete('cascade');
            $table->foreign('duoc_tao_boi')->references('id')->on('nguoi_dungs')->nullOnDelete();
        });
    }
};
