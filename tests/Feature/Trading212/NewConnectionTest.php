<?php

namespace Tests\Feature\Trading212;

use App\Models\User;
use App\Services\Trading212Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NewConnectionTest extends TestCase
{
    use RefreshDatabase;

    // Generate valid 64-char test credentials (validation requires min:64, max:64)
    public static string $validKeyId = 'test_key_id_1234567890123456789012345678901234567890123456789012';
    public static string $validSecretKey = 'test_secret_1234567890123456789012345678901234567890123456789012';
    
    public User $user;
    public $mock;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->mock = $this->mock(Trading212Service::class, function (MockInterface $mock) {
            $mock->shouldReceive('tokenHasAuth')
                ->andReturnTrue();
        });
    }

    #[DataProvider('invalidDataProvider')]
    public function test_new_connection_is_invalid(string $keyId, string $secretKeyId)
    {
        $this->actingAs($this->user);
        $this->post('/connections/trading212', [
            'key_id' => $keyId,
            'secret_key' => $secretKeyId,
        ])->assertSessionHasErrors();
        $this->mock->shouldNotHaveBeenCalled();
    }

    public static function invalidDataProvider()
    {
        return [
            'Too Short Key Id' => ['1234567', self::$validSecretKey],
            'Too Short Secret Key' => [self::$validKeyId, '1234567'],
            'Missing Key Id' => ['', self::$validSecretKey],
            'Missing Secret Key' => [self::$validKeyId, ''],
            'Invalid Key Id' => ['invalid', self::$validSecretKey],
            'Invalid Secret Key' => [self::$validKeyId, 'invalid'],
        ];
    }

    public function test_new_connection_is_valid()
    {
        $this->actingAs($this->user);

        $response = $this->post('/connections/trading212', [
            'key_id' => self::$validKeyId,
            'secret_key' => self::$validSecretKey,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->mock->shouldHaveReceived('tokenHasAuth')->once();
    }
}