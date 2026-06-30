<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goi_dich_vu_tinh_nangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goi_dich_vu_id')->constrained('goi_dich_vus')->cascadeOnDelete();
            $table->string('feature_code');
            $table->unsignedInteger('quota')->nullable();
            $table->string('reset_cycle', 32)->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->timestamps();

            $table->unique(['goi_dich_vu_id', 'feature_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goi_dich_vu_tinh_nangs');
    }
};
