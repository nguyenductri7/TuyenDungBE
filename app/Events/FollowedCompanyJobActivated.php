<?php

namespace App\Events;

use App\Models\NguoiDung;
use App\Models\TinTuyenDung;
use App\Support\EncodedId;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FollowedCompanyJobActivated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const TYPE_PUBLISHED = 'published';
    public const TYPE_REOPENED = 'reopened';

    /**
     * @param  array<int>  $recipientIds
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $recipientIds,
        private readonly array $payload,
    ) {
    }

    public static function fromJob(TinTuyenDung $job, string $activityType): ?self
    {
        if (!in_array($activityType, [self::TYPE_PUBLISHED, self::TYPE_REOPENED], true)) {
            return null;
        }

        $job->loadMissing('congTy:id,ten_cong_ty,logo,trang_thai');

        $company = $job->congTy;

        if (!$company) {
            return null;
        }

        $activityAt = match ($activityType) {
            self::TYPE_REOPENED => $job->reactivated_at,
            default => $job->published_at ?? $job->created_at,
        } ?? now();

        $recipientIds = $company->nguoiDungTheoDois()
            ->where('nguoi_dungs.vai_tro', NguoiDung::VAI_TRO_UNG_VIEN)
            ->where('nguoi_dungs.trang_thai', 1)
            ->wherePivot('created_at', '<=', $activityAt)
            ->pluck('nguoi_dungs.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (!$recipientIds) {
            return null;
        }

        $openJobsCount = $company->tinTuyenDungs()
            ->where('trang_thai', TinTuyenDung::TRANG_THAI_HOAT_DONG)
            ->where(function ($query) {
                $query->whereNull('ngay_het_han')
                    ->orWhere('ngay_het_han', '>=', now());
            })
            ->count();

        $activityAtIso = $activityAt->toISOString();
        $publishedAt = $job->published_at?->toISOString();
        $reactivatedAt = $job->reactivated_at?->toISOString();
        $createdAt = $job->created_at?->toISOString() ?? now()->toISOString();
        $isReopened = $activityType === self::TYPE_REOPENED;

        return new self($recipientIds, [
            'notification_id' => "candidate-followed-company-job-{$activityType}-{$company->id}-{$job->id}-{$activityAtIso}",
            'type' => $isReopened ? 'followed-company-job-reopened' : 'followed-company-job-published',
            'activity_type' => $activityType,
            'company' => [
                'id' => (int) $company->id,
                'name' => (string) $company->ten_cong_ty,
                'logo_url' => $company->logo
                    ? url('/api/v1/cong-ty-logo?path=' . urlencode($company->logo))
                    : null,
                'open_jobs_count' => (int) $openJobsCount,
            ],
            'job' => [
                'id' => (int) $job->id,
                'encoded_id' => EncodedId::encode((int) $job->id),
                'title' => (string) $job->tieu_de,
                'created_at' => $createdAt,
                'published_at' => $publishedAt,
                'reactivated_at' => $reactivatedAt,
                'activity_at' => $activityAtIso,
                'activity_type' => $activityType,
                'dia_diem_lam_viec' => $job->dia_diem_lam_viec,
                'hinh_thuc_lam_viec' => $job->hinh_thuc_lam_viec,
                'muc_luong_tu' => $job->muc_luong_tu,
                'muc_luong_den' => $job->muc_luong_den,
                'ngay_het_han' => $job->ngay_het_han?->toISOString(),
                'trang_thai' => (int) $job->trang_thai,
            ],
            'message' => $isReopened
                ? "{$company->ten_cong_ty} vừa mở lại vị trí {$job->tieu_de}."
                : "{$company->ten_cong_ty} vừa đăng vị trí {$job->tieu_de}.",
            'sent_at' => now()->toISOString(),
        ]);
    }

    public function broadcastOn(): array
    {
        return array_map(
            fn (int $recipientId) => new PrivateChannel("candidate.{$recipientId}"),
            $this->recipientIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'company.job-activity';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }

    public function recipientIds(): array
    {
        return $this->recipientIds;
    }

    public function notificationPayload(): array
    {
        return $this->payload;
    }
}
