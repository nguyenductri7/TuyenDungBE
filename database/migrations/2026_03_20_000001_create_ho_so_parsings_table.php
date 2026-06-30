<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ho_so_parsings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ho_so_id')->unique();
            $table->longText('raw_text')->nullable();
            $table->string('parsed_name', 150)->nullable();
            $table->string('parsed_email', 150)->nullable();
            $table->string('parsed_phone', 20)->nullable();
            $table->json('parsed_skills_json')->nullable();
            $table->json('parsed_experience_json')->nullable();
            $table->json('parsed_education_json')->nullable();
            $table->tinyInteger('parse_status')->default(0)
                ->comment('0: pending, 1: success, 2: failed');
            $table->string('parser_version', 50)->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('ho_so_id')
                ->references('id')
                ->on('ho_sos')
                ->onDelete('cascade');

            $table->index('parse_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ho_so_parsings');
    }
};
