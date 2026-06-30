<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cong_ty_vai_tro_noi_bos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cong_ty_id')->constrained('cong_tys')->cascadeOnDelete();
            $table->string('ma_vai_tro', 80);
            $table->string('ten_vai_tro', 120);
            $table->text('mo_ta')->nullable();
            $table->string('vai_tro_goc', 50)->default('viewer');
            $table->foreignId('duoc_tao_boi')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cong_ty_id', 'ma_vai_tro']);
            $table->index(['cong_ty_id', 'vai_tro_goc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cong_ty_vai_tro_noi_bos');
    }
};
