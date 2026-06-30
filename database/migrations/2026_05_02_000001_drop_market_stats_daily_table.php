<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('market_stats_daily');
    }

    public function down(): void
    {
        // Market dashboard has been removed from the system.
    }
};
