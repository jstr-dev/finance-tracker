<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\User;
use App\Services\Import\AbstractImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCSVImport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $importId,
        protected int $userId,
        protected string $path,
        protected string $serviceClass
    ) {}

    public function handle(): void
    {
        $import = Import::findOrFail($this->importId);
        $user = User::findOrFail($this->userId);
        
        /** @var AbstractImportService $service */
        $service = app($this->serviceClass);
        
        try {
            $service->processImport($import, $user, $this->path);
        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
            
            throw $e;
        }
    }
}
