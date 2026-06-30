<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cong_ty_nguoi_dungs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cong_ty_id');
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->string('vai_tro_noi_bo', 50)->default('member')
                ->comment('owner, member');
            $table->unsignedBigInteger('duoc_tao_boi')->nullable();
            $table->timestamps();

            $table->foreign('cong_ty_id')
                ->references('id')
                ->on('cong_tys')
                ->onDelete('cascade');

            $table->foreign('nguoi_dung_id')
                ->references('id')
                ->on('nguoi_dungs')
                ->onDelete('cascade');

            $table->foreign('duoc_tao_boi')
                ->references('id')
                ->on('nguoi_dungs')
                ->nullOnDelete();

            $table->unique(['cong_ty_id', 'nguoi_dung_id']);
            $table->unique('nguoi_dung_id');
            $table->index('vai_tro_noi_bo');
        });

        $now = now();

        DB::table('cong_tys')
            ->whereNotNull('nguoi_dung_id')
            ->orderBy('id')
            ->get(['id', 'nguoi_dung_id'])
            ->each(function ($company) use ($now): void {
                DB::table('cong_ty_nguoi_dungs')->updateOrInsert(
                    ['nguoi_dung_id' => $company->nguoi_dung_id],
                    [
                        'cong_ty_id' => $company->id,
                        'vai_tro_noi_bo' => 'owner',
                        'duoc_tao_boi' => $company->nguoi_dung_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('cong_ty_nguoi_dungs');
    }
};
