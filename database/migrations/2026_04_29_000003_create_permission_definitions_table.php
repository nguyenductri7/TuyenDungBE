<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 30);
            $table->string('key', 100);
            $table->string('label', 150);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('default_enabled')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['scope', 'key']);
            $table->index(['scope', 'is_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_definitions');
    }
};
