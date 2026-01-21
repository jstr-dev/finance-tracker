<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Import\AmericanExpressImportService;
use Illuminate\Console\Command;

class ImportCSV extends Command
{
    protected $signature = 'import:csv 
        {userId : The ID of the user to import the CSV for} 
        {path : The path to the CSV file to import}
        {--type=amex : The type of CSV to import}
    ';

    protected $description = 'Import CSV using given file path.';
    protected ?string $path = null;
    protected $services = [
        'amex' => AmericanExpressImportService::class,
    ];

    public function handle()
    {
        $user = User::find($this->argument('userId'));
        $type = $this->option('type');
        $serviceClass = $this->services[$type] ?? null;

        if (!$serviceClass) {
            $this->error("Unsupported import type: {$type}");
            return;
        }

        /** @var \App\Services\Import\AbstractImportService $service */
        $service = app($serviceClass);
        
        if (!$user) {
            $this->error('User not found.');
            return;
        }

        try {
            $this->info("Starting import for user ID: {$user->id}");
            $service->import($user, $this->argument('path'));
        } catch (\Exception $e) {
            $this->error('Error during import: ' . $e->getMessage());
        }
    }
}
