<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tin_tuyen_dungs', function (Blueprint $table) {
            $table->timestamp('featured_activated_at')->nullable()->after('reactivated_at');
            $table->timestamp('featured_until')->nullable()->after('featured_activated_at');
            $table->index('featured_until');
        });
    }

    public function down(): void
    {
        Schema::table('tin_tuyen_dungs', function (Blueprint $table) {
            $table->dropIndex(['featured_until']);
            $table->dropColumn([
                'featured_activated_at',
                'featured_until',
            ]);
        });
    }
};
