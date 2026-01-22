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
        $serviceClass = $this->services[$type] ?? null;

        if (!$serviceClass) {
            $this->error("Unsupported import type: {$type}");
            return 1;
        }

        /** @var \App\Services\Import\AbstractImportService $service */
        $service = app($serviceClass);
        
        if (!$user) {
            $this->error('User not found.');
            return 1;
        }

        $import = null;

        try {
            $this->info("Starting import for user ID: {$user->id}");

            // Create Import record
            $import = Import::create([
                'user_id' => $user->id,
                'type' => $type,
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Set import ID on service
            $service->setImportId($import->id);

            // Run import
            $service->import($user, $this->argument('path'));

            // Mark as completed
            $import->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->info('Import completed successfully.');
            return 0;
        } catch (\Exception $e) {
            if ($import) {
                $import->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                ]);
            }

            $this->error('Error during import: ' . $e->getMessage());
            return 1;
        }
    }
}
