<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nguoi_dungs', function (Blueprint $table) {
            if (Schema::hasColumn('nguoi_dungs', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nguoi_dungs', function (Blueprint $table) {
            if (!Schema::hasColumn('nguoi_dungs', 'remember_token')) {
                $table->string('remember_token', 100)->nullable()->after('mat_khau');
            }
        });
    }
};
