<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tin_tuyen_dungs', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('trang_thai');
            $table->timestamp('reactivated_at')->nullable()->after('published_at');
            $table->index('published_at');
            $table->index('reactivated_at');
        });

        DB::table('tin_tuyen_dungs')
            ->whereNull('published_at')
            ->update([
                'published_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tin_tuyen_dungs', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropIndex(['reactivated_at']);
            $table->dropColumn(['published_at', 'reactivated_at']);
        });
    }
};
