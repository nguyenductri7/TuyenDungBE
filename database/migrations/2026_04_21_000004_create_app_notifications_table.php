<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dungs')->cascadeOnDelete();
            $table->string('loai', 80);
            $table->string('tieu_de', 180);
            $table->text('noi_dung');
            $table->string('duong_dan', 255)->nullable();
            $table->json('du_lieu_bo_sung')->nullable();
            $table->timestamp('da_doc_luc')->nullable();
            $table->timestamps();

            $table->index(['nguoi_dung_id', 'da_doc_luc', 'created_at']);
            $table->index(['nguoi_dung_id', 'loai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
