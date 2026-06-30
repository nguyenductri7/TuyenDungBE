<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->decimal('kinh_nghiem_nam', 4, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ho_sos', function (Blueprint $table) {
            $table->integer('kinh_nghiem_nam')->default(0)->change();
        });
    }
};
