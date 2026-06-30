<?php

use App\Models\NguoiDung;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nguoi_dungs', function (Blueprint $table) {
            $table->string('cap_admin', 32)->nullable()->after('vai_tro');
        });

        DB::transaction(function (): void {
            DB::table('nguoi_dungs')
                ->where('vai_tro', '!=', NguoiDung::VAI_TRO_ADMIN)
                ->update(['cap_admin' => null]);

            $adminIds = DB::table('nguoi_dungs')
                ->where('vai_tro', NguoiDung::VAI_TRO_ADMIN)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            if ($adminIds === []) {
                return;
            }

            $superAdminId = array_shift($adminIds);

            DB::table('nguoi_dungs')
                ->where('id', $superAdminId)
                ->update(['cap_admin' => NguoiDung::CAP_ADMIN_SUPER_ADMIN]);

            if ($adminIds !== []) {
                DB::table('nguoi_dungs')
                    ->whereIn('id', $adminIds)
                    ->update(['cap_admin' => NguoiDung::CAP_ADMIN_ADMIN]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('nguoi_dungs', function (Blueprint $table) {
            $table->dropColumn('cap_admin');
        });
    }
};
