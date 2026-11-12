<?php

namespace Tests\Feature;

use App\Filament\Resources\Panel\ApiKeyResource\Pages\ListApiKeys;
use App\Models\ApiKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ApiKeyListTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('admin', 'web');
    }

    protected function userWithRole(string $role): User
    {
        $user = User::create([
            'name' => 'Test '.uniqid(),
            'email' => 'user.'.uniqid().'@example.test',
            'password' => Hash::make('secret123'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function makeKey(array $attributes = []): ApiKey
    {
        $plain = ApiKey::generatePlainKey();

        return ApiKey::create(array_merge([
            'name' => 'Key Test '.uniqid(),
            'key_hash' => ApiKey::hashKey($plain),
            'key_prefix' => ApiKey::prefixOf($plain),
            'rate_limit' => 60,
            'is_active' => true,
            'created_by' => null,
        ], $attributes));
    }

    public function test_admin_biasa_bisa_melihat_daftar(): void
    {
        $key = $this->makeKey(['name' => 'Key Terlihat']);

        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(ListApiKeys::class)
            ->assertSuccessful()
            ->assertSee('Key Terlihat');
    }

    public function test_kolom_status_kedaluwarsa(): void
    {
        $this->makeKey(['name' => 'Key Lama', 'expires_at' => now()->subDay()]);

        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(ListApiKeys::class)->assertSee('Kedaluwarsa');
    }
}
