<?php

namespace App\Console\Commands\Trading212;

use App\Jobs\SyncTrading212Data;
use App\Models\UserConnection;
use Illuminate\Console\Command;

class TestSync extends Command
{
    protected $signature = 't212:test-sync';
    protected $description = 'Command description';

    public function handle()
    {
        $conn = UserConnection::where('user_id', '=', 1)
            ->first();
        SyncTrading212Data::dispatch($conn);
    }
}
