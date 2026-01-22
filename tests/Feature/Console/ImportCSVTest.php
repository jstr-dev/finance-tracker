<?php

namespace Tests\Feature\Console;

use App\Models\Import;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportCSVTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        
        // Seed default categories required for normalization
        \App\Models\DefaultCategory::create(['name' => 'Shopping']);
        \App\Models\DefaultCategory::create(['name' => 'Groceries']);
        \App\Models\DefaultCategory::create(['name' => 'Entertainment']);
        \App\Models\DefaultCategory::create(['name' => 'Transportation']);
        \App\Models\DefaultCategory::create(['name' => 'Bills']);
        \App\Models\DefaultCategory::create(['name' => 'Restaurants']);
        \App\Models\DefaultCategory::create(['name' => 'Health']);
    }

    public function test_service_can_import_csv_directly(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->andReturn(
                    "Acme Store|ACME.*\nGrocery Mart|GROCERY.*",
                    "Shopping|General\\s+Purchases.*\nGroceries|General\\s+Purchases.*"
                );
        });

        $user = User::factory()->create();
        
        $csvContent = <<<CSV
            Date,Description,Amount,Extended Details,Appears On Your Statement As,Address,Town/City,Postcode,Country,Reference,Category
            02/11/2025,ACME STORE*ABC123  ONLINE.COM,42.50,,ACME STORE*ABC123  ONLINE.COM,123 MAIN STREET,SPRINGFIELD,12345,UNITED STATES,'TX001234567890001',General Purchases-Online Purchases
            03/11/2025,GROCERY MART 9999 DOWNTOWN,15.75,,GROCERY MART 9999 DOWNTOWN,456 OAK AVENUE,RIVERSIDE,67890,UNITED STATES,'TX001234567890002',General Purchases-Groceries
        CSV;

        Storage::disk('local')->put('test-amex.csv', $csvContent);

        // Test service directly
        $import = Import::create([
            'user_id' => $user->id,
            'type' => 'amex',
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $service = app(\App\Services\Import\AmericanExpressImportService::class);
        $service->setImportId($import->id);
        $service->import($user, 'test-amex.csv');

        $this->assertEquals(2, UserTransaction::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('user_transactions', [
            'user_id' => $user->id,
            'transaction_id' => 'TX001234567890001',
            'import_id' => $import->id,
        ]);
    }

    public function test_imports_amex_csv_successfully(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->andReturn(
                    "Acme Store|ACME.*\nGrocery Mart|GROCERY.*",
                    "Shopping|General\\s+Purchases.*\nGroceries|General\\s+Purchases.*"
                );
        });

        $user = User::factory()->create();
        
        $csvContent = <<<CSV
            Date,Description,Amount,Extended Details,Appears On Your Statement As,Address,Town/City,Postcode,Country,Reference,Category
            02/11/2025,ACME STORE*ABC123  ONLINE.COM,42.50,,ACME STORE*ABC123  ONLINE.COM,123 MAIN STREET,SPRINGFIELD,12345,UNITED STATES,'TX001234567890001',General Purchases-Online Purchases
            03/11/2025,GROCERY MART 9999 DOWNTOWN,15.75,,GROCERY MART 9999 DOWNTOWN,456 OAK AVENUE,RIVERSIDE,67890,UNITED STATES,'TX001234567890002',General Purchases-Groceries
        CSV;

        Storage::disk('local')->put('test-amex.csv', $csvContent);

        $this->artisan('import:csv', [
            'userId' => $user->id,
            'path' => 'test-amex.csv',
            '--type' => 'amex'
        ])
            ->assertExitCode(0);

        // Verify import record was created
        $this->assertDatabaseHas('imports', [
            'user_id' => $user->id,
            'type' => 'amex',
            'status' => 'completed',
        ]);

        $import = Import::where('user_id', $user->id)->first();

        // Verify transactions were imported with import_id
        $this->assertDatabaseHas('user_transactions', [
            'user_id' => $user->id,
            'transaction_id' => 'TX001234567890001',
            'import_id' => $import->id,
            'amount' => 42.50,
        ]);

        $this->assertDatabaseHas('user_transactions', [
            'user_id' => $user->id,
            'transaction_id' => 'TX001234567890002',
            'import_id' => $import->id,
            'amount' => 15.75,
        ]);

        $this->assertEquals(2, UserTransaction::where('user_id', $user->id)->count());
    }

    public function test_fails_when_user_not_found(): void
    {
        Storage::disk('local')->put('test.csv', 'test data');

        $this->artisan('import:csv', [
            'userId' => 999,
            'path' => 'test.csv',
        ])->assertExitCode(1);
    }

    public function test_fails_with_invalid_import_type(): void
    {
        $user = User::factory()->create();
        Storage::disk('local')->put('test.csv', 'test data');

        $this->artisan('import:csv', [
            'userId' => $user->id,
            'path' => 'test.csv',
            '--type' => 'invalid-type'
        ])->assertExitCode(1);
    }
}
