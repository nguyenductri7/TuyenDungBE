<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->resetDemoData();

        $this->call([
            NganhNgheSeeder::class,
            KyNangSeeder::class,
            BangGiaTinhNangAiSeeder::class,
            GoiDichVuSeeder::class,
            NguoiDungSeeder::class,
            HoSoSeeder::class,
            NguoiDungKyNangSeeder::class,
            CongTySeeder::class,
            TinTuyenDungSeeder::class,
            LuuTinSeeder::class,
            UngTuyenSeeder::class,
        ]);
    }

    private function resetDemoData(): void
    {
        $tables = [
            'ai_chat_messages',
            'ai_interview_reports',
            'ai_chat_sessions',
            'ai_usage_logs',
            'app_notifications',
            'audit_logs',
            'onboarding_tasks',
            'onboarding_plans',
            'interview_rounds',
            'ung_tuyens',
            'luu_tins',
            'theo_doi_cong_tys',
            'ket_qua_matchings',
            'tu_van_nghe_nghieps',
            'ho_so_parsings',
            'tin_tuyen_dung_parsings',
            'tin_tuyen_dung_ky_nangs',
            'chi_tiet_nganh_nghes',
            'tin_tuyen_dungs',
            'cong_ty_vai_tro_noi_bos',
            'cong_ty_nguoi_dungs',
            'cong_tys',
            'nguoi_dung_ky_nangs',
            'ho_sos',
            'su_dung_tinh_nang_ais',
            'nguoi_dung_goi_dich_vus',
            'giao_dich_thanh_toans',
            'bien_dong_vi',
            'vi_nguoi_dungs',
            'personal_access_tokens',
            'nguoi_dungs',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->command?->info('✅ DatabaseSeeder: Đã dọn dữ liệu demo cũ, giữ lại ngành nghề/kỹ năng/bảng giá/gói dịch vụ.');
    }
}
