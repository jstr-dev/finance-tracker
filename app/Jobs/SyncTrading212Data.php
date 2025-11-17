<?php

namespace App\Jobs;

use App\Events\Trading212SyncComplete;
use App\Models\UserConnection;
use App\Services\Trading212Service;
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
        $service = new Trading212Service();
        $service->sync($this->conn);

        $this->conn->status = 'active';
        $this->conn->first_sync_at = now();
        $this->conn->save();

        event(new Trading212SyncComplete($this->conn));
    }
}
