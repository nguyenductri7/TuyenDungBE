<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateLegacyInterviewData();

        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->dropColumn([
                'ngay_hen_phong_van',
                'vong_phong_van_hien_tai',
                'trang_thai_tham_gia_phong_van',
                'thoi_gian_phan_hoi_phong_van',
                'thoi_gian_gui_nhac_lich',
                'hinh_thuc_phong_van',
                'nguoi_phong_van',
                'link_phong_van',
                'ket_qua_phong_van',
                'rubric_danh_gia_phong_van',
            ]);
        });
    }

    private function migrateLegacyInterviewData(): void
    {
        if (!Schema::hasTable('interview_rounds') || !Schema::hasColumn('ung_tuyens', 'ngay_hen_phong_van')) {
            return;
        }

        $validRoundTypes = ['hr', 'technical', 'manager', 'final', 'culture', 'other'];

        DB::table('ung_tuyens')
            ->select([
                'ung_tuyens.id',
                'ung_tuyens.ngay_hen_phong_van',
                'ung_tuyens.vong_phong_van_hien_tai',
                'ung_tuyens.trang_thai_tham_gia_phong_van',
                'ung_tuyens.thoi_gian_phan_hoi_phong_van',
                'ung_tuyens.thoi_gian_gui_nhac_lich',
                'ung_tuyens.hinh_thuc_phong_van',
                'ung_tuyens.nguoi_phong_van',
                'ung_tuyens.link_phong_van',
                'ung_tuyens.ket_qua_phong_van',
                'ung_tuyens.rubric_danh_gia_phong_van',
            ])
            ->leftJoin('interview_rounds', 'interview_rounds.ung_tuyen_id', '=', 'ung_tuyens.id')
            ->whereNull('interview_rounds.id')
            ->where(function ($query): void {
                $query
                    ->whereNotNull('ung_tuyens.ngay_hen_phong_van')
                    ->orWhereNotNull('ung_tuyens.vong_phong_van_hien_tai')
                    ->orWhereNotNull('ung_tuyens.trang_thai_tham_gia_phong_van')
                    ->orWhereNotNull('ung_tuyens.thoi_gian_phan_hoi_phong_van')
                    ->orWhereNotNull('ung_tuyens.thoi_gian_gui_nhac_lich')
                    ->orWhereNotNull('ung_tuyens.hinh_thuc_phong_van')
                    ->orWhereNotNull('ung_tuyens.nguoi_phong_van')
                    ->orWhereNotNull('ung_tuyens.link_phong_van')
                    ->orWhereNotNull('ung_tuyens.ket_qua_phong_van')
                    ->orWhereNotNull('ung_tuyens.rubric_danh_gia_phong_van');
            })
            ->orderBy('ung_tuyens.id')
            ->chunk(200, function ($applications) use ($validRoundTypes): void {
                $now = now();
                $rows = [];

                foreach ($applications as $application) {
                    $roundType = $application->vong_phong_van_hien_tai;
                    if (!in_array($roundType, $validRoundTypes, true)) {
                        $roundType = $application->ngay_hen_phong_van ? 'technical' : 'hr';
                    }

                    $rows[] = [
                        'ung_tuyen_id' => $application->id,
                        'thu_tu' => 1,
                        'ten_vong' => $roundType === 'hr' ? 'HR screening' : 'Phỏng vấn',
                        'loai_vong' => $roundType,
                        'trang_thai' => $application->ket_qua_phong_van ? 1 : 0,
                        'ngay_hen_phong_van' => $application->ngay_hen_phong_van,
                        'hinh_thuc_phong_van' => $application->hinh_thuc_phong_van,
                        'nguoi_phong_van' => $application->nguoi_phong_van,
                        'link_phong_van' => $application->link_phong_van,
                        'trang_thai_tham_gia' => $application->trang_thai_tham_gia_phong_van,
                        'thoi_gian_phan_hoi' => $application->thoi_gian_phan_hoi_phong_van,
                        'thoi_gian_gui_nhac_lich' => $application->thoi_gian_gui_nhac_lich,
                        'ket_qua' => $application->ket_qua_phong_van,
                        'rubric_danh_gia_json' => $application->rubric_danh_gia_phong_van,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows) {
                    DB::table('interview_rounds')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::table('ung_tuyens', function (Blueprint $table): void {
            $table->timestamp('ngay_hen_phong_van')->nullable()->after('thu_xin_viec_ai');
            $table->string('vong_phong_van_hien_tai', 32)->nullable()->after('ngay_hen_phong_van');
            $table->unsignedTinyInteger('trang_thai_tham_gia_phong_van')->nullable()->after('vong_phong_van_hien_tai');
            $table->timestamp('thoi_gian_phan_hoi_phong_van')->nullable()->after('trang_thai_tham_gia_phong_van');
            $table->timestamp('thoi_gian_gui_nhac_lich')->nullable()->after('thoi_gian_phan_hoi_phong_van');
            $table->string('hinh_thuc_phong_van', 50)->nullable()->after('thoi_gian_gui_nhac_lich');
            $table->string('nguoi_phong_van')->nullable()->after('hinh_thuc_phong_van');
            $table->string('link_phong_van', 2048)->nullable()->after('nguoi_phong_van');
            $table->string('ket_qua_phong_van')->nullable()->after('link_phong_van');
            $table->text('rubric_danh_gia_phong_van')->nullable()->after('ket_qua_phong_van');
        });
    }
};
