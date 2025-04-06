<?php

namespace App\Jobs;

use App\Events\Trading212SyncComplete;
use App\Models\UserConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncTrading212Data implements ShouldQueue
{
    use Queueable;

    protected UserConnection $conn;

    public function __construct(UserConnection $conn)
    {
        $this->conn = $conn;
    }

    public function handle(): void
    {
        event(new Trading212SyncComplete($this->conn));
    }
}
