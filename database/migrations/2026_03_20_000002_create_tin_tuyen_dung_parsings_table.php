<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tin_tuyen_dung_parsings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tin_tuyen_dung_id')->unique();
            $table->longText('raw_text')->nullable();
            $table->json('parsed_skills_json')->nullable();
            $table->json('parsed_requirements_json')->nullable();
            $table->json('parsed_benefits_json')->nullable();
            $table->json('parsed_salary_json')->nullable();
            $table->json('parsed_location_json')->nullable();
            $table->tinyInteger('parse_status')->default(0)
                ->comment('0: pending, 1: success, 2: failed');
            $table->string('parser_version', 50)->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('tin_tuyen_dung_id')
                ->references('id')
                ->on('tin_tuyen_dungs')
                ->onDelete('cascade');

            $table->index('parse_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tin_tuyen_dung_parsings');
    }
};
