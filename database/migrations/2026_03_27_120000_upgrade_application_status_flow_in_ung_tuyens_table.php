<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('ung_tuyens')
                ->where('trang_thai', 2)
                ->update(['trang_thai' => 4]);

            DB::table('ung_tuyens')
                ->where('trang_thai', 3)
                ->update(['trang_thai' => 5]);

            DB::table('ung_tuyens')
                ->whereIn('trang_thai', [0, 1])
                ->whereNotNull('ngay_hen_phong_van')
                ->update(['trang_thai' => 2]);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('ung_tuyens')
                ->where('trang_thai', 2)
                ->update(['trang_thai' => 1]);

            DB::table('ung_tuyens')
                ->where('trang_thai', 4)
                ->update(['trang_thai' => 2]);

            DB::table('ung_tuyens')
                ->where('trang_thai', 5)
                ->update(['trang_thai' => 3]);
        });
    }
};
