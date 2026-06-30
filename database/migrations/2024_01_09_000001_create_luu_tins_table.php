<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('luu_tins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->unsignedBigInteger('tin_tuyen_dung_id');
            $table->timestamps(); // create_at, updated_at

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->foreign('tin_tuyen_dung_id')
                ->references('id')
                ->on('tin_tuyen_dungs')
                ->onDelete('cascade');

            // Một người dùng chỉ có thể lưu 1 tin 1 lần
            $table->unique(['nguoi_dung_id', 'tin_tuyen_dung_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('luu_tins');
    }
};
