<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nganh_nghes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ten_nganh', 150);
            $table->string('slug', 150)->unique();
            $table->text('mo_ta')->nullable();
            $table->unsignedBigInteger('danh_muc_cha_id')->nullable()
                ->comment('ID ngành nghề cha (null = ngành gốc)');
            $table->string('icon', 100)->nullable();
            // trang_thai: 1 = hiển thị, 0 = ẩn
            $table->tinyInteger('trang_thai')->default(1)
                ->comment('1: hien_thi, 0: an');
            $table->timestamps();

            $table->foreign('danh_muc_cha_id')
                ->references('id')
                ->on('nganh_nghes')
                ->onDelete('set null');

            $table->index('danh_muc_cha_id');
            $table->index('trang_thai');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nganh_nghes');
    }
};
