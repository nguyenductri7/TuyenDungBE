<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->string('actor_role', 50)->nullable();
            $table->foreignId('company_id')->nullable()->constrained('cong_tys')->nullOnDelete();
            $table->string('target_type', 150)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('action', 120);
            $table->text('description');
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index('actor_id');
            $table->index('company_id');
            $table->index('action');
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
