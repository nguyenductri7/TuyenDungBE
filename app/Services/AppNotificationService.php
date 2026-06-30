<?php

namespace App\Services;

use App\Events\AppNotificationCreated;
use App\Models\AppNotification;
use App\Models\CongTy;
use App\Models\NguoiDung;
use Illuminate\Support\Collection;

class AppNotificationService
{
    public function createForUser(
        int|NguoiDung|null $recipient,
        string $type,
        string $title,
        string $message,
        ?string $path = null,
        array $metadata = [],
        bool $broadcast = true,
    ): ?AppNotification {
        $recipientId = $recipient instanceof NguoiDung ? $recipient->id : $recipient;

        if (!$recipientId) {
            return null;
        }

        $notification = AppNotification::create([
            'nguoi_dung_id' => (int) $recipientId,
            'loai' => $type,
            'tieu_de' => $title,
            'noi_dung' => $message,
            'duong_dan' => $path,
            'du_lieu_bo_sung' => $metadata ?: null,
        ]);

        if ($broadcast) {
            try {
                broadcast(new AppNotificationCreated($notification));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $notification;
    }

    /**
     * @param  iterable<int|NguoiDung>  $recipients
     */
    public function createForUsers(
        iterable $recipients,
        string $type,
        string $title,
        string $message,
        ?string $path = null,
        array $metadata = [],
    ): void {
        foreach ($this->normalizeRecipientIds($recipients) as $recipientId) {
            $this->createForUser($recipientId, $type, $title, $message, $path, $metadata);
        }
    }

    public function recruitmentRecipients(CongTy $company, ?int $preferredHrId = null): Collection
    {
        $activeMembers = $company->thanhViens()
            ->where('nguoi_dungs.trang_thai', 1)
            ->get(['nguoi_dungs.id']);

        $roleRecipients = $activeMembers
            ->filter(function (NguoiDung $member) use ($company): bool {
                if (CongTy::normalizeVaiTroNoiBo($member->pivot?->vai_tro_noi_bo) === CongTy::VAI_TRO_NOI_BO_OWNER) {
                    return true;
                }

                $permissions = $member->layQuyenNoiBoCongTy($company);

                return collect(['jobs', 'applications', 'interviews', 'offers', 'onboarding'])
                    ->contains(fn (string $permission) => ($permissions[$permission] ?? false) === true);
            })
            ->pluck('id');

        return collect([
            $company->nguoi_dung_id,
            $preferredHrId,
            ...$roleRecipients->all(),
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function normalizeRecipientIds(iterable $recipients): array
    {
        return collect($recipients)
            ->map(fn ($recipient) => $recipient instanceof NguoiDung ? $recipient->id : $recipient)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
