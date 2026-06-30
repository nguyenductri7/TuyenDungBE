<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class CvBuilderWritingController extends Controller
{
    private const SECTIONS = ['summary', 'career_goal', 'experience', 'project', 'skills'];
    private const TONES = ['professional', 'concise', 'impact', 'fresher'];

    public function generate(Request $request, AiClientService $aiClient): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập không còn hợp lệ.',
            ], 401);
        }

        $validated = $request->validate([
            'section' => ['required', 'string', Rule::in(self::SECTIONS)],
            'profile' => ['nullable', 'array'],
            'item' => ['nullable', 'array'],
            'item_index' => ['nullable', 'integer', 'min:0'],
            'tone' => ['nullable', 'string', Rule::in(self::TONES)],
            'language' => ['nullable', 'string', Rule::in(['vi', 'en'])],
        ]);

        $section = $validated['section'];
        $profile = $this->normalizeProfile($validated['profile'] ?? []);
        $item = $this->normalizeFlatArray($validated['item'] ?? []);
        $options = [
            'tone' => $validated['tone'] ?? 'professional',
            'language' => $validated['language'] ?? 'vi',
            'item' => $item,
            'item_index' => $validated['item_index'] ?? null,
            'candidate_id' => $nguoiDung->id,
        ];

        $usedFallback = false;
        $provider = 'ai_service';

        try {
            $aiResponse = $aiClient->generateCvBuilderWriting($profile, $section, $options);
            $data = $this->normalizeAiResponse($aiResponse, $section);
            $provider = $data['provider'] ?: $provider;

            if ($data['suggestions'] === [] && $data['skill_suggestions'] === []) {
                throw new RuntimeException('AI service không trả về gợi ý hợp lệ.');
            }
        } catch (RuntimeException $e) {
            $usedFallback = true;
            $provider = 'local_fallback';

            $aiClient->recordFallback('cv_builder_ai_writing', $e->getMessage(), [
                'section' => $section,
                'profile' => $profile,
                'item' => $item,
                'tone' => $options['tone'],
            ], [
                'fallback_mode' => 'laravel_rule_based_writer',
            ]);

            $data = $this->buildFallbackResponse($profile, $section, $item, $options['tone']);
        }

        return response()->json([
            'success' => true,
            'message' => $usedFallback
                ? 'AI service chưa sẵn sàng, hệ thống đã dùng bộ gợi ý nội bộ.'
                : 'Đã sinh gợi ý nội dung CV.',
            'data' => [
                'section' => $section,
                'tone' => $options['tone'],
                'suggestions' => $data['suggestions'],
                'skill_suggestions' => $data['skill_suggestions'],
                'used_fallback' => $usedFallback,
                'provider' => $provider,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function normalizeProfile(array $profile): array
    {
        $allowed = [
            'tieu_de_ho_so',
            'muc_tieu_nghe_nghiep',
            'trinh_do',
            'kinh_nghiem_nam',
            'mo_ta_ban_than',
            'vi_tri_ung_tuyen_muc_tieu',
            'ten_nganh_nghe_muc_tieu',
            'ky_nang_json',
            'kinh_nghiem_json',
            'hoc_van_json',
            'du_an_json',
            'chung_chi_json',
        ];

        $normalized = [];
        foreach ($allowed as $key) {
            $value = $profile[$key] ?? null;
            if (is_array($value)) {
                $normalized[$key] = array_slice(array_values($value), 0, 12);
                continue;
            }

            $normalized[$key] = $this->clean($value);
        }

        return $normalized;
    }

    private function normalizeFlatArray(array $item): array
    {
        $normalized = [];
        foreach ($item as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $normalized[$key] = $this->clean($value);
            }
        }

        return $normalized;
    }

    private function normalizeAiResponse(array $response, string $section): array
    {
        $payload = $response['data'] ?? $response;
        $suggestions = $payload['suggestions'] ?? [];
        $skillSuggestions = $payload['skill_suggestions'] ?? $payload['skills'] ?? [];

        return [
            'suggestions' => $section === 'skills' ? [] : $this->normalizeStringList($suggestions),
            'skill_suggestions' => $section === 'skills'
                ? $this->normalizeSkillSuggestions($skillSuggestions)
                : [],
            'provider' => $payload['meta']['provider'] ?? null,
        ];
    }

    private function normalizeStringList(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => $this->clean(is_array($item) ? ($item['text'] ?? $item['content'] ?? '') : $item),
            array_slice($items, 0, 5)
        )));
    }

    private function normalizeSkillSuggestions(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $skills = [];
        foreach (array_slice($items, 0, 12) as $item) {
            $name = is_array($item) ? ($item['ten'] ?? $item['name'] ?? '') : $item;
            $level = is_array($item) ? ($item['muc_do'] ?? $item['level'] ?? 'kha') : 'kha';
            $name = $this->clean($name);
            if ($name === '') {
                continue;
            }

            $skills[] = [
                'ten' => $name,
                'muc_do' => in_array($level, ['co_ban', 'kha', 'tot', 'xuat_sac'], true) ? $level : 'kha',
            ];
        }

        return $skills;
    }

    private function buildFallbackResponse(array $profile, string $section, array $item, string $tone): array
    {
        if ($section === 'skills') {
            return [
                'suggestions' => [],
                'skill_suggestions' => $this->suggestSkills($profile),
            ];
        }

        return [
            'suggestions' => match ($section) {
                'career_goal' => $this->suggestCareerGoals($profile, $tone),
                'experience' => $this->suggestExperienceBullets($profile, $item, $tone),
                'project' => $this->suggestProjectBullets($profile, $item, $tone),
                default => $this->suggestSummaries($profile, $tone),
            },
            'skill_suggestions' => [],
        ];
    }

    private function suggestSummaries(array $profile, string $tone): array
    {
        $title = $this->targetTitle($profile);
        $industry = $this->industry($profile);
        $years = $this->yearsLabel($profile);
        $skills = $this->skillText($profile);
        $impact = $tone === 'impact' ? 'tập trung vào kết quả đo được, chất lượng triển khai và khả năng phối hợp đa chức năng' : 'có tư duy hệ thống, chủ động học hỏi và giao tiếp rõ ràng';

        return [
            "Ứng viên {$title} {$years}, định hướng phát triển trong lĩnh vực {$industry}. Có thế mạnh về {$skills}, {$impact}; mong muốn đóng góp vào các sản phẩm/dự án có tác động thực tế.",
            "{$title} {$years} với nền tảng {$industry}, quen làm việc theo mục tiêu rõ ràng và ưu tiên hiệu quả vận hành. Nổi bật ở {$skills}, khả năng phân tích vấn đề và chuyển yêu cầu thành kết quả cụ thể.",
            "Ứng viên định hướng {$title}, có kinh nghiệm xây dựng, tối ưu và phối hợp triển khai công việc trong môi trường {$industry}. Phù hợp với vai trò cần sự chủ động, trách nhiệm và khả năng tạo giá trị ổn định.",
        ];
    }

    private function suggestCareerGoals(array $profile, string $tone): array
    {
        $title = $this->targetTitle($profile);
        $industry = $this->industry($profile);
        $skills = $this->skillText($profile);
        $growth = $tone === 'fresher'
            ? 'nhanh chóng hoàn thiện nền tảng chuyên môn, học từ dự án thực tế và phát triển thành nhân sự nòng cốt'
            : 'mở rộng phạm vi ảnh hưởng, nâng cao chất lượng đầu ra và đóng góp vào mục tiêu tăng trưởng của tổ chức';

        return [
            "Mục tiêu trở thành {$title} có năng lực triển khai vững chắc trong lĩnh vực {$industry}, tận dụng {$skills} để giải quyết bài toán thực tế và tạo kết quả bền vững cho doanh nghiệp.",
            "Tìm kiếm cơ hội ở vị trí {$title}, nơi có thể {$growth}. Ưu tiên môi trường đề cao dữ liệu, trách nhiệm cá nhân và tinh thần phối hợp liên phòng ban.",
            "Trong 1-2 năm tới, tập trung phát triển chuyên sâu ở mảng {$industry}, cải thiện năng lực {$skills} và đảm nhận nhiều đầu việc có tác động trực tiếp đến hiệu quả sản phẩm/kinh doanh.",
        ];
    }

    private function suggestExperienceBullets(array $profile, array $item, string $tone): array
    {
        $position = $this->firstFilled([$item['vi_tri'] ?? '', $this->targetTitle($profile)]);
        $company = $this->firstFilled([$item['cong_ty'] ?? '', 'đội nhóm/doanh nghiệp']);
        $skills = $this->skillText($profile);
        $verb = $tone === 'impact' ? 'Dẫn dắt' : 'Tham gia triển khai';

        return [
            "{$verb} các đầu việc tại {$company} ở vai trò {$position}, phối hợp với các bên liên quan để làm rõ yêu cầu, ưu tiên backlog và đảm bảo tiến độ bàn giao.\n- Ứng dụng {$skills} để tối ưu quy trình, giảm lỗi lặp lại và cải thiện chất lượng đầu ra.\n- Theo dõi phản hồi/nghiệp vụ sau triển khai để đề xuất điều chỉnh phù hợp.",
            "Phụ trách nhóm nhiệm vụ cốt lõi của vị trí {$position}: phân tích yêu cầu, xây dựng giải pháp, kiểm tra kết quả và báo cáo tiến độ định kỳ.\n- Chủ động xử lý vấn đề phát sinh, phối hợp đa chức năng và ghi nhận bài học cải tiến.\n- Đóng góp vào việc chuẩn hóa tài liệu, quy trình hoặc tiêu chí nghiệm thu.",
            "Thực hiện các công việc chuyên môn tại {$company} với trọng tâm là chất lượng, khả năng mở rộng và trải nghiệm người dùng/nội bộ.\n- Kết hợp {$skills} để giải quyết các điểm nghẽn trong vận hành.\n- Hỗ trợ đồng đội, chia sẻ kiến thức và duy trì nhịp làm việc ổn định.",
        ];
    }

    private function suggestProjectBullets(array $profile, array $item, string $tone): array
    {
        $name = $this->firstFilled([$item['ten'] ?? '', 'dự án trọng điểm']);
        $role = $this->firstFilled([$item['vai_tro'] ?? '', $this->targetTitle($profile)]);
        $tools = $this->firstFilled([$item['linh_vuc_hoac_cong_cu'] ?? '', $this->skillText($profile)]);
        $result = $tone === 'impact'
            ? 'giúp rút ngắn thời gian xử lý, tăng độ chính xác và cải thiện trải nghiệm sử dụng'
            : 'giúp dự án vận hành rõ ràng hơn, dễ bảo trì và thuận tiện cho các bên liên quan';

        return [
            "Trong dự án {$name}, đảm nhận vai trò {$role}, tập trung làm rõ phạm vi, thiết kế hướng triển khai và phối hợp hoàn thiện các hạng mục chính. Sử dụng {$tools} để bảo đảm chất lượng, tiến độ và khả năng mở rộng của giải pháp.",
            "Tham gia {$name} với trách nhiệm phân tích bối cảnh, triển khai phần việc được giao và kiểm thử kết quả trước khi bàn giao. Kết quả nổi bật: {$result}.",
            "Đóng góp vào {$name} thông qua việc xây dựng luồng xử lý, chuẩn hóa tài liệu và phối hợp phản hồi sau demo/nghiệm thu. Vai trò {$role} giúp kết nối yêu cầu nghiệp vụ với giải pháp thực tế.",
        ];
    }

    private function suggestSkills(array $profile): array
    {
        $title = mb_strtolower($this->targetTitle($profile));
        $industry = mb_strtolower($this->industry($profile));
        $base = ['Giao tiếp', 'Giải quyết vấn đề', 'Làm việc nhóm', 'Quản lý thời gian'];

        if (str_contains($title, 'backend') || str_contains($industry, 'công nghệ') || str_contains($industry, 'it')) {
            $base = ['Laravel', 'REST API', 'MySQL/PostgreSQL', 'Git', 'Kiểm thử API', 'Tối ưu hiệu năng'];
        } elseif (str_contains($title, 'frontend')) {
            $base = ['Vue.js', 'JavaScript', 'HTML/CSS', 'Responsive UI', 'REST API Integration', 'Git'];
        } elseif (str_contains($title, 'product')) {
            $base = ['Product Discovery', 'User Story', 'Roadmap Planning', 'Stakeholder Management', 'Agile/Scrum'];
        } elseif (str_contains($title, 'marketing')) {
            $base = ['Content Planning', 'SEO', 'Performance Marketing', 'Google Analytics', 'Social Media'];
        } elseif (str_contains($title, 'hr') || str_contains($industry, 'nhân sự')) {
            $base = ['Talent Acquisition', 'Screening CV', 'Interview Coordination', 'Onboarding', 'HR Communication'];
        }

        $existing = array_map(
            fn ($skill) => mb_strtolower($skill),
            $this->skillNames($profile)
        );

        return array_values(array_map(
            fn ($skill) => ['ten' => $skill, 'muc_do' => 'kha'],
            array_slice(array_filter($base, fn ($skill) => !in_array(mb_strtolower($skill), $existing, true)), 0, 8)
        ));
    }

    private function targetTitle(array $profile): string
    {
        return $this->firstFilled([
            $profile['vi_tri_ung_tuyen_muc_tieu'] ?? '',
            $profile['tieu_de_ho_so'] ?? '',
            'nhân sự chuyên môn',
        ]);
    }

    private function industry(array $profile): string
    {
        return $this->firstFilled([
            $profile['ten_nganh_nghe_muc_tieu'] ?? '',
            'ngành nghề mục tiêu',
        ]);
    }

    private function yearsLabel(array $profile): string
    {
        $years = (float) ($profile['kinh_nghiem_nam'] ?? 0);
        if ($years <= 0) {
            return 'có nền tảng thực hành và tinh thần học hỏi tốt';
        }

        return 'có ' . rtrim(rtrim(number_format($years, 1, '.', ''), '0'), '.') . ' năm kinh nghiệm';
    }

    private function skillText(array $profile): string
    {
        $skills = array_slice($this->skillNames($profile), 0, 5);
        return $skills === [] ? 'kỹ năng chuyên môn liên quan' : implode(', ', $skills);
    }

    private function skillNames(array $profile): array
    {
        $skills = [];
        foreach (($profile['ky_nang_json'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = $this->clean($item['ten'] ?? '');
            if ($name !== '') {
                $skills[] = $name;
            }
        }

        return array_values(array_unique($skills));
    }

    private function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            $cleaned = $this->clean($value);
            if ($cleaned !== '') {
                return $cleaned;
            }
        }

        return '';
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')) ?? '');
    }
}
