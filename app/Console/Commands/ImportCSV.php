<?php

namespace App\Console\Commands;

use App\Models\Import;
use App\Models\User;
use App\Services\Import\AmericanExpressImportService;
use Illuminate\Console\Command;

class ImportCSV extends Command
{
    protected $signature = 'import:csv 
        {userId : The ID of the user to import the CSV for} 
        {path : The path to the CSV file to import}
        {--type=amex : The type of CSV to import}
        {--async : Process the import asynchronously via queue}
    ';

    protected $description = 'Import CSV using given file path.';
    protected ?string $path = null;
    protected $services = [
        'amex' => AmericanExpressImportService::class,
    ];

    public function handle(): int
    {
        $user = User::find($this->argument('userId'));
        $type = $this->option('type');
        $async = $this->option('async');
        $serviceClass = $this->services[$type] ?? null;

        if (!$serviceClass) {
            $this->error("Unsupported import type: {$type}");
            return 1;
        }

        if (!$user) {
            $this->error('User not found.');
            return 1;
        }

        try {
            $this->info("Starting import for user ID: {$user->id}");

            /** @var \App\Services\Import\AbstractImportService $service */
            $service = app($serviceClass);
            $import = $service->startImport($user, $this->argument('path'), $async);

            if ($async) {
                $this->info("Import queued successfully. Import ID: {$import->id}");
            } else {
                $this->info('Import completed successfully.');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error during import: ' . $e->getMessage());
            return 1;
        }
    }
}
