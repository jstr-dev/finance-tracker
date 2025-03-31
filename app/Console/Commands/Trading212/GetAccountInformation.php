<?php

namespace App\Console\Commands\Trading212;

use App\Models\UserConnection;
use App\Services\Trading212Service;
use Illuminate\Console\Command;

class GetAccountInformation extends Command
{
    protected $signature = 't212:get-info {conn} {--method=} {--pie=}';
    protected $description = 'Get account information about an account.';

    public function handle()
    {
        $conn = $this->argument('conn');
        $conn = UserConnection::trading212()
            ->where('id', $conn)
            ->first();
        $method = $this->option('method') ?? 'info';

        if (!$conn) {
            return $this->error('Sorry, that connection doesn\'t exist.');
        }

        $service = new Trading212Service();

        switch ($method) {
            case 'info':
                $info = $service->getAccountInformation($conn);
                break;
            case 'pies':
                $info = $service->getAllPies($conn);
                break;
            case 'pie':
                $pieId = $this->option('pie');
                $info = $service->getPie($conn, $pieId);
                break;
            case 'instruments':
                $info = $service->getInstruments($conn);
                break;
            default:
                return $this->error('That method doesn\'t exist.');
        }

        print_r($info);
    }
}
