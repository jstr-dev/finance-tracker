<?php

namespace Tests\Unit\Services\Import;

use App\Services\Import\AmericanExpressImportService;
use Tests\TestCase;

class AmericanExpressImportServiceTest extends TestCase
{
    public function test_returns_correct_type(): void
    {
        $service = new AmericanExpressImportService();
        
        $this->assertEquals('amex', $service->getType());
    }

    public function test_returns_required_csv_headers(): void
    {
        $service = new AmericanExpressImportService();
        $headers = $service->getRequiredCSVHeaders();
        
        $this->assertContains('Date', $headers);
        $this->assertContains('Description', $headers);
        $this->assertContains('Amount', $headers);
        $this->assertContains('Reference', $headers);
    }

    public function test_extracts_transaction_id_from_reference_field(): void
    {
        $service = new AmericanExpressImportService();
        
        $row = [
            'reference' => "'TX001234567890001'",
        ];
        
        $result = $service->getRowTransactionID($row);
        
        $this->assertEquals('TX001234567890001', $result);
    }

    public function test_extracts_category_from_row(): void
    {
        $service = new AmericanExpressImportService();
        
        $row = [
            'category' => 'General Purchases-Online Purchases',
        ];
        
        $result = $service->extractCategory($row);
        
        $this->assertEquals('General Purchases-Online Purchases', $result);
    }

    public function test_formats_row_for_import_with_all_fields(): void
    {
        $service = new AmericanExpressImportService();
        
        $row = [
            'date' => '02/11/2025',
            'description' => 'ACME STORE*ABC123  ONLINE.COM',
            'appears on your statement as' => 'ACME STORE*ABC123',
            'amount' => '42.50',
            'extended details' => 'Product purchase',
            'town/city' => 'SPRINGFIELD',
            'postcode' => '12345',
            'country' => 'US',
        ];
        
        $result = $service->formatRowForImport($row);
        
        $this->assertEquals([
            'transaction_date' => '2025-11-02',
            'payee' => 'ACME STORE*ABC123',
            'amount' => '42.50',
            'description' => 'Product purchase',
            'city' => 'SPRINGFIELD',
            'postcode' => '12345',
            'country' => 'US',
        ], $result);
    }

    public function test_uses_description_as_payee_when_statement_field_missing(): void
    {
        $service = new AmericanExpressImportService();
        
        $row = [
            'date' => '02/11/2025',
            'description' => 'FOOD MART STORES',
            'amount' => '25.00',
        ];
        
        $result = $service->formatRowForImport($row);
        
        $this->assertEquals('FOOD MART STORES', $result['payee']);
    }

    public function test_handles_missing_optional_fields(): void
    {
        $service = new AmericanExpressImportService();
        
        $row = [
            'date' => '02/11/2025',
            'description' => 'MERCHANT',
            'amount' => '10.00',
        ];
        
        $result = $service->formatRowForImport($row);
        
        $this->assertNull($result['description']);
        $this->assertNull($result['city']);
        $this->assertNull($result['postcode']);
        $this->assertNull($result['country']);
    }
}

