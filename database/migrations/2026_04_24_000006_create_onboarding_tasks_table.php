<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_plan_id')->constrained('onboarding_plans')->cascadeOnDelete();
            $table->string('tieu_de', 180);
            $table->text('mo_ta')->nullable();
            $table->date('han_hoan_tat')->nullable();
            $table->string('nguoi_phu_trach', 30)->default('candidate');
            $table->string('trang_thai', 30)->default('pending');
            $table->unsignedSmallInteger('thu_tu')->default(1);
            $table->timestamp('hoan_tat_luc')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['onboarding_plan_id', 'thu_tu']);
            $table->index(['onboarding_plan_id', 'trang_thai']);
            $table->index(['nguoi_phu_trach', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_tasks');
    }
};
