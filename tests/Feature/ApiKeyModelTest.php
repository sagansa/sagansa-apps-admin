<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Tests\TestCase;

class ApiKeyModelTest extends TestCase
{
    public function test_generate_plain_key_format(): void
    {
        $plain = ApiKey::generatePlainKey();

        $this->assertSame(49, strlen($plain));
        $this->assertStringStartsWith('sga_sk_', $plain);
        $this->assertMatchesRegularExpression('/^sga_sk_[A-Za-z0-9]{42}$/', $plain);
    }

    public function test_hash_dan_prefix(): void
    {
        $plain = 'sga_sk_abcdefghij1234567890abcdefghij1234567890ab';

        $this->assertSame(hash('sha256', $plain), ApiKey::hashKey($plain));
        $this->assertSame('sga_sk_abc…', ApiKey::prefixOf($plain));
    }

    public function test_status_kedaluwarsa_nonaktif_aktif(): void
    {
        $expired = ApiKey::make(['expires_at' => now()->subDay(), 'is_active' => true]);
        $this->assertSame('Kedaluwarsa', $expired->status());

        $inactive = ApiKey::make(['expires_at' => null, 'is_active' => false]);
        $this->assertSame('Nonaktif', $inactive->status());

        $active = ApiKey::make(['expires_at' => now()->addYear(), 'is_active' => true]);
        $this->assertSame('Aktif', $active->status());
    }

    public function test_id_adalah_uuid(): void
    {
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/i',
            (new ApiKey)->newUniqueId()
        );
    }
}
