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
        Schema::create('ky_nangs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ten_ky_nang', 150);
            $table->integer('so_chung_chi')->default(0)
                ->comment('Số chứng chỉ liên quan');
            $table->string('hinh_anh', 150)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ky_nangs');
    }
};
