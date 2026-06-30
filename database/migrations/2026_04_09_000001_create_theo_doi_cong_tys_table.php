<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theo_doi_cong_tys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->unsignedBigInteger('cong_ty_id');
            $table->timestamps();

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->foreign('cong_ty_id')
                ->references('id')
                ->on('cong_tys')
                ->onDelete('cascade');

            $table->unique(['nguoi_dung_id', 'cong_ty_id']);
            $table->index('cong_ty_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theo_doi_cong_tys');
    }
};
