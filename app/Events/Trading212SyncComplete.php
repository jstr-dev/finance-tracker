<?php

namespace App\Events;

use App\Models\UserConnection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Trading212SyncComplete implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private UserConnection $conn;

    public function __construct(UserConnection $conn)
    {
        $this->conn = $conn; 
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->conn->user_id),
        ];
    }
}
