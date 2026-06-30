<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('vector_embeddings');
    }

    public function down(): void
    {
        // Feature removed; rollback does not recreate this table.
    }
};
