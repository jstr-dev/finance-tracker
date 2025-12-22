<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Import\AmericanExpressImportService;
use Illuminate\Console\Command;

class ImportAmericanExpressCSV extends Command
{
    protected $signature = 'amex:import-csv {userId} {path}';
    protected $description = 'Import American Express CSV from given path.';

    public function handle()
    {
        $user = User::find($this->argument('userId')); 
        
        if (!$user) {
            $this->error('User not found.');
            return;
        }

        $path = $this->argument('path');
        $service = new AmericanExpressImportService(); 
        
        $this->info('Importing...');

        $service->import($user, $path);
    }
}
