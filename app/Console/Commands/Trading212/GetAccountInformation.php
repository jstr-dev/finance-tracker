<?php

namespace App\Console\Commands\Trading212;

use App\Models\UserConnection;
use App\Services\Trading212Service;
use Illuminate\Console\Command;

class GetAccountInformation extends Command
{
    protected $signature = 't212:get-info {conn}';
    protected $description = 'Get account information about an account.';

    public function handle()
    {
        $conn = $this->argument('conn');
        $conn = UserConnection::trading212()
            ->where('id', $conn)
            ->first();
        
        if (!$conn) {
            return $this->error('Sorry, that connection doesn\'t exist.');
        }

        $info = (new Trading212Service())->getAccountInformation($conn);
        dd($info);
    }
}
