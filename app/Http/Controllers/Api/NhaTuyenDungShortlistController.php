<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BillingException;
use App\Http\Controllers\Api\Concerns\ResolvesEmployerCompany;
use App\Http\Controllers\Controller;
use App\Models\HoSo;
use App\Models\NguoiDung;
use App\Models\TinTuyenDung;
use App\Models\UngTuyen;
use App\Services\Ai\AiClientService;
use App\Services\Billing\FeatureAccessService;
use App\Support\EncodedId;
use App\Support\ExperienceValue;
use App\Support\SkillAliasMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class NhaTuyenDungShortlistController extends Controller
{
    use ResolvesEmployerCompany;

    public function __construct(
        private readonly AiClientService $aiClientService,
        private readonly FeatureAccessService $featureAccessService,
    )
    {
    }

    public function index(Request $request, string $tinTuyenDungId): JsonResponse
    {
        $decodedTinTuyenDungId = EncodedId::decodeOrFail($tinTuyenDungId);
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào để dùng AI Shortlist.',
            ], 404);
        }

        $limit = min(max((int) $request->get('limit', 10), 1), 30);
        $scope = $request->get('scope') === 'applied' ? 'applied' : 'public';
        $tin = TinTuyenDung::with([
                'nganhNghes:id,ten_nganh',
                'parsing:id,tin_tuyen_dung_id,parsed_skills_json,parsed_requirements_json,parsed_salary_json,parsed_location_json,parse_status',
                'kyNangYeuCaus.kyNang:id,ten_ky_nang',
            ])
            ->where('cong_ty_id', $congTy->id)
            ->findOrFail($decodedTinTuyenDungId);
        $this->abortIfCannotManageJobRecord($user, $congTy, $tin);

        $jobProfile = $this->buildJobProfile($tin);
        $profiles = $this->loadProfilesForShortlist($tin, $scope);
        $aiExplain = (bool) $request->boolean('ai_explain', false);
        $rankedCandidates = $profiles
            ->map(fn (HoSo $profile) => $this->scoreProfile($profile, $jobProfile))
            ->sortByDesc('score')
            ->groupBy(fn (array $item) => $item['candidate']['id'] ?? $item['ho_so']['nguoi_dung_id'])
            ->map(function (Collection $candidateProfiles) {
                $best = $candidateProfiles->first();
                $best['profile_count'] = $candidateProfiles->count();
                return $best;
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        $usage = null;
        $enrichment = [
            'items' => $rankedCandidates,
            'ai_attempt_count' => 0,
            'ai_success_count' => 0,
        ];

        try {
            if ($aiExplain && $rankedCandidates->isNotEmpty()) {
                $usage = $this->featureAccessService->beginUsage(
                    $user,
                    'employer_shortlist_ai_explanation',
                    'tin_tuyen_dung',
                    $tin->id,
                    [
                        'scope' => 'employer_shortlist',
                        'limit' => $limit,
                        'shortlist_scope' => $scope,
                    ],
                    $request->header('X-Idempotency-Key'),
                );
            }

            $enrichment = $this->enrichTopProfilesWithAiExplanation(
                $rankedCandidates,
                $tin,
                $jobProfile,
                $aiExplain,
            );

            if ($usage) {
                if (($enrichment['ai_success_count'] ?? 0) > 0) {
                    $usage = $this->featureAccessService->commitUsage($usage, [
                        'scope' => 'employer_shortlist',
                        'shortlist_scope' => $scope,
                        'ai_attempt_count' => (int) ($enrichment['ai_attempt_count'] ?? 0),
                        'ai_success_count' => (int) ($enrichment['ai_success_count'] ?? 0),
                    ]);
                } else {
                    $this->featureAccessService->failUsage($usage, 'AI shortlist fallback toàn phần.', [
                        'scope' => 'employer_shortlist',
                        'shortlist_scope' => $scope,
                    ]);
                    $usage = null;
                }
            }
        } catch (BillingException $exception) {
            if ($usage) {
                $this->featureAccessService->failUsage($usage, $exception->getMessage(), [
                    'scope' => 'employer_shortlist',
                    'shortlist_scope' => $scope,
                ]);
            }

            return response()->json([
                'success' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                ...$exception->context,
            ], $exception->status);
        } catch (Throwable $exception) {
            if ($usage) {
                $this->featureAccessService->failUsage($usage, $exception->getMessage(), [
                    'scope' => 'employer_shortlist',
                    'shortlist_scope' => $scope,
                ]);
            }

            throw $exception;
        }

        $rankedProfiles = $enrichment['items'];

        return response()->json([
            'success' => true,
            'data' => [
                'job' => [
                    'id' => $tin->id,
                    'tieu_de' => $tin->tieu_de,
                    'required_skills' => $jobProfile['required_skills'],
                    'industries' => $jobProfile['industries'],
                ],
                'items' => $rankedProfiles,
                'meta' => [
                    'total_candidates_scanned' => $profiles->groupBy('nguoi_dung_id')->count(),
                    'total_profiles_scanned' => $profiles->count(),
                    'limit' => $limit,
                    'scope' => $scope,
                    'scope_label' => $scope === 'applied' ? 'Ứng viên đã ứng tuyển' : 'CV công khai',
                    'model_version' => 'employer_shortlist_hybrid_v2',
                    'ai_explanation_enabled' => $aiExplain,
                    'ai_attempt_count' => (int) ($enrichment['ai_attempt_count'] ?? 0),
                    'ai_success_count' => (int) ($enrichment['ai_success_count'] ?? 0),
                    'permission_scope' => $this->coTheQuanLyTatCaTinTuyenDung($user, $congTy) ? 'company' : 'owned_job',
                    'billing' => $usage ? [
                        'feature_code' => $usage->feature_code,
                        'usage_id' => $usage->id,
                        'billing_mode' => $usage->billing_mode,
                    ] : null,
                ],
            ],
        ]);
    }

    public function compare(Request $request, string $tinTuyenDungId): JsonResponse
    {
        $decodedTinTuyenDungId = EncodedId::decodeOrFail($tinTuyenDungId);
        $request->validate([
            'ho_so_ids' => ['required', 'array', 'min:2', 'max:5'],
            'ho_so_ids.*' => ['integer', 'distinct'],
        ]);

        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào để so sánh ứng viên.',
            ], 404);
        }

        $tin = TinTuyenDung::with([
                'nganhNghes:id,ten_nganh',
                'parsing:id,tin_tuyen_dung_id,parsed_skills_json,parsed_requirements_json,parsed_salary_json,parsed_location_json,parse_status',
                'kyNangYeuCaus.kyNang:id,ten_ky_nang',
            ])
            ->where('cong_ty_id', $congTy->id)
            ->findOrFail($decodedTinTuyenDungId);
        $this->abortIfCannotManageJobRecord($user, $congTy, $tin);

        $jobProfile = $this->buildJobProfile($tin);
        $profiles = $this->baseProfileQuery()
            ->whereIn('id', $request->input('ho_so_ids', []))
            ->get();
        $aiExplain = (bool) $request->boolean('ai_explain', false);
        $scoredItems = $profiles
            ->map(fn (HoSo $profile) => $this->scoreProfile($profile, $jobProfile))
            ->sortByDesc('score')
            ->values();

        $usage = null;
        $enrichment = [
            'items' => $scoredItems,
            'ai_attempt_count' => 0,
            'ai_success_count' => 0,
        ];

        try {
            if ($aiExplain && $scoredItems->isNotEmpty()) {
                $usage = $this->featureAccessService->beginUsage(
                    $user,
                    'employer_candidate_compare_ai',
                    'tin_tuyen_dung',
                    $tin->id,
                    [
                        'scope' => 'employer_compare',
                        'ho_so_ids' => $request->input('ho_so_ids', []),
                    ],
                    $request->header('X-Idempotency-Key'),
                );
            }

            $enrichment = $this->enrichTopProfilesWithAiExplanation(
                $scoredItems,
                $tin,
                $jobProfile,
                $aiExplain,
            );

            if ($usage) {
                if (($enrichment['ai_success_count'] ?? 0) > 0) {
                    $usage = $this->featureAccessService->commitUsage($usage, [
                        'scope' => 'employer_compare',
                        'ai_attempt_count' => (int) ($enrichment['ai_attempt_count'] ?? 0),
                        'ai_success_count' => (int) ($enrichment['ai_success_count'] ?? 0),
                    ]);
                } else {
                    $this->featureAccessService->failUsage($usage, 'AI compare fallback toàn phần.', [
                        'scope' => 'employer_compare',
                    ]);
                    $usage = null;
                }
            }
        } catch (BillingException $exception) {
            if ($usage) {
                $this->featureAccessService->failUsage($usage, $exception->getMessage(), [
                    'scope' => 'employer_compare',
                ]);
            }

            return response()->json([
                'success' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                ...$exception->context,
            ], $exception->status);
        } catch (Throwable $exception) {
            if ($usage) {
                $this->featureAccessService->failUsage($usage, $exception->getMessage(), [
                    'scope' => 'employer_compare',
                ]);
            }

            throw $exception;
        }

        $items = $enrichment['items'];

        return response()->json([
            'success' => true,
            'data' => [
                'job' => [
                    'id' => $tin->id,
                    'tieu_de' => $tin->tieu_de,
                    'required_skills' => $jobProfile['required_skills'],
                ],
                'items' => $items,
                'matrix' => $this->buildComparisonMatrix($items, $jobProfile),
                'meta' => [
                    'ai_explanation_enabled' => $aiExplain,
                    'ai_attempt_count' => (int) ($enrichment['ai_attempt_count'] ?? 0),
                    'ai_success_count' => (int) ($enrichment['ai_success_count'] ?? 0),
                    'billing' => $usage ? [
                        'feature_code' => $usage->feature_code,
                        'usage_id' => $usage->id,
                        'billing_mode' => $usage->billing_mode,
                    ] : null,
                ],
            ],
        ]);
    }

    private function loadProfilesForShortlist(TinTuyenDung $tin, string $scope): Collection
    {
        $query = $this->baseProfileQuery();

        if ($scope === 'applied') {
            $appliedProfileIds = UngTuyen::query()
                ->where('tin_tuyen_dung_id', $tin->id)
                ->where(function ($query) {
                    $query->whereNull('da_rut_don')->orWhere('da_rut_don', false);
                })
                ->pluck('ho_so_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            return $query->whereIn('id', $appliedProfileIds)->get();
        }

        return $query
            ->where('trang_thai', HoSo::TRANG_THAI_CONG_KHAI)
            ->get();
    }

    private function baseProfileQuery()
    {
        return HoSo::with([
            'nguoiDung:id,ho_ten,email,so_dien_thoai,anh_dai_dien',
            'nguoiDung.kyNangs:id,ten_ky_nang',
            'parsing:id,ho_so_id,raw_text,parsed_skills_json,parsed_experience_json,parsed_education_json',
        ]);
    }

    private function buildJobProfile(TinTuyenDung $tin): array
    {
        $manualSkills = $tin->kyNangYeuCaus
            ->map(fn ($item) => $item->kyNang?->ten_ky_nang)
            ->filter()
            ->values()
            ->all();
        $parsedSkills = $this->extractNames($tin->parsing?->parsed_skills_json ?? []);
        $industries = $tin->nganhNghes
            ->pluck('ten_nganh')
            ->filter()
            ->values()
            ->all();

        return [
            'title' => $tin->tieu_de,
            'description' => $tin->mo_ta_cong_viec,
            'location' => $tin->dia_diem_lam_viec,
            'work_mode' => $tin->hinh_thuc_lam_viec,
            'salary_from' => $tin->muc_luong_tu,
            'salary_to' => $tin->muc_luong_den,
            'salary_unit' => $tin->don_vi_luong,
            'requirements' => $this->extractNames($tin->parsing?->parsed_requirements_json ?? []),
            'required_skills' => $this->canonicalSkillValues([...$manualSkills, ...$parsedSkills]),
            'industries' => $industries,
            'experience_years' => $this->extractYears($tin->kinh_nghiem_yeu_cau),
            'education' => $tin->trinh_do_yeu_cau,
            'search_text' => $this->normalizeText(implode(' ', [
                $tin->tieu_de,
                $tin->mo_ta_cong_viec,
                $tin->cap_bac,
                $tin->kinh_nghiem_yeu_cau,
                $tin->trinh_do_yeu_cau,
                implode(' ', $manualSkills),
                implode(' ', $parsedSkills),
                implode(' ', $industries),
            ])),
        ];
    }

    private function scoreProfile(HoSo $profile, array $jobProfile): array
    {
        $candidateSkills = $this->candidateSkills($profile);
        $matchedSkills = $this->matchSkills($jobProfile['required_skills'], $candidateSkills);
        $missingSkills = array_values(array_diff($jobProfile['required_skills'], $matchedSkills));
        $candidateText = $this->candidateSearchText($profile);

        $skillScore = count($jobProfile['required_skills']) > 0
            ? round((((count($matchedSkills) / count($jobProfile['required_skills'])) * 100) * 0.78) + ($this->textOverlapScore($jobProfile['search_text'], $candidateText) * 0.22), 1)
            : $this->textOverlapScore($jobProfile['search_text'], $candidateText);

        $experienceScore = $this->experienceScore(
            (float) ($profile->kinh_nghiem_nam ?? 0),
            (float) ($jobProfile['experience_years'] ?? 0),
        );
        $educationScore = $this->educationScore($profile->trinh_do, $jobProfile['education']);
        $industryScore = $this->industryScore($profile, $jobProfile);
        $cvQualityScore = $this->cvQualityScore($profile);
        $evidenceScore = $this->evidenceScore($profile);

        $score = round(
            ($skillScore * 0.45)
            + ($experienceScore * 0.18)
            + ($educationScore * 0.08)
            + ($industryScore * 0.12)
            + ($cvQualityScore * 0.10)
            + ($evidenceScore * 0.07),
            1,
        );

        return [
            'score' => $score,
            'recommendation' => $this->recommendationLabel($score),
            'score_breakdown' => [
                'skills' => $skillScore,
                'experience' => $experienceScore,
                'education' => $educationScore,
                'industry' => $industryScore,
                'cv_quality' => $cvQualityScore,
                'evidence' => $evidenceScore,
            ],
            'matched_skills' => array_slice($matchedSkills, 0, 10),
            'missing_skills' => array_slice($missingSkills, 0, 10),
            'explanation' => $this->buildExplanation($profile, $score, $matchedSkills, $missingSkills, $experienceScore),
            'ai_explanation' => null,
            'structured_explanation' => $this->fallbackStructuredExplanation($profile, $matchedSkills, $missingSkills, $experienceScore),
            'source_insights' => $this->sourceInsights($profile),
            'confidence' => $this->confidenceInsight($profile, $jobProfile, false),
            'candidate' => $this->mapCandidate($profile->nguoiDung),
            'ho_so' => $this->mapProfile($profile),
        ];
    }

    private function candidateSkills(HoSo $profile): array
    {
        $userSkills = $profile->nguoiDung?->kyNangs
            ? $profile->nguoiDung->kyNangs->pluck('ten_ky_nang')->all()
            : [];

        return $this->canonicalSkillValues([
            ...$userSkills,
            ...$this->extractNames($profile->ky_nang_json ?? []),
            ...$this->extractNames($profile->parsing?->parsed_skills_json ?? []),
            ...$this->extractNames($profile->kinh_nghiem_json ?? []),
            ...$this->extractNames($profile->du_an_json ?? []),
            ...$this->extractNames($profile->chung_chi_json ?? []),
        ]);
    }

    private function matchSkills(array $requiredSkills, array $candidateSkills): array
    {
        $candidateMap = collect($candidateSkills)
            ->mapWithKeys(fn ($skill) => [SkillAliasMatcher::canonicalKey($skill) => $skill])
            ->filter(fn ($skill, $key) => $key !== '')
            ->all();

        return collect($requiredSkills)
            ->filter(function ($requiredSkill) use ($candidateMap) {
                $required = SkillAliasMatcher::canonicalKey($requiredSkill);
                if (!$required) {
                    return false;
                }

                foreach ($candidateMap as $candidateNormalized => $candidateSkill) {
                    if ($candidateNormalized === $required) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    }

    private function textOverlapScore(string $jobText, string $candidateText): float
    {
        $jobWords = collect(explode(' ', $jobText))->filter(fn ($word) => mb_strlen($word) >= 4)->unique();
        $candidateWords = collect(explode(' ', $candidateText))->filter(fn ($word) => mb_strlen($word) >= 4)->unique();

        if ($jobWords->isEmpty()) {
            return 50.0;
        }

        $matched = $jobWords->intersect($candidateWords)->count();

        return round(min(100, ($matched / max(1, $jobWords->count())) * 100), 1);
    }

    private function experienceScore(float $candidateYears, float $requiredYears): float
    {
        if ($requiredYears <= 0) {
            return $candidateYears > 0 ? 85.0 : 65.0;
        }

        if ($candidateYears >= $requiredYears) {
            return 100.0;
        }

        return round(max(35, ($candidateYears / $requiredYears) * 100), 1);
    }

    private function educationScore(?string $candidateEducation, ?string $requiredEducation): float
    {
        if (!$requiredEducation) {
            return $candidateEducation ? 80.0 : 60.0;
        }

        $rank = [
            'Trung học' => 1,
            'Trung cấp' => 2,
            'Cao đẳng' => 3,
            'Đại học' => 4,
            'Thạc sĩ' => 5,
            'Tiến sĩ' => 6,
        ];
        $candidateRank = $rank[HoSo::normalizeTrinhDo($candidateEducation) ?? ''] ?? 0;
        $requiredRank = $rank[HoSo::normalizeTrinhDo($requiredEducation) ?? ''] ?? 0;

        if ($requiredRank === 0) {
            return $candidateEducation ? 80.0 : 60.0;
        }

        return $candidateRank >= $requiredRank ? 100.0 : max(45.0, round(($candidateRank / $requiredRank) * 100, 1));
    }

    private function industryScore(HoSo $profile, array $jobProfile): float
    {
        $industryText = $this->normalizeText(implode(' ', $jobProfile['industries']));
        $candidateText = $this->candidateSearchText($profile);

        if (!$industryText) {
            return 70.0;
        }

        foreach (explode(' ', $industryText) as $word) {
            if (mb_strlen($word) >= 4 && str_contains($candidateText, $word)) {
                return 100.0;
            }
        }

        return 55.0;
    }

    private function cvQualityScore(HoSo $profile): float
    {
        $score = 35.0;

        if ($profile->file_cv) {
            $score += 12;
        }

        if ($profile->parsing?->raw_text || $this->extractNames($profile->parsing?->parsed_skills_json ?? [])) {
            $score += 22;
        }

        $builderSections = [
            $profile->ky_nang_json,
            $profile->kinh_nghiem_json,
            $profile->hoc_van_json,
            $profile->du_an_json,
            $profile->chung_chi_json,
        ];
        $filledSections = collect($builderSections)
            ->filter(fn ($section) => is_array($section) && count(array_filter($section)) > 0)
            ->count();

        if ($filledSections > 0) {
            $score += 15 + min(18, $filledSections * 4);
        }

        if ($profile->muc_tieu_nghe_nghiep || $profile->mo_ta_ban_than) {
            $score += 8;
        }

        if ($profile->ten_template_cv || $profile->mau_cv) {
            $score += 5;
        }

        return round(min(100, $score), 1);
    }

    private function evidenceScore(HoSo $profile): float
    {
        $evidenceText = $this->normalizeText(implode(' ', [
            json_encode($profile->du_an_json ?: [], JSON_UNESCAPED_UNICODE),
            json_encode($profile->chung_chi_json ?: [], JSON_UNESCAPED_UNICODE),
            $profile->parsing?->raw_text,
        ]));

        $score = 45.0;
        foreach (['github', 'demo', 'portfolio', 'behance', 'figma', 'dashboard', 'certificate', 'chung chi', 'case study', 'api'] as $keyword) {
            if (str_contains($evidenceText, $this->normalizeText($keyword))) {
                $score += 10;
            }
        }

        if (preg_match('/https?:\/\/|www\./', $evidenceText)) {
            $score += 18;
        }

        return round(min(100, $score), 1);
    }

    private function sourceInsights(HoSo $profile): array
    {
        $isBuilder = ($profile->nguon_ho_so && $profile->nguon_ho_so !== 'upload')
            || collect([$profile->ky_nang_json, $profile->kinh_nghiem_json, $profile->hoc_van_json, $profile->du_an_json, $profile->chung_chi_json])
                ->contains(fn ($section) => is_array($section) && count(array_filter($section)) > 0);

        return [
            'source' => $isBuilder ? 'builder' : 'upload',
            'label' => $isBuilder ? 'CV tạo trên hệ thống' : 'CV upload',
            'has_parsed_upload' => (bool) ($profile->parsing?->raw_text || $profile->parsing?->parsed_skills_json),
            'builder_section_count' => collect([$profile->ky_nang_json, $profile->kinh_nghiem_json, $profile->hoc_van_json, $profile->du_an_json, $profile->chung_chi_json])
                ->filter(fn ($section) => is_array($section) && count(array_filter($section)) > 0)
                ->count(),
        ];
    }

    private function extractYears(?string $value): float
    {
        if (!$value) {
            return 0.0;
        }

        $normalized = ExperienceValue::normalize($value);
        if (is_numeric($normalized)) {
            return (float) $normalized;
        }

        preg_match_all('/\d+/', $value, $matches);
        $numbers = array_map('intval', $matches[0] ?? []);

        return $numbers ? (float) min($numbers) : 0.0;
    }

    private function extractNames(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if (is_string($item)) {
                    return $item;
                }

                if (is_array($item)) {
                    return $item['skill_name']
                        ?? $item['ten_ky_nang']
                        ?? $item['ten']
                        ?? $item['ky_nang']
                        ?? $item['cong_nghe']
                        ?? $item['vai_tro']
                        ?? $item['vi_tri']
                        ?? $item['ten_du_an']
                        ?? $item['du_an']
                        ?? $item['cong_ty']
                        ?? $item['to_chuc']
                        ?? $item['name']
                        ?? $item['title']
                        ?? $item['text']
                        ?? $item['value']
                        ?? null;
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function uniqueValues(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => $this->normalizeText($value))
            ->values()
            ->all();
    }

    private function canonicalSkillValues(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => SkillAliasMatcher::displayName((string) $value))
            ->filter()
            ->unique(fn ($value) => SkillAliasMatcher::canonicalKey($value))
            ->values()
            ->all();
    }

    private function normalizeText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $converted !== false ? $converted : $value;
        $value = preg_replace('/[^a-z0-9+#.\s]/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function candidateSearchText(HoSo $profile): string
    {
        return $this->normalizeText(implode(' ', [
            $profile->tieu_de_ho_so,
            $profile->muc_tieu_nghe_nghiep,
            $profile->mo_ta_ban_than,
            $profile->ten_nganh_nghe_muc_tieu,
            $profile->vi_tri_ung_tuyen_muc_tieu,
            $profile->parsing?->raw_text,
            implode(' ', $this->candidateSkills($profile)),
            implode(' ', $this->extractNames($profile->kinh_nghiem_json ?? [])),
            implode(' ', $this->extractNames($profile->du_an_json ?? [])),
            implode(' ', $this->extractNames($profile->hoc_van_json ?? [])),
            implode(' ', $this->extractNames($profile->chung_chi_json ?? [])),
            json_encode($profile->kinh_nghiem_json ?: [], JSON_UNESCAPED_UNICODE),
            json_encode($profile->du_an_json ?: [], JSON_UNESCAPED_UNICODE),
            json_encode($profile->hoc_van_json ?: [], JSON_UNESCAPED_UNICODE),
        ]));
    }

    private function recommendationLabel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Nên ưu tiên phỏng vấn',
            $score >= 65 => 'Phù hợp để xem xét',
            $score >= 50 => 'Cần kiểm tra thêm',
            default => 'Phù hợp thấp',
        };
    }

    private function buildExplanation(HoSo $profile, float $score, array $matchedSkills, array $missingSkills, float $experienceScore): string
    {
        $parts = [];

        if ($matchedSkills) {
            $parts[] = 'Khớp các kỹ năng chính: ' . implode(', ', array_slice($matchedSkills, 0, 5)) . '.';
        }

        if ($profile->kinh_nghiem_nam) {
            $parts[] = "Ứng viên có {$this->formatExperienceDuration((float) $profile->kinh_nghiem_nam)} kinh nghiệm.";
        } elseif ($experienceScore < 70) {
            $parts[] = 'Chưa thấy thông tin kinh nghiệm rõ ràng trong hồ sơ.';
        }

        if ($missingSkills) {
            $parts[] = 'Cần kiểm tra thêm: ' . implode(', ', array_slice($missingSkills, 0, 4)) . '.';
        }

        if (!$parts) {
            $parts[] = 'Hồ sơ có một số tín hiệu phù hợp với JD nhưng cần HR đọc chi tiết CV để xác nhận.';
        }

        return "Điểm phù hợp {$score}%. " . implode(' ', $parts);
    }

    private function enrichTopProfilesWithAiExplanation(Collection $items, TinTuyenDung $tin, array $jobProfile, bool $enabled): array
    {
        if (!$enabled || $items->isEmpty()) {
            return [
                'items' => $items->values(),
                'ai_attempt_count' => 0,
                'ai_success_count' => 0,
            ];
        }

        $aiAttemptCount = 0;
        $aiSuccessCount = 0;
        $indexedItems = $items->values();
        $jdProfile = $this->buildAiJdProfile($tin, $jobProfile);
        $parallelRequests = [];

        foreach ($indexedItems->take(5) as $index => $item) {
            $aiAttemptCount++;
            $parallelRequests[$index] = [
                'ho_so_id' => (int) ($item['ho_so']['id'] ?? 0),
                'tin_tuyen_dung_id' => (int) $tin->id,
                'cv_profile' => $this->buildAiCvProfile($item),
                'jd_profile' => $jdProfile,
                'include_llm_explanation' => true,
            ];
        }

        try {
            $aiResponses = $this->aiClientService->matchCvJdParallel($parallelRequests);
        } catch (RuntimeException $exception) {
            $aiResponses = [];
            foreach ($parallelRequests as $request) {
                $this->aiClientService->recordFallback(
                    'employer_shortlist_ai_explanation',
                    $exception->getMessage(),
                    [
                        'ho_so_id' => (int) $request['ho_so_id'],
                        'tin_tuyen_dung_id' => (int) $request['tin_tuyen_dung_id'],
                    ],
                    ['scope' => 'shortlist_parallel_explanation']
                );
            }
        }

        $mappedItems = $indexedItems
            ->map(function (array $item, int $index) use ($tin, $jobProfile, $aiResponses, &$aiSuccessCount) {
                if ($index >= 5) {
                    return $item;
                }

                $response = $aiResponses[$index] ?? null;
                if (!is_array($response) || ($response['success'] ?? true) === false) {
                    $error = is_array($response) ? ($response['error'] ?? 'AI service chưa phản hồi.') : 'AI service chưa phản hồi.';
                    $this->aiClientService->recordFallback(
                        'employer_shortlist_ai_explanation',
                        (string) $error,
                        [
                            'ho_so_id' => (int) ($item['ho_so']['id'] ?? 0),
                            'tin_tuyen_dung_id' => (int) $tin->id,
                        ],
                        ['scope' => 'shortlist_item_explanation']
                    );
                    $item['ai_error'] = (string) $error;
                    $item['confidence'] = $this->confidenceInsightFromMappedProfile($item, $jobProfile, false);
                    return $item;
                }

                $data = $response['data'] ?? $response;
                $aiExplanation = $this->extractAiExplanation($data);

                if ($aiExplanation) {
                    $item['ai_explanation'] = (string) $aiExplanation;
                    $item['explanation'] = (string) $aiExplanation;
                }
                $item['structured_explanation'] = $this->extractStructuredExplanation($data, $item);

                $aiScore = $this->extractAiScore($data);
                if ($aiScore !== null) {
                    $item['rule_score'] = $item['score'];
                    $item['ai_score'] = $aiScore;
                    $item['score'] = round(($item['score'] * 0.7) + ($aiScore * 0.3), 1);
                    $item['recommendation'] = $this->recommendationLabel($item['score']);
                    $item['score_breakdown']['ai_match'] = $aiScore;
                } else {
                    $item['ai_score'] = null;
                }

                $item['ai_model_version'] = $data['model_version'] ?? ($response['model_version'] ?? 'ai_service');
                $item['confidence'] = $this->confidenceInsightFromMappedProfile($item, $jobProfile, true);
                $aiSuccessCount++;

                return $item;
            });

        return [
            'items' => $mappedItems,
            'ai_attempt_count' => $aiAttemptCount,
            'ai_success_count' => $aiSuccessCount,
        ];
    }

    private function buildAiCvProfile(array $item): array
    {
        $profile = $item['ho_so'] ?? [];

        return [
            'tieu_de_ho_so' => $profile['tieu_de_ho_so'] ?? null,
            'muc_tieu_nghe_nghiep' => $profile['muc_tieu_nghe_nghiep'] ?? null,
            'trinh_do' => $profile['trinh_do'] ?? null,
            'kinh_nghiem_nam' => $profile['kinh_nghiem_nam'] ?? null,
            'mo_ta_ban_than' => $profile['mo_ta_ban_than'] ?? null,
            'raw_text' => $profile['raw_text'] ?? null,
            'nguon_ho_so' => $profile['nguon_ho_so'] ?? null,
            'matched_skills' => $item['matched_skills'] ?? [],
            'missing_skills' => $item['missing_skills'] ?? [],
            'parsed_skills' => $profile['parsed_skills_json'] ?? ($item['matched_skills'] ?? []),
            'parsed_experience' => $profile['parsed_experience_json'] ?? [],
            'parsed_education' => $profile['parsed_education_json'] ?? [],
            'score_breakdown' => $item['score_breakdown'] ?? [],
            'ky_nang_json' => $profile['ky_nang_json'] ?? [],
            'kinh_nghiem_json' => $profile['kinh_nghiem_json'] ?? [],
            'hoc_van_json' => $profile['hoc_van_json'] ?? [],
            'du_an_json' => $profile['du_an_json'] ?? [],
            'chung_chi_json' => $profile['chung_chi_json'] ?? [],
        ];
    }

    private function extractAiExplanation(array $data): ?string
    {
        $direct = $data['explanation']
            ?? $data['giai_thich']
            ?? $data['summary']
            ?? $data['nhan_xet']
            ?? $data['recommendation_reason']
            ?? null;

        if (is_string($direct) && trim($direct) !== '') {
            return trim($direct);
        }

        $parts = [];
        foreach (['strengths', 'diem_manh', 'risks', 'diem_yeu', 'questions', 'cau_hoi_goi_y'] as $key) {
            if (!empty($data[$key]) && is_array($data[$key])) {
                $parts[] = implode('; ', array_filter(array_map('strval', $data[$key])));
            }
        }

        return $parts ? implode(' ', $parts) : null;
    }

    private function extractStructuredExplanation(array $data, array $item): array
    {
        return [
            'strengths' => $this->extractAiList($data, ['strengths', 'diem_manh', 'uu_diem'], $item['structured_explanation']['strengths'] ?? []),
            'weaknesses' => $this->extractAiList($data, ['weaknesses', 'diem_yeu', 'gaps'], $item['structured_explanation']['weaknesses'] ?? []),
            'risks' => $this->extractAiList($data, ['risks', 'rui_ro', 'can_luu_y'], $item['structured_explanation']['risks'] ?? []),
            'interview_questions' => $this->extractAiList($data, ['questions', 'cau_hoi_goi_y', 'interview_questions'], $item['structured_explanation']['interview_questions'] ?? []),
            'recommendation' => (string) ($data['recommendation']
                ?? $data['khuyen_nghi']
                ?? $data['final_recommendation']
                ?? ($item['structured_explanation']['recommendation'] ?? $item['recommendation'] ?? 'HR nên đọc CV chi tiết trước khi quyết định.')),
        ];
    }

    private function extractAiList(array $data, array $keys, array $fallback = []): array
    {
        foreach ($keys as $key) {
            if (empty($data[$key])) {
                continue;
            }

            if (is_array($data[$key])) {
                return collect($data[$key])
                    ->map(fn ($value) => is_array($value) ? ($value['text'] ?? $value['value'] ?? json_encode($value, JSON_UNESCAPED_UNICODE)) : $value)
                    ->filter()
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->values()
                    ->all();
            }

            if (is_string($data[$key])) {
                return [trim($data[$key])];
            }
        }

        return array_values(array_filter($fallback));
    }

    private function fallbackStructuredExplanation(HoSo $profile, array $matchedSkills, array $missingSkills, float $experienceScore): array
    {
        $strengths = [];
        $weaknesses = [];
        $risks = [];
        $questions = [];

        if ($matchedSkills) {
            $strengths[] = 'Kỹ năng khớp với JD: ' . implode(', ', array_slice($matchedSkills, 0, 5)) . '.';
        }

        if ($profile->kinh_nghiem_nam) {
            $strengths[] = "Có {$this->formatExperienceDuration((float) $profile->kinh_nghiem_nam)} kinh nghiệm được khai báo trong hồ sơ.";
        }

        if ($missingSkills) {
            $weaknesses[] = 'Chưa thấy rõ các kỹ năng/yêu cầu: ' . implode(', ', array_slice($missingSkills, 0, 5)) . '.';
            $questions[] = 'Hãy yêu cầu ứng viên mô tả kinh nghiệm thực tế với: ' . implode(', ', array_slice($missingSkills, 0, 3)) . '.';
        }

        if ($experienceScore < 70) {
            $weaknesses[] = 'Kinh nghiệm chưa đủ rõ so với yêu cầu của JD.';
        }

        if (!$profile->parsing?->raw_text && $profile->file_cv) {
            $risks[] = 'CV upload chưa có dữ liệu parse đầy đủ, điểm có thể thấp hơn thực tế.';
        }

        if (!$strengths) {
            $strengths[] = 'Có một số tín hiệu phù hợp với JD nhưng chưa đủ nổi bật.';
        }

        if (!$questions) {
            $questions[] = 'Ứng viên đã từng xử lý nhiệm vụ nào gần nhất với JD này và kết quả đo lường ra sao?';
        }

        return [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses ?: ['Chưa phát hiện điểm yếu rõ ràng từ dữ liệu hiện có.'],
            'risks' => $risks ?: ['Cần HR đọc CV chi tiết để xác nhận ngữ cảnh và mức độ đóng góp thực tế.'],
            'interview_questions' => $questions,
            'recommendation' => 'Dùng kết quả này để ưu tiên đọc CV/phỏng vấn, không thay thế quyết định của HR.',
        ];
    }

    private function confidenceInsight(HoSo $profile, array $jobProfile, bool $hasAiResult): array
    {
        $signals = 0;
        $warnings = [];

        if ($jobProfile['required_skills']) {
            $signals += 18;
        } else {
            $warnings[] = 'JD chưa có kỹ năng yêu cầu rõ ràng.';
        }

        if ($profile->parsing?->raw_text || $this->extractNames($profile->parsing?->parsed_skills_json ?? [])) {
            $signals += 24;
        } elseif ($profile->file_cv) {
            $signals += 10;
            $warnings[] = 'CV upload chưa parse đủ dữ liệu.';
        }

        $builderSectionCount = (int) ($this->sourceInsights($profile)['builder_section_count'] ?? 0);
        if ($builderSectionCount >= 3) {
            $signals += 24;
        } elseif ($builderSectionCount > 0) {
            $signals += 14;
            $warnings[] = 'CV tạo trên hệ thống còn ít mục dữ liệu.';
        }

        if ($profile->kinh_nghiem_nam !== null || $profile->trinh_do) {
            $signals += 14;
        } else {
            $warnings[] = 'Thiếu thông tin kinh nghiệm hoặc trình độ.';
        }

        if ($profile->du_an_json || $profile->chung_chi_json) {
            $signals += 10;
        } else {
            $warnings[] = 'Thiếu minh chứng dự án/chứng chỉ.';
        }

        if ($hasAiResult) {
            $signals += 10;
        }

        $score = min(100, $signals);
        $level = match (true) {
            $score >= 75 => 'high',
            $score >= 50 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'label' => [
                'high' => 'Độ tin cậy cao',
                'medium' => 'Độ tin cậy trung bình',
                'low' => 'Độ tin cậy thấp',
            ][$level],
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function confidenceInsightFromMappedProfile(array $item, array $jobProfile, bool $hasAiResult): array
    {
        $profile = $item['ho_so'] ?? [];
        $source = $item['source_insights'] ?? [];
        $signals = 0;
        $warnings = [];

        if ($jobProfile['required_skills']) {
            $signals += 18;
        } else {
            $warnings[] = 'JD chưa có kỹ năng yêu cầu rõ ràng.';
        }

        if (!empty($source['has_parsed_upload'])) {
            $signals += 24;
        } elseif (!empty($profile['file_cv'])) {
            $signals += 10;
            $warnings[] = 'CV upload chưa parse đủ dữ liệu.';
        }

        $builderSectionCount = (int) ($source['builder_section_count'] ?? 0);
        if ($builderSectionCount >= 3) {
            $signals += 24;
        } elseif ($builderSectionCount > 0) {
            $signals += 14;
            $warnings[] = 'CV tạo trên hệ thống còn ít mục dữ liệu.';
        }

        if (($profile['kinh_nghiem_nam'] ?? null) !== null || !empty($profile['trinh_do'])) {
            $signals += 14;
        } else {
            $warnings[] = 'Thiếu thông tin kinh nghiệm hoặc trình độ.';
        }

        if (!empty($profile['du_an_json']) || !empty($profile['chung_chi_json'])) {
            $signals += 10;
        } else {
            $warnings[] = 'Thiếu minh chứng dự án/chứng chỉ.';
        }

        if ($hasAiResult) {
            $signals += 10;
        }

        $score = min(100, $signals);
        $level = match (true) {
            $score >= 75 => 'high',
            $score >= 50 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'label' => [
                'high' => 'Độ tin cậy cao',
                'medium' => 'Độ tin cậy trung bình',
                'low' => 'Độ tin cậy thấp',
            ][$level],
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function extractAiScore(array $data): ?float
    {
        $score = $data['diem_phu_hop']
            ?? $data['match_score']
            ?? $data['score']
            ?? $data['overall_score']
            ?? null;

        if (!is_numeric($score)) {
            return null;
        }

        $score = (float) $score;
        if ($score <= 1) {
            $score *= 100;
        }

        return round(max(0, min(100, $score)), 1);
    }

    private function buildAiJdProfile(TinTuyenDung $tin, array $jobProfile): array
    {
        return [
            'tieu_de' => $tin->tieu_de,
            'mo_ta_cong_viec' => $tin->mo_ta_cong_viec,
            'raw_text' => $tin->parsing?->raw_text ?? $tin->mo_ta_cong_viec,
            'kinh_nghiem_yeu_cau' => $tin->kinh_nghiem_yeu_cau,
            'trinh_do_yeu_cau' => $tin->trinh_do_yeu_cau,
            'dia_diem_lam_viec' => $tin->dia_diem_lam_viec,
            'hinh_thuc_lam_viec' => $tin->hinh_thuc_lam_viec,
            'muc_luong_tu' => $tin->muc_luong_tu,
            'muc_luong_den' => $tin->muc_luong_den,
            'don_vi_luong' => $tin->don_vi_luong,
            'parsed_salary_json' => $tin->parsing?->parsed_salary_json ?? [],
            'parsed_location_json' => $tin->parsing?->parsed_location_json ?? [],
            'parsed_skills' => $tin->parsing?->parsed_skills_json ?? [],
            'parsed_requirements' => $tin->parsing?->parsed_requirements_json ?? [],
            'required_skills' => $jobProfile['required_skills'],
            'requirements' => $jobProfile['requirements'],
            'industries' => $jobProfile['industries'],
        ];
    }

    private function buildComparisonMatrix(Collection $items, array $jobProfile): array
    {
        return [
            'required_skills' => $jobProfile['required_skills'],
            'rows' => $items->map(fn (array $item) => [
                'ho_so_id' => $item['ho_so']['id'] ?? null,
                'candidate_name' => $item['candidate']['ho_ten'] ?? 'Ứng viên',
                'score' => $item['score'] ?? 0,
                'recommendation' => $item['recommendation'] ?? '',
                'matched_skills' => $item['matched_skills'] ?? [],
                'missing_skills' => $item['missing_skills'] ?? [],
                'explanation' => $item['explanation'] ?? null,
                'ai_explanation' => $item['ai_explanation'] ?? null,
                'breakdown' => $item['score_breakdown'] ?? [],
            'source' => $item['source_insights']['label'] ?? null,
            'confidence' => $item['confidence'] ?? null,
            'structured_explanation' => $item['structured_explanation'] ?? null,
            'strongest_area' => $this->comparisonInsight($item, true),
            'weakest_area' => $this->comparisonInsight($item, false),
        ])->values()->all(),
        ];
    }

    private function comparisonInsight(array $item, bool $highest): string
    {
        $structured = $item['structured_explanation'] ?? [];
        $key = $highest ? 'strengths' : 'weaknesses';
        $items = $structured[$key] ?? [];

        if (is_array($items)) {
            $text = collect($items)
                ->map(fn ($value) => is_array($value) ? ($value['text'] ?? $value['value'] ?? null) : $value)
                ->filter()
                ->map(fn ($value) => trim((string) $value))
                ->first();

            if ($text) {
                return $text;
            }
        }

        return $this->breakdownInsight($item['score_breakdown'] ?? [], $highest);
    }

    private function breakdownInsight(array $breakdown, bool $highest): string
    {
        if (!$breakdown) {
            return 'Chưa đủ dữ liệu';
        }

        $highest ? arsort($breakdown) : asort($breakdown);
        $key = (string) array_key_first($breakdown);
        $score = round((float) ($breakdown[$key] ?? 0));

        return "{$this->breakdownLabel($key)} ({$score}%) - {$this->breakdownMeaning($key, $highest)}";
    }

    private function breakdownLabel(string $key): string
    {
        return [
            'skills' => 'Kỹ năng',
            'experience' => 'Kinh nghiệm',
            'education' => 'Trình độ',
            'industry' => 'Ngành nghề',
            'cv_quality' => 'Chất lượng CV',
            'evidence' => 'Minh chứng',
            'ai_match' => 'Đánh giá AI',
        ][$key] ?? $key;
    }

    private function breakdownMeaning(string $key, bool $highest): string
    {
        $meanings = [
            'skills' => [
                true => 'kỹ năng trong CV khớp tốt với yêu cầu JD',
                false => 'kỹ năng trong CV còn thiếu hoặc chưa thể hiện rõ',
            ],
            'experience' => [
                true => 'số năm và nội dung kinh nghiệm phù hợp với yêu cầu',
                false => 'kinh nghiệm chưa đủ mạnh so với yêu cầu',
            ],
            'education' => [
                true => 'trình độ học vấn đáp ứng tốt yêu cầu',
                false => 'trình độ học vấn chưa rõ hoặc thấp hơn yêu cầu',
            ],
            'industry' => [
                true => 'bối cảnh ngành nghề gần với vị trí đang tuyển',
                false => 'ngành nghề mục tiêu/chuyên môn chưa sát JD',
            ],
            'cv_quality' => [
                true => 'CV có nhiều mục dữ liệu rõ ràng để đánh giá',
                false => 'CV còn ít thông tin hoặc thiếu cấu trúc đánh giá',
            ],
            'evidence' => [
                true => 'có dự án, chứng chỉ hoặc liên kết minh chứng năng lực',
                false => 'thiếu dự án, chứng chỉ hoặc minh chứng kết quả',
            ],
            'ai_match' => [
                true => 'AI đánh giá tổng thể CV và JD có mức khớp cao',
                false => 'AI đánh giá tổng thể CV và JD còn nhiều điểm lệch',
            ],
        ];

        return $meanings[$key][$highest] ?? ($highest ? 'đây là điểm mạnh nổi bật' : 'đây là điểm cần cải thiện');
    }

    private function formatExperienceDuration(float $years): string
    {
        if ($years <= 0) {
            return '0 năm';
        }

        if ($years < 1) {
            $months = max(1, (int) round($years * 12));
            return "{$months} tháng";
        }

        $formatted = rtrim(rtrim(number_format($years, 2, '.', ''), '0'), '.');

        return "{$formatted} năm";
    }

    private function mapCandidate(?NguoiDung $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'ho_ten' => $user->ho_ten,
            'email' => $user->email,
            'so_dien_thoai' => $user->so_dien_thoai,
            'anh_dai_dien' => $user->anh_dai_dien,
            'avatar_url' => $user->anh_dai_dien
                ? url('/api/v1/anh-dai-dien?path=' . urlencode($user->anh_dai_dien))
                : null,
        ];
    }

    private function mapProfile(HoSo $profile): array
    {
        return [
            'id' => $profile->id,
            'nguoi_dung_id' => $profile->nguoi_dung_id,
            'tieu_de_ho_so' => $profile->tieu_de_ho_so,
            'muc_tieu_nghe_nghiep' => $profile->muc_tieu_nghe_nghiep,
            'trinh_do' => $profile->trinh_do,
            'kinh_nghiem_nam' => $profile->kinh_nghiem_nam,
            'mo_ta_ban_than' => $profile->mo_ta_ban_than,
            'nguon_ho_so' => $profile->nguon_ho_so,
            'mau_cv' => $profile->mau_cv,
            'bo_cuc_cv' => $profile->bo_cuc_cv,
            'ten_template_cv' => $profile->ten_template_cv,
            'che_do_mau_cv' => $profile->che_do_mau_cv,
            'vi_tri_ung_tuyen_muc_tieu' => $profile->vi_tri_ung_tuyen_muc_tieu,
            'ten_nganh_nghe_muc_tieu' => $profile->ten_nganh_nghe_muc_tieu,
            'che_do_anh_cv' => $profile->che_do_anh_cv,
            'anh_cv' => $profile->anh_cv,
            'anh_cv_url' => $profile->anh_cv_url,
            'ky_nang_json' => $profile->ky_nang_json,
            'kinh_nghiem_json' => $profile->kinh_nghiem_json,
            'hoc_van_json' => $profile->hoc_van_json,
            'du_an_json' => $profile->du_an_json,
            'chung_chi_json' => $profile->chung_chi_json,
            'raw_text' => $profile->parsing?->raw_text,
            'parsed_skills_json' => $profile->parsing?->parsed_skills_json ?? [],
            'parsed_experience_json' => $profile->parsing?->parsed_experience_json ?? [],
            'parsed_education_json' => $profile->parsing?->parsed_education_json ?? [],
            'file_cv' => $profile->file_cv,
            'file_cv_url' => $profile->file_cv
                ? route('nha-tuyen-dung.ho-sos.cv', ['id' => $profile->id])
                : null,
        ];
    }
}
