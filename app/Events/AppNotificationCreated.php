<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(private readonly AppNotification $notification)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->notification->nguoi_dung_id}");
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->loai,
            'title' => $this->notification->tieu_de,
            'message' => $this->notification->noi_dung,
            'to' => $this->notification->duong_dan,
            'data' => $this->notification->du_lieu_bo_sung ?: null,
            'read_at' => optional($this->notification->da_doc_luc)?->toISOString(),
            'created_at' => optional($this->notification->created_at)?->toISOString(),
        ];
    }
}
