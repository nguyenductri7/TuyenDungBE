<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tin_tuyen_dung_ky_nangs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tin_tuyen_dung_id');
            $table->unsignedBigInteger('ky_nang_id');
            $table->tinyInteger('muc_do_yeu_cau')->nullable();
            $table->boolean('bat_buoc')->default(false);
            $table->decimal('trong_so', 5, 2)->nullable();
            $table->enum('nguon_du_lieu', ['manual', 'jd_parser', 'ai_inferred'])->default('manual');
            $table->decimal('do_tin_cay', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('tin_tuyen_dung_id')
                ->references('id')
                ->on('tin_tuyen_dungs')
                ->onDelete('cascade');

            $table->foreign('ky_nang_id')
                ->references('id')
                ->on('ky_nangs')
                ->onDelete('cascade');

            $table->unique(['tin_tuyen_dung_id', 'ky_nang_id'], 'ttdkn_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tin_tuyen_dung_ky_nangs');
    }
};
