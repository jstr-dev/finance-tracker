<?php

namespace Tests\Feature;

use App\Models\DefaultCategory;
use App\Models\MerchantNormalization;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\GeminiService;
use App\Services\Import\AmericanExpressImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTransactionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        
        DefaultCategory::create(['name' => 'Shopping']);
        DefaultCategory::create(['name' => 'Groceries']);
    }

    public function test_imports_csv_and_creates_transactions(): void
    {
        $user = User::factory()->create();
        
        $csv = "Date,Description,Amount,Reference,Category,Extended Details,Appears on your statement as,Town/City,Postcode,Country\n";
        $csv .= "02/11/2025,\"ACME STORE\",42.50,'TX123456',\"General Purchases-Online\",\"\",\"ACME STORE*ABC\",\"SPRINGFIELD\",\"12345\",\"US\"\n";
        $csv .= "03/11/2025,\"GROCERY MART\",15.75,'TX789012',\"Groceries\",\"\",\"GROCERY MART 123\",\"RIVERSIDE\",\"67890\",\"US\"";
        
        Storage::put('test.csv', $csv);
        
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->andReturn("Acme Store|ACME.*\nGrocery Mart|GROCERY.*", "Shopping|General\\s+Purchases.*\nGroceries|Groceries.*");
        });
        
        $service = new AmericanExpressImportService();
        $service->import($user, 'test.csv');
        
        $this->assertEquals(2, UserTransaction::count());
        
        $acme = UserTransaction::where('transaction_id', 'TX123456')->first();
        $this->assertNotNull($acme);
        $this->assertEquals($user->id, $acme->user_id);
        $this->assertEquals('ACME STORE*ABC', $acme->payee);
        $this->assertEquals('Acme Store', $acme->merchant);
        $this->assertEquals('Shopping', $acme->category);
        $this->assertEquals('42.50', $acme->amount);
        $this->assertEquals('SPRINGFIELD', $acme->city);
        $this->assertEquals('12345', $acme->postcode);
        $this->assertEquals('US', $acme->country);
        
        $grocery = UserTransaction::where('transaction_id', 'TX789012')->first();
        $this->assertNotNull($grocery);
        $this->assertEquals('Grocery Mart', $grocery->merchant);
        $this->assertEquals('Groceries', $grocery->category);
    }

    public function test_updates_existing_transactions_on_reimport(): void
    {
        $user = User::factory()->create();
        
        UserTransaction::create([
            'user_id' => $user->id,
            'transaction_id' => 'TX123456',
            'payee' => 'OLD MERCHANT',
            'merchant' => 'Old',
            'category' => 'Old Category',
            'amount' => 5.00,
            'transaction_date' => now(),
            'currency' => 'GBP',
            'import_id' => null,
        ]);
        
        $csv = "Date,Description,Amount,Reference\n";
        $csv .= "02/11/2025,\"NEW MERCHANT\",10.00,'TX123456'";
        
        Storage::put('test.csv', $csv);
        
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')->andReturn("New Merchant|NEW.*");
        });
        
        $service = new AmericanExpressImportService();
        $service->import($user, 'test.csv');
        
        $this->assertEquals(1, UserTransaction::count());
        
        $transaction = UserTransaction::where('transaction_id', 'TX123456')->first();
        $this->assertEquals('New Merchant', $transaction->merchant);
        $this->assertEquals('10.00', $transaction->amount);
    }

    public function test_processes_transactions_in_chunks(): void
    {
        $user = User::factory()->create();
        
        $csv = "Date,Description,Amount,Reference\n";
        for ($i = 1; $i <= 250; $i++) {
            $csv .= "01/01/2025,\"MERCHANT {$i}\",10.00,'TX{$i}'\n";
        }
        
        Storage::put('test.csv', $csv);
        
        $callCount = 0;
        $this->mock(GeminiService::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('chat')->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                return str_repeat("Merchant|MERCHANT.*\n", 100);
            });
        });
        
        $service = new AmericanExpressImportService();
        $service->import($user, 'test.csv');
        
        $this->assertEquals(250, UserTransaction::count());
        $this->assertGreaterThanOrEqual(1, $callCount);
    }

    public function test_caches_normalized_merchants_between_chunks(): void
    {
        $user = User::factory()->create();
        
        $csv = "Date,Description,Amount,Reference\n";
        $csv .= "01/01/2025,\"WIDGET CO*ABC\",10.00,'TX1'\n";
        $csv .= str_repeat("01/01/2025,\"OTHER MERCHANT\",5.00,'TX{n}'\n", 99);
        $csv .= "02/01/2025,\"WIDGET CO*XYZ\",15.00,'TX101'";
        
        Storage::put('test.csv', $csv);
        
        $aiCalls = 0;
        $this->mock(GeminiService::class, function ($mock) use (&$aiCalls) {
            $mock->shouldReceive('chat')->andReturnUsing(function () use (&$aiCalls) {
                $aiCalls++;
                return "Widget Co|WIDGET\\s+CO.*\n" . str_repeat("Other|OTHER.*\n", 99);
            });
        });
        
        $service = new AmericanExpressImportService();
        $service->import($user, 'test.csv');
        
        $widget1 = UserTransaction::where('transaction_id', 'TX1')->first();
        $widget2 = UserTransaction::where('transaction_id', 'TX101')->first();
        
        $this->assertEquals('Widget Co', $widget1->merchant);
        $this->assertEquals('Widget Co', $widget2->merchant);
        $this->assertEquals(1, MerchantNormalization::where('regex_pattern', 'WIDGET\s+CO.*')->count());
        $this->assertGreaterThanOrEqual(1, $aiCalls);
    }

    public function test_throws_exception_on_invalid_headers(): void
    {
        $this->expectException(\App\Exceptions\InvalidHeadersException::class);
        
        $user = User::factory()->create();
        
        $csv = "WrongHeader1,WrongHeader2\n";
        $csv .= "value1,value2";
        
        Storage::put('test.csv', $csv);
        
        $service = new AmericanExpressImportService();
        $service->import($user, 'test.csv');
    }

    public function test_sets_import_id_when_provided(): void
    {
        $user = User::factory()->create();
        
        $csv = "Date,Description,Amount,Reference\n";
        $csv .= "01/01/2025,\"MERCHANT\",10.00,'TX1'";
        
        Storage::put('test.csv', $csv);
        
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')->andReturn("Merchant|MERCHANT.*");
        });
        
        $service = new AmericanExpressImportService();
        $import = $service->startImport($user, 'test.csv');
        
        $transaction = UserTransaction::first();
        $this->assertEquals($import->id, $transaction->import_id);
    }

    public function test_normalizes_headers_before_validation(): void
    {
        $user = User::factory()->create();
        
        $csv = " Date , Description , Amount , Reference \n";
        $csv .= "01/01/2025,\"MERCHANT\",10.00,'TX1'";
        
        Storage::put('test.csv', $csv);
        
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')->andReturn("Merchant|MERCHANT.*");
        });
        
        $service = new AmericanExpressImportService();
        $service->import($user, 'test.csv');
        
        $this->assertEquals(1, UserTransaction::count());
    }
}

