<?php

namespace Database\Seeders;

use App\Models\GoiDichVu;
use App\Models\GoiDichVuTinhNang;
use Illuminate\Database\Seeder;

class GoiDichVuSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'ma_goi' => 'FREE',
                'ten_goi' => 'Gói Free',
                'mo_ta' => 'Dùng thử AI với free quota giới hạn.',
                'gia' => 0,
                'chu_ky' => GoiDichVu::CHU_KY_FREE,
                'trang_thai' => GoiDichVu::TRANG_THAI_HOAT_DONG,
                'is_free' => true,
                'features' => [],
            ],
            [
                'ma_goi' => 'PRO_MONTHLY',
                'ten_goi' => 'Gói Pro Tháng',
                'mo_ta' => 'Dùng AI nâng cao theo chu kỳ tháng.',
                'gia' => 59000,
                'chu_ky' => GoiDichVu::CHU_KY_THANG,
                'trang_thai' => GoiDichVu::TRANG_THAI_HOAT_DONG,
                'is_free' => false,
                'features' => [
                    ['feature_code' => 'cover_letter_generation', 'quota' => 20, 'reset_cycle' => 'monthly', 'is_unlimited' => false],
                    ['feature_code' => 'career_report_generation', 'quota' => 10, 'reset_cycle' => 'monthly', 'is_unlimited' => false],
                    ['feature_code' => 'chatbot_message', 'quota' => 200, 'reset_cycle' => 'monthly', 'is_unlimited' => false],
                    ['feature_code' => 'mock_interview_session', 'quota' => 20, 'reset_cycle' => 'monthly', 'is_unlimited' => false],
                ],
            ],
            [
                'ma_goi' => 'PRO_YEARLY',
                'ten_goi' => 'Gói Pro Năm',
                'mo_ta' => 'Dùng AI nâng cao theo chu kỳ năm với hạn mức lớn hơn.',
                'gia' => 499000,
                'chu_ky' => GoiDichVu::CHU_KY_NAM,
                'trang_thai' => GoiDichVu::TRANG_THAI_HOAT_DONG,
                'is_free' => false,
                'features' => [
                    ['feature_code' => 'cover_letter_generation', 'quota' => 300, 'reset_cycle' => 'yearly', 'is_unlimited' => false],
                    ['feature_code' => 'career_report_generation', 'quota' => 120, 'reset_cycle' => 'yearly', 'is_unlimited' => false],
                    ['feature_code' => 'chatbot_message', 'quota' => 3000, 'reset_cycle' => 'yearly', 'is_unlimited' => false],
                    ['feature_code' => 'mock_interview_session', 'quota' => 240, 'reset_cycle' => 'yearly', 'is_unlimited' => false],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = GoiDichVu::query()->updateOrCreate(
                ['ma_goi' => $planData['ma_goi']],
                $planData,
            );

            foreach ($features as $featureData) {
                GoiDichVuTinhNang::query()->updateOrCreate(
                    [
                        'goi_dich_vu_id' => $plan->id,
                        'feature_code' => $featureData['feature_code'],
                    ],
                    $featureData,
                );
            }
        }
    }
}
