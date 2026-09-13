<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class JarvisAlertEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $message;
    public string $severity;  // 'info', 'warning', 'critical'
    public ?array $data;

    public function __construct(string $message, string $severity = 'info', ?array $data = null)
    {
        $this->message  = $message;
        $this->severity = $severity;
        $this->data     = $data;
    }

    public function broadcastOn(): array
    {
        return [new \Illuminate\Broadcasting\PrivateChannel('admin.jarvis')];
    }

    public function broadcastAs(): string
    {
        return 'JarvisAlert';
    }
}
