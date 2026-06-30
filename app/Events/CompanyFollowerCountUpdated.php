<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyFollowerCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly int $companyId,
        private readonly int $followerCount,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("company.public.{$this->companyId}"),
            new PrivateChannel("company.{$this->companyId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'company.followers.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->companyId,
            'follower_count' => $this->followerCount,
            'updated_at' => now()->toISOString(),
        ];
    }
}
