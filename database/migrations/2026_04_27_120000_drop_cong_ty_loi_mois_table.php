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
        Schema::dropIfExists('cong_ty_loi_mois');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('cong_ty_loi_mois', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cong_ty_id')->constrained('cong_tys')->cascadeOnDelete();
            $table->foreignId('nguoi_dung_id')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->string('email', 150);
            $table->string('vai_tro_noi_bo', 50)->default('recruiter');
            $table->string('trang_thai', 50)->default('pending');
            $table->foreignId('duoc_moi_boi')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->timestamp('phan_hoi_luc')->nullable();
            $table->timestamps();
        });
    }
};
