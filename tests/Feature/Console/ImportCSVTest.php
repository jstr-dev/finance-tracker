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
                    json_encode(['normalizations' => [
                        ['normalized' => 'Acme Store', 'regex' => 'ACME.*'],
                        ['normalized' => 'Grocery Mart', 'regex' => 'GROCERY.*'],
                        ['normalized' => 'Payment', 'regex' => 'PAYMENT.*'],
                    ]]),
                    json_encode(['normalizations' => [
                        ['normalized' => 'Shopping', 'regex' => 'General\\s+Purchases.*'],
                        ['normalized' => 'Groceries', 'regex' => 'General\\s+Purchases.*'],
                        ['normalized' => 'Bills', 'regex' => 'Payments.*'],
                    ]])
                );
        });

        $user = User::factory()->create();
        
        $csvContent = file_get_contents(base_path('tests/fixtures/csv/amex-test.csv'));
        Storage::disk('local')->put('test-amex.csv', $csvContent);

        $service = app(\App\Services\Import\AmericanExpressImportService::class);
        $service->setDisk('local');
        $import = $service->startImport($user, 'test-amex.csv');

        $this->assertEquals(3, UserTransaction::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('user_transactions', [
            'user_id' => $user->id,
            'transaction_id' => 'TX001234567890001',
            'import_id' => $import->id,
            'account_type' => 'credit',
            'transaction_type' => 'purchase',
        ]);
    }

    public function test_imports_amex_csv_successfully(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->andReturn(
                    json_encode(['normalizations' => [
                        ['normalized' => 'Acme Store', 'regex' => 'ACME.*'],
                        ['normalized' => 'Grocery Mart', 'regex' => 'GROCERY.*'],
                        ['normalized' => 'Payment', 'regex' => 'PAYMENT.*'],
                    ]]),
                    json_encode(['normalizations' => [
                        ['normalized' => 'Shopping', 'regex' => 'General\\s+Purchases.*'],
                        ['normalized' => 'Groceries', 'regex' => 'General\\s+Purchases.*'],
                        ['normalized' => 'Bills', 'regex' => 'Payments.*'],
                    ]])
                );
        });

        $user = User::factory()->create();
        
        $csvContent = file_get_contents(base_path('tests/fixtures/csv/amex-test.csv'));
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

        $this->assertEquals(3, UserTransaction::where('user_id', $user->id)->count());
        
        // Verify payment transaction is marked correctly
        $this->assertDatabaseHas('user_transactions', [
            'user_id' => $user->id,
            'transaction_id' => 'TX001234567890003',
            'import_id' => $import->id,
            'amount' => -1000.00,
            'account_type' => 'credit',
            'transaction_type' => 'payment',
        ]);
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

    public function test_trims_whitespace_from_csv_values(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->andReturn(
                    json_encode(['normalizations' => [['normalized' => 'Widget Co', 'regex' => 'WIDGET.*']]]),
                    json_encode(['normalizations' => [['normalized' => 'Shopping', 'regex' => 'General\\s+Purchases.*']]])
                );
        });

        $user = User::factory()->create();
        
        $csvContent = file_get_contents(base_path('tests/fixtures/csv/amex-with-whitespace.csv'));
        Storage::disk('local')->put('test-whitespace.csv', $csvContent);

        $this->artisan('import:csv', [
            'userId' => $user->id,
            'path' => 'test-whitespace.csv',
            '--type' => 'amex'
        ])
            ->assertExitCode(0);

        $this->assertDatabaseHas('user_transactions', [
            'user_id' => $user->id,
            'transaction_id' => 'TX001234567890003',
            'amount' => 99.99,
            'merchant' => 'Widget Co',
            'category' => 'Shopping',
        ]);
    }
}
