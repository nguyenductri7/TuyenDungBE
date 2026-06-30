<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hr_audit_logs');
    }

    public function down(): void
    {
        Schema::create('hr_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cong_ty_id')->constrained('cong_tys')->cascadeOnDelete();
            $table->foreignId('nguoi_thuc_hien_id')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->foreignId('nguoi_bi_tac_dong_id')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->string('loai_su_kien', 80);
            $table->text('mo_ta');
            $table->json('du_lieu_bo_sung')->nullable();
            $table->timestamps();
        });
    }
};
