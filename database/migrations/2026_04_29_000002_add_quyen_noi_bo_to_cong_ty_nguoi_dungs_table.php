<?php

use App\Models\CongTy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cong_ty_nguoi_dungs', function (Blueprint $table) {
            $table->json('quyen_noi_bo')->nullable()->after('vai_tro_noi_bo');
        });

        DB::table('cong_ty_nguoi_dungs')
            ->orderBy('id')
            ->get(['id', 'vai_tro_noi_bo'])
            ->each(function ($membership): void {
                DB::table('cong_ty_nguoi_dungs')
                    ->where('id', $membership->id)
                    ->update([
                        'quyen_noi_bo' => json_encode(CongTy::defaultHrPermissionsForRole($membership->vai_tro_noi_bo)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('cong_ty_nguoi_dungs', function (Blueprint $table) {
            $table->dropColumn('quyen_noi_bo');
        });
    }
};
