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
        Schema::create('cong_tys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nguoi_dung_id')->comment('NTD sở hữu');
            $table->string('ten_cong_ty', 200);
            $table->string('ma_so_thue', 20)->unique();
            $table->text('mo_ta')->nullable();
            $table->string('dia_chi', 255)->nullable();
            $table->string('dien_thoai', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('logo', 255)->nullable();
            $table->unsignedBigInteger('nganh_nghe_id')->nullable()->comment('Ngành nghề chính');
            $table->string('quy_mo', 50)->nullable()
                ->comment('1-10, 11-50, 51-200, 201-500, 500+');
            $table->tinyInteger('trang_thai')->default(1)
                ->comment('1: hoat_dong, 0: tam_ngung');
            $table->timestamps();

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->foreign('nganh_nghe_id')
                ->references('id')
                ->on('nganh_nghes')
                ->onDelete('set null');

            $table->index('nguoi_dung_id');
            $table->index('nganh_nghe_id');
            $table->index('trang_thai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cong_tys');
    }
};
