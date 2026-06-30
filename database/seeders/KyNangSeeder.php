<?php

namespace Database\Seeders;

use App\Models\KyNang;
use Illuminate\Database\Seeder;
use RuntimeException;

class KyNangSeeder extends Seeder
{
    /**
     * Seed du lieu bang ky_nangs tu catalog skill chung cua AI.
     *
     * Nguon du lieu chinh: AI/data/skill_aliases.json
     * Cach lam nay giup BE va AI dong bo cung mot danh sach ky nang.
     */
    public function run(): void
    {
        $kyNangs = $this->loadSkillsFromJson();

        foreach ($kyNangs as $skill) {
            $tenKyNang = (string) $skill['skill_name'];
            $category = (string) ($skill['category'] ?? 'general');

            KyNang::updateOrCreate(
                ['ten_ky_nang' => $tenKyNang],
                [
                    'mo_ta' => $this->buildDescription($tenKyNang, $category),
                    'icon' => $this->iconForCategory($category),
                ]
            );
        }

        $this->command->info('✅ KyNangSeeder: Đã đồng bộ ' . count($kyNangs) . ' kỹ năng từ AI/data/skill_aliases.json');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadSkillsFromJson(): array
    {
        $jsonPath = dirname(base_path()) . '/AI/data/skill_aliases.json';

        if (!file_exists($jsonPath)) {
            throw new RuntimeException("Không tìm thấy file skill catalog: {$jsonPath}");
        }

        $content = file_get_contents($jsonPath);
        $decoded = json_decode($content ?: '[]', true);

        if (!is_array($decoded)) {
            throw new RuntimeException('skill_aliases.json không phải JSON hợp lệ.');
        }

        return array_values(array_filter($decoded, function ($item) {
            return is_array($item)
                && !empty($item['skill_name'])
                && isset($item['aliases'])
                && is_array($item['aliases']);
        }));
    }

    private function buildDescription(string $skillName, string $category): string
    {
        $templates = [
            'backend_development' => 'Kỹ năng phát triển backend liên quan đến %s.',
            'frontend_development' => 'Kỹ năng phát triển giao diện và trải nghiệm web với %s.',
            'mobile_development' => 'Kỹ năng phát triển ứng dụng di động với %s.',
            'database' => 'Kỹ năng làm việc với cơ sở dữ liệu và lưu trữ dữ liệu bằng %s.',
            'devops_cloud' => 'Kỹ năng hạ tầng, triển khai và cloud liên quan đến %s.',
            'qa_testing' => 'Kỹ năng kiểm thử và đảm bảo chất lượng với %s.',
            'ui_ux_design' => 'Kỹ năng thiết kế sản phẩm và giao diện sử dụng %s.',
            'data_analysis' => 'Kỹ năng phân tích, xử lý và trực quan hóa dữ liệu bằng %s.',
            'ai_ml' => 'Kỹ năng trí tuệ nhân tạo và học máy liên quan đến %s.',
            'digital_marketing' => 'Kỹ năng digital marketing và tăng trưởng với %s.',
            'sales_business' => 'Kỹ năng kinh doanh, bán hàng và quản lý khách hàng với %s.',
            'business_analysis' => 'Kỹ năng phân tích nghiệp vụ và thu thập yêu cầu với %s.',
            'office_admin' => 'Kỹ năng hành chính, văn phòng và hỗ trợ nghiệp vụ với %s.',
            'finance_accounting' => 'Kỹ năng tài chính, kế toán và quản trị chi phí với %s.',
            'hr_recruitment' => 'Kỹ năng nhân sự, tuyển dụng và phát triển đội ngũ với %s.',
            'customer_support' => 'Kỹ năng hỗ trợ và chăm sóc khách hàng với %s.',
            'erp_business_systems' => 'Kỹ năng sử dụng hệ thống doanh nghiệp và vận hành quy trình với %s.',
            'soft_skills' => 'Kỹ năng mềm quan trọng trong công việc như %s.',
            'project_management' => 'Kỹ năng quản lý dự án và điều phối công việc với %s.',
            'logistics_supply_chain' => 'Kỹ năng logistics, kho vận và chuỗi cung ứng với %s.',
            'engineering_construction' => 'Kỹ năng kỹ thuật, xây dựng và thiết kế với %s.',
            'healthcare' => 'Kỹ năng chuyên môn trong lĩnh vực y tế với %s.',
            'education_training' => 'Kỹ năng giảng dạy, đào tạo và quản lý học tập với %s.',
            'hospitality_tourism' => 'Kỹ năng dịch vụ, du lịch và vận hành lưu trú với %s.',
            'languages' => 'Ngoại ngữ hoặc kỹ năng ngôn ngữ liên quan đến %s.',
            'general' => 'Kỹ năng nghề nghiệp liên quan đến %s.',
        ];

        $template = $templates[$category] ?? $templates['general'];

        return sprintf($template, $skillName);
    }

    private function iconForCategory(string $category): string
    {
        return match ($category) {
            'backend_development' => '🧩',
            'frontend_development' => '🌐',
            'mobile_development' => '📱',
            'database' => '🗄️',
            'devops_cloud' => '☁️',
            'qa_testing' => '🧪',
            'ui_ux_design' => '🎨',
            'data_analysis' => '📊',
            'ai_ml' => '🤖',
            'digital_marketing' => '📣',
            'sales_business' => '🤝',
            'business_analysis' => '📘',
            'office_admin' => '🏢',
            'finance_accounting' => '💰',
            'hr_recruitment' => '🧑‍💼',
            'customer_support' => '🎧',
            'erp_business_systems' => '🏭',
            'soft_skills' => '✨',
            'project_management' => '📋',
            'logistics_supply_chain' => '🚚',
            'engineering_construction' => '🏗️',
            'healthcare' => '🩺',
            'education_training' => '📚',
            'hospitality_tourism' => '🛎️',
            'languages' => '🌍',
            default => '🔹',
        };
    }
}
