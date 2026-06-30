<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 80);
            $table->string('endpoint', 160)->nullable();
            $table->string('provider', 80)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('model_version', 160)->nullable();
            $table->string('status', 30)->default('success');
            $table->boolean('used_fallback')->default(false);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('cong_tys')->nullOnDelete();
            $table->string('request_ref_type', 80)->nullable();
            $table->unsignedBigInteger('request_ref_id')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['feature', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['used_fallback', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['request_ref_type', 'request_ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
