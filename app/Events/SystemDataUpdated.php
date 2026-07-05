<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemDataUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ?int $actorId,
        public readonly string $path,
        public readonly string $method,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('system-updates')];
    }

    public function broadcastAs(): string
    {
        return 'system.data.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'actor_id' => $this->actorId,
            'path' => $this->path,
            'method' => $this->method,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
