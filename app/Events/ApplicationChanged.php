<?php

namespace App\Events;

use App\Models\UngTuyen;
use App\Support\EncodedId;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly int $applicationId,
        private readonly int $companyId,
        private readonly ?int $candidateId,
        private readonly string $changeType,
        private readonly array $payload = [],
    ) {
    }

    public static function fromApplication(UngTuyen $application, string $changeType, array $payload = []): ?self
    {
        $application->loadMissing(['tinTuyenDung:id,cong_ty_id,tieu_de', 'hoSo.nguoiDung:id']);

        $companyId = (int) ($application->tinTuyenDung?->cong_ty_id ?? 0);

        if (!$companyId) {
            return null;
        }

        return new self(
            applicationId: (int) $application->id,
            companyId: $companyId,
            candidateId: $application->hoSo?->nguoiDung?->id ? (int) $application->hoSo->nguoiDung->id : null,
            changeType: $changeType,
            payload: [
                'ung_tuyen_id' => (int) $application->id,
                'tin_tuyen_dung_id' => (int) $application->tin_tuyen_dung_id,
                'tin_tuyen_dung_encoded_id' => EncodedId::encode((int) $application->tin_tuyen_dung_id),
                'tin_tuyen_dung_tieu_de' => $application->tinTuyenDung?->tieu_de,
                'trang_thai' => (int) $application->trang_thai,
                'trang_thai_offer' => $application->trang_thai_offer !== null ? (int) $application->trang_thai_offer : null,
                'trang_thai_tham_gia_phong_van' => $application->trang_thai_tham_gia_phong_van,
                'da_rut_don' => (bool) $application->da_rut_don,
                ...$payload,
            ],
        );
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("company.{$this->companyId}")];

        if ($this->candidateId) {
            $channels[] = new PrivateChannel("user.{$this->candidateId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'application.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->changeType,
            'company_id' => $this->companyId,
            'candidate_id' => $this->candidateId,
            'payload' => $this->payload,
            'sent_at' => now()->toISOString(),
        ];
    }
}
