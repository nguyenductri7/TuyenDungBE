<?php

namespace Database\Seeders;

use App\Models\BangGiaTinhNangAi;
use Illuminate\Database\Seeder;

class BangGiaTinhNangAiSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'feature_code' => 'cover_letter_generation',
                'ten_hien_thi' => 'Sinh thư xin việc AI',
                'don_gia' => 3000,
                'don_vi_tinh' => 'request',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'career_report_generation',
                'ten_hien_thi' => 'Sinh báo cáo định hướng nghề nghiệp',
                'don_gia' => 5000,
                'don_vi_tinh' => 'request',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'chatbot_message',
                'ten_hien_thi' => 'Chatbot tư vấn nghề nghiệp',
                'don_gia' => 1000,
                'don_vi_tinh' => 'message',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'mock_interview_session',
                'ten_hien_thi' => 'Phiên mock interview',
                'don_gia' => 7000,
                'don_vi_tinh' => 'session',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'employer_featured_job_7d',
                'ten_hien_thi' => 'Featured Job 7 ngày',
                'don_gia' => 99000,
                'don_vi_tinh' => 'listing',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'employer_featured_job_30d',
                'ten_hien_thi' => 'Featured Job 30 ngày',
                'don_gia' => 299000,
                'don_vi_tinh' => 'listing',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'employer_shortlist_ai_explanation',
                'ten_hien_thi' => 'AI Shortlist ứng viên',
                'don_gia' => 4000,
                'don_vi_tinh' => 'request',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'employer_candidate_compare_ai',
                'ten_hien_thi' => 'AI Compare ứng viên',
                'don_gia' => 6000,
                'don_vi_tinh' => 'request',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'interview_copilot_generate',
                'ten_hien_thi' => 'AI Interview Copilot',
                'don_gia' => 5000,
                'don_vi_tinh' => 'request',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
            [
                'feature_code' => 'interview_copilot_evaluate',
                'ten_hien_thi' => 'AI Evaluate Interview',
                'don_gia' => 5000,
                'don_vi_tinh' => 'request',
                'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
            ],
        ];

        foreach ($rows as $row) {
            BangGiaTinhNangAi::query()->updateOrCreate(
                ['feature_code' => $row['feature_code']],
                $row,
            );
        }
    }
}
