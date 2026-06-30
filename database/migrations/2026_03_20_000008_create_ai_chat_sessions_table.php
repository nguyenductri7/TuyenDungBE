<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->enum('session_type', ['career_consultant', 'mock_interview']);
            $table->unsignedBigInteger('related_ho_so_id')->nullable();
            $table->unsignedBigInteger('related_tin_tuyen_dung_id')->nullable();
            $table->tinyInteger('status')->default(1)
                ->comment('0: closed, 1: active, 2: archived');
            $table->string('title', 255)->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->foreign('related_ho_so_id')
                ->references('id')
                ->on('ho_sos')
                ->onDelete('set null');

            $table->foreign('related_tin_tuyen_dung_id')
                ->references('id')
                ->on('tin_tuyen_dungs')
                ->onDelete('set null');

            $table->index('session_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_sessions');
    }
};
