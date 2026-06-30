<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_definitions', function (Blueprint $table) {
            $table->string('mapped_permission_key', 100)->nullable()->after('description');
            $table->index(['scope', 'mapped_permission_key']);
        });
    }

    public function down(): void
    {
        Schema::table('permission_definitions', function (Blueprint $table) {
            $table->dropIndex(['scope', 'mapped_permission_key']);
            $table->dropColumn('mapped_permission_key');
        });
    }
};
