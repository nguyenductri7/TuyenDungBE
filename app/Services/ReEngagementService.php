<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\NguoiDung;
use App\Models\TinTuyenDung;
use App\Models\UngTuyen;
use App\Support\EncodedId;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReEngagementService
{
    public function __construct(private readonly AppNotificationService $notificationService)
    {
    }

    public function buildInsights(NguoiDung $candidate, int $similarLimit = 6): array
    {
        $savedJobs = $this->savedJobs($candidate);
        $appliedJobIds = $this->appliedJobIds($candidate);
        $now = now();

        $actionableSavedJobs = $savedJobs
            ->filter(fn (TinTuyenDung $job) => !$appliedJobIds->contains((int) $job->id))
            ->filter(fn (TinTuyenDung $job) => $this->isActiveJob($job));

        $expiringSavedJobs = $actionableSavedJobs
            ->filter(function (TinTuyenDung $job) use ($now): bool {
                if (!$job->ngay_het_han) {
                    return false;
                }

                return $job->ngay_het_han->greaterThanOrEqualTo($now)
                    && $job->ngay_het_han->lessThanOrEqualTo($now->copy()->addDays(3));
            })
            ->sortBy('ngay_het_han')
            ->take(5)
            ->map(fn (TinTuyenDung $job) => $this->mapJob($job, ['reason_type' => 'expiring_saved_job']))
            ->values()
            ->all();

        $staleSavedJobs = $actionableSavedJobs
            ->filter(function (TinTuyenDung $job) use ($now): bool {
                $savedAt = $job->pivot?->created_at;

                return $savedAt && $savedAt->lessThanOrEqualTo($now->copy()->subDays(7));
            })
            ->sortBy('pivot.created_at')
            ->take(5)
            ->map(fn (TinTuyenDung $job) => $this->mapJob($job, ['reason_type' => 'stale_saved_job']))
            ->values()
            ->all();

        $similarJobs = $this->similarJobs($candidate, $savedJobs, $appliedJobIds, $similarLimit)
            ->map(fn (array $item) => $this->mapJob($item['job'], [
                'reason_type' => 'similar_to_saved_jobs',
                'match_score' => $item['score'],
                'match_reasons' => $item['reasons'],
                'source_saved_jobs' => $item['source_saved_jobs'],
            ]))
            ->values()
            ->all();

        return [
            'summary' => [
                'saved_jobs' => $savedJobs->count(),
                'expiring_saved_jobs' => count($expiringSavedJobs),
                'stale_saved_jobs' => count($staleSavedJobs),
                'similar_jobs' => count($similarJobs),
            ],
            'expiring_saved_jobs' => $expiringSavedJobs,
            'stale_saved_jobs' => $staleSavedJobs,
            'similar_jobs' => $similarJobs,
        ];
    }

    public function runForCandidate(NguoiDung $candidate, bool $dryRun = false): array
    {
        $insights = $this->buildInsights($candidate, 4);
        $stats = [
            'candidate_id' => (int) $candidate->id,
            'expiring_notifications' => 0,
            'stale_notifications' => 0,
            'similar_notifications' => 0,
        ];

        foreach ($insights['expiring_saved_jobs'] as $job) {
            if ($this->recentNotificationExists($candidate, 'candidate_saved_job_expiring', (int) $job['id'], now()->subDay())) {
                continue;
            }

            $encodedJobId = EncodedId::encode((int) $job['id']);

            if (!$dryRun) {
                $this->notificationService->createForUser(
                    $candidate,
                    'candidate_saved_job_expiring',
                    'Tin đã lưu sắp hết hạn',
                    "Vị trí {$job['tieu_de']} sẽ hết hạn trong {$job['days_until_deadline']} ngày. Đây là thời điểm tốt để xem lại và ứng tuyển.",
                    "/jobs/{$encodedJobId}",
                    [
                        'tin_tuyen_dung_id' => (int) $job['id'],
                        'tin_tuyen_dung_encoded_id' => $encodedJobId,
                        'ngay_het_han' => $job['ngay_het_han'],
                        'days_until_deadline' => $job['days_until_deadline'],
                        'source' => 're_engagement_engine',
                    ],
                );
            }

            $stats['expiring_notifications']++;
        }

        foreach ($insights['stale_saved_jobs'] as $job) {
            if ($this->recentNotificationExists($candidate, 'candidate_saved_job_follow_up', (int) $job['id'], now()->subDays(7))) {
                continue;
            }

            if (!$dryRun) {
                $this->notificationService->createForUser(
                    $candidate,
                    'candidate_saved_job_follow_up',
                    'Bạn vẫn quan tâm tin đã lưu này?',
                    "Bạn đã lưu {$job['tieu_de']} một thời gian nhưng chưa ứng tuyển. Hãy xem lại yêu cầu hoặc bỏ lưu nếu không còn phù hợp.",
                    '/saved-jobs',
                    [
                        'tin_tuyen_dung_id' => (int) $job['id'],
                        'tin_tuyen_dung_encoded_id' => EncodedId::encode((int) $job['id']),
                        'saved_at' => $job['saved_at'],
                        'source' => 're_engagement_engine',
                    ],
                );
            }

            $stats['stale_notifications']++;
        }

        foreach (array_slice($insights['similar_jobs'], 0, 2) as $job) {
            if ($this->recentNotificationExists($candidate, 'candidate_similar_job_suggestion', (int) $job['id'], now()->subDays(5))) {
                continue;
            }

            $encodedJobId = EncodedId::encode((int) $job['id']);

            if (!$dryRun) {
                $this->notificationService->createForUser(
                    $candidate,
                    'candidate_similar_job_suggestion',
                    'Có job tương tự tin bạn đã lưu',
                    "Hệ thống tìm thấy {$job['tieu_de']} khá giống các tin bạn đã lưu, điểm gợi ý {$job['match_score']}/100.",
                    "/jobs/{$encodedJobId}",
                    [
                        'tin_tuyen_dung_id' => (int) $job['id'],
                        'tin_tuyen_dung_encoded_id' => $encodedJobId,
                        'match_score' => $job['match_score'],
                        'match_reasons' => $job['match_reasons'],
                        'source_saved_jobs' => $job['source_saved_jobs'],
                        'source' => 're_engagement_engine',
                    ],
                );
            }

            $stats['similar_notifications']++;
        }

        return $stats;
    }

    private function savedJobs(NguoiDung $candidate): Collection
    {
        return $candidate->tinDaLuus()
            ->with([
                'congTy:id,ten_cong_ty,logo,dia_chi',
                'nganhNghes:id,ten_nganh',
                'kyNangYeuCaus.kyNang:id,ten_ky_nang',
                'parsing:id,tin_tuyen_dung_id,parsed_skills_json,parsed_requirements_json',
            ])
            ->orderBy('luu_tins.created_at', 'desc')
            ->get();
    }

    private function appliedJobIds(NguoiDung $candidate): Collection
    {
        $profileIds = $candidate->hoSos()->pluck('id');

        if ($profileIds->isEmpty()) {
            return collect();
        }

        return UngTuyen::query()
            ->whereIn('ho_so_id', $profileIds)
            ->where('da_rut_don', false)
            ->pluck('tin_tuyen_dung_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function similarJobs(NguoiDung $candidate, Collection $savedJobs, Collection $appliedJobIds, int $limit): Collection
    {
        if ($savedJobs->isEmpty()) {
            return collect();
        }

        $savedProfile = $this->buildSavedProfile($savedJobs);
        $savedJobIds = $savedJobs->pluck('id')->map(fn ($id) => (int) $id);
        $excludeIds = $savedJobIds->merge($appliedJobIds)->unique()->values()->all();

        if (empty($savedProfile['industry_ids']) && empty($savedProfile['skills']) && empty($savedProfile['title_tokens'])) {
            return collect();
        }

        $jobs = TinTuyenDung::query()
            ->with([
                'congTy:id,ten_cong_ty,logo,dia_chi',
                'nganhNghes:id,ten_nganh',
                'kyNangYeuCaus.kyNang:id,ten_ky_nang',
                'parsing:id,tin_tuyen_dung_id,parsed_skills_json,parsed_requirements_json',
            ])
            ->where('trang_thai', TinTuyenDung::TRANG_THAI_HOAT_DONG)
            ->whereNotIn('id', $excludeIds ?: [0])
            ->where(function ($query): void {
                $query->whereNull('ngay_het_han')
                    ->orWhere('ngay_het_han', '>=', now());
            })
            ->latest('published_at')
            ->limit(160)
            ->get();

        return $jobs
            ->map(fn (TinTuyenDung $job) => $this->scoreSimilarJob($job, $savedProfile, $savedJobs))
            ->filter(fn (array $item) => $item['score'] >= 35)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    private function buildSavedProfile(Collection $savedJobs): array
    {
        $industryIds = [];
        $skills = [];
        $titleTokens = [];
        $locations = [];

        foreach ($savedJobs as $job) {
            foreach ($job->nganhNghes as $industry) {
                $industryIds[] = (int) $industry->id;
            }

            foreach ($this->jobSkills($job) as $skill) {
                $skills[] = $skill;
            }

            foreach ($this->titleTokens((string) $job->tieu_de) as $token) {
                $titleTokens[] = $token;
            }

            if ($job->dia_diem_lam_viec) {
                $locations[] = $this->normalize((string) $job->dia_diem_lam_viec);
            }
        }

        return [
            'industry_ids' => array_values(array_unique($industryIds)),
            'skills' => array_values(array_unique($skills)),
            'title_tokens' => array_values(array_unique($titleTokens)),
            'locations' => array_values(array_unique(array_filter($locations))),
        ];
    }

    private function scoreSimilarJob(TinTuyenDung $job, array $savedProfile, Collection $savedJobs): array
    {
        $jobIndustryIds = $job->nganhNghes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $jobSkills = $this->jobSkills($job);
        $jobTitleTokens = $this->titleTokens((string) $job->tieu_de);
        $jobLocation = $this->normalize((string) $job->dia_diem_lam_viec);

        $industryOverlap = count(array_intersect($savedProfile['industry_ids'], $jobIndustryIds));
        $skillOverlap = count(array_intersect($savedProfile['skills'], $jobSkills));
        $titleOverlap = count(array_intersect($savedProfile['title_tokens'], $jobTitleTokens));
        $locationOverlap = $jobLocation && in_array($jobLocation, $savedProfile['locations'], true);

        $score = 0;
        $reasons = [];

        if ($industryOverlap > 0) {
            $score += min(35, $industryOverlap * 18);
            $reasons[] = 'Cùng nhóm ngành với tin đã lưu';
        }

        if ($skillOverlap > 0) {
            $score += min(45, $skillOverlap * 12);
            $matchedSkills = array_values(array_intersect($savedProfile['skills'], $jobSkills));
            $reasons[] = 'Khớp kỹ năng: ' . implode(', ', array_slice($matchedSkills, 0, 4));
        }

        if ($titleOverlap > 0) {
            $score += min(15, $titleOverlap * 5);
            $reasons[] = 'Tiêu đề/vị trí tương đồng';
        }

        if ($locationOverlap) {
            $score += 5;
            $reasons[] = 'Cùng khu vực làm việc';
        }

        $sourceSavedJobs = $savedJobs
            ->filter(function (TinTuyenDung $savedJob) use ($jobIndustryIds, $jobSkills): bool {
                $sourceIndustryIds = $savedJob->nganhNghes->pluck('id')->map(fn ($id) => (int) $id)->all();
                $sourceSkills = $this->jobSkills($savedJob);

                return count(array_intersect($sourceIndustryIds, $jobIndustryIds)) > 0
                    || count(array_intersect($sourceSkills, $jobSkills)) > 0;
            })
            ->take(3)
            ->map(fn (TinTuyenDung $source) => [
                'id' => (int) $source->id,
                'tieu_de' => $source->tieu_de,
            ])
            ->values()
            ->all();

        return [
            'job' => $job,
            'score' => min(100, $score),
            'reasons' => array_values(array_unique($reasons)),
            'source_saved_jobs' => $sourceSavedJobs,
        ];
    }

    private function mapJob(TinTuyenDung $job, array $extra = []): array
    {
        $deadline = $job->ngay_het_han;
        $daysUntilDeadline = $deadline ? max(0, now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false)) : null;

        return [
            'id' => (int) $job->id,
            'tieu_de' => $job->tieu_de,
            'cong_ty' => $job->congTy ? [
                'id' => (int) $job->congTy->id,
                'ten_cong_ty' => $job->congTy->ten_cong_ty,
                'logo' => $job->congTy->logo,
            ] : null,
            'dia_diem_lam_viec' => $job->dia_diem_lam_viec,
            'cap_bac' => $job->cap_bac,
            'hinh_thuc_lam_viec' => $job->hinh_thuc_lam_viec,
            'muc_luong_tu' => $job->muc_luong_tu,
            'muc_luong_den' => $job->muc_luong_den,
            'ngay_het_han' => $deadline?->toISOString(),
            'days_until_deadline' => $daysUntilDeadline,
            'saved_at' => $job->pivot?->created_at?->toISOString(),
            'nganh_nghes' => $job->nganhNghes
                ->map(fn ($industry) => ['id' => (int) $industry->id, 'ten_nganh' => $industry->ten_nganh])
                ->values()
                ->all(),
            'ky_nangs' => array_slice($this->jobSkills($job, false), 0, 8),
            ...$extra,
        ];
    }

    private function isActiveJob(TinTuyenDung $job): bool
    {
        if ((int) $job->trang_thai !== TinTuyenDung::TRANG_THAI_HOAT_DONG) {
            return false;
        }

        return !$job->ngay_het_han || $job->ngay_het_han->greaterThanOrEqualTo(now());
    }

    private function recentNotificationExists(NguoiDung $candidate, string $type, int $jobId, $since): bool
    {
        return AppNotification::query()
            ->where('nguoi_dung_id', $candidate->id)
            ->where('loai', $type)
            ->where('du_lieu_bo_sung->tin_tuyen_dung_id', $jobId)
            ->where('created_at', '>=', $since)
            ->exists();
    }

    private function jobSkills(TinTuyenDung $job, bool $normalized = true): array
    {
        $skills = $job->kyNangYeuCaus
            ->pluck('kyNang.ten_ky_nang')
            ->filter()
            ->values()
            ->all();

        $skills = array_merge(
            $skills,
            $this->flattenParsedBlock($job->parsing?->parsed_skills_json),
            $this->flattenParsedBlock($job->parsing?->parsed_requirements_json)
        );

        $skills = array_values(array_unique(array_filter(array_map(
            fn ($skill) => is_string($skill) ? trim($skill) : null,
            $skills
        ))));

        if (!$normalized) {
            return $skills;
        }

        return array_values(array_unique(array_filter(array_map(fn ($skill) => $this->normalize($skill), $skills))));
    }

    private function flattenParsedBlock($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $flat = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $flat[] = $item;
                continue;
            }

            if (is_array($item)) {
                foreach (['skill_name', 'ten_ky_nang', 'requirement', 'name', 'value'] as $key) {
                    if (!empty($item[$key]) && is_string($item[$key])) {
                        $flat[] = $item[$key];
                        break;
                    }
                }
            }
        }

        return $flat;
    }

    private function titleTokens(string $title): array
    {
        $tokens = preg_split('/\s+/u', $this->normalize($title)) ?: [];
        $stopWords = ['tuyen', 'dung', 'nhan', 'vien', 'thuc', 'tap', 'senior', 'junior', 'middle', 'viec', 'lam'];

        return array_values(array_unique(array_filter($tokens, function (string $token) use ($stopWords): bool {
            return mb_strlen($token) >= 3 && !in_array($token, $stopWords, true);
        })));
    }

    private function normalize(string $value): string
    {
        $value = Str::ascii(mb_strtolower($value));
        $value = preg_replace('/[^a-z0-9+#.\s-]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return $value;
    }
}
