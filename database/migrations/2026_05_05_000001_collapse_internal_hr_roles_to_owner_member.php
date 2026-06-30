<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cong_ty_nguoi_dungs')) {
            DB::table('cong_ty_nguoi_dungs')
                ->where(function ($query): void {
                    $query->whereNull('vai_tro_noi_bo')
                        ->orWhere('vai_tro_noi_bo', '!=', 'owner');
                })
                ->update([
                    'vai_tro_noi_bo' => 'member',
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('cong_ty_vai_tro_noi_bos')) {
            DB::table('cong_ty_vai_tro_noi_bos')->delete();
        }
    }

    public function down(): void
    {
        // Irreversible cleanup: old role names and custom role definitions are intentionally removed.
    }
};
