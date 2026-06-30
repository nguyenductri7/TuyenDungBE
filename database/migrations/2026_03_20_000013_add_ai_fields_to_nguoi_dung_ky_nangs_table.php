<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nguoi_dung_ky_nangs', function (Blueprint $table) {
            $table->enum('nguon_du_lieu', ['manual', 'cv_parser', 'ai_inferred'])
                ->default('manual')
                ->after('hinh_anh');
            $table->decimal('do_tin_cay', 5, 2)->nullable()->after('nguon_du_lieu');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('nguoi_dung_ky_nangs', function (Blueprint $table) {
            $table->dropColumn(['nguon_du_lieu', 'do_tin_cay', 'updated_at']);
        });
    }
};
