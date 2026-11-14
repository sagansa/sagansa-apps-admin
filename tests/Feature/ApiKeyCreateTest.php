<?php

namespace Tests\Feature;

use App\Filament\Resources\Panel\ApiKeyResource\Pages\CreateApiKeys;
use App\Filament\Resources\Panel\ApiKeyResource\Pages\RevealApiKey;
use App\Models\ApiKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ApiKeyCreateTest extends TestCase
{
    use DatabaseTransactions;

    /** ApiKey ditulis ke koneksi mysql_auth — transaksi test harus membungkusnya juga. */
    protected array $connectionsToTransact = [null, 'mysql_auth'];

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

    public function test_create_menyimpan_hash_bukan_plaintext(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $this->actingAs($superAdmin);

        Livewire::test(CreateApiKeys::class)
            ->fillForm([
                'name' => 'Integrasi Gudang',
                'scopes' => ['products:read'],
                'rate_limit' => 120,
            ], 'form')
            ->call('create')
            ->assertHasNoFormErrors();

        $apiKey = ApiKey::where('name', 'Integrasi Gudang')->firstOrFail();

        $plain = session('api_key_plain');

        $this->assertNotNull($plain, 'plaintext harus ada di session flash');
        $this->assertStringStartsWith('sga_sk_', $plain);
        $this->assertSame(ApiKey::hashKey($plain), $apiKey->key_hash);
        $this->assertSame(120, $apiKey->rate_limit);
        $this->assertSame(['products:read'], $apiKey->scopes);
        $this->assertTrue($apiKey->is_active);
        $this->assertEquals($superAdmin->id, $apiKey->created_by);

        // Plaintext tidak boleh tersimpan di kolom mana pun
        $this->assertNotEquals($plain, $apiKey->key_hash);
        $this->assertNotEquals($plain, $apiKey->key_prefix);
    }

    public function test_create_redirect_ke_halaman_reveal(): void
    {
        $this->actingAs($this->userWithRole('super_admin'));

        Livewire::test(CreateApiKeys::class)
            ->fillForm(['name' => 'Key Redirect'], 'form')
            ->call('create');

        $this->assertNotNull(session('api_key_id'));
    }

    public function test_admin_biasa_tidak_bisa_create(): void
    {
        $user = $this->userWithRole('admin');
        $this->actingAs($user);

        $this->assertTrue($user->cannot('create', ApiKey::class));
        $this->assertDatabaseMissing('api_keys', ['name' => 'Key Terlarang'], 'mysql_auth');
    }

    public function test_reveal_menolak_tanpa_flash(): void
    {
        $this->actingAs($this->userWithRole('super_admin'));

        Livewire::test(RevealApiKey::class)
            ->assertRedirect(route('filament.admin.resources.panel.api-keys.index'));
    }

    public function test_reveal_ditolak_untuk_non_superadmin(): void
    {
        session()->flash('api_key_plain', 'sga_sk_dummy');
        session()->flash('api_key_id', 'dummy-id');

        $this->actingAs($this->userWithRole('admin'));

        // abort(403) di mount dikonversi Livewire menjadi response — uji guard-nya langsung.
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new RevealApiKey())->mount();
    }

    public function test_validasi_ip_dan_rate_limit(): void
    {
        $this->actingAs($this->userWithRole('super_admin'));

        Livewire::test(CreateApiKeys::class)
            ->fillForm([
                'name' => 'Key Validasi',
                'allowed_ips' => ['bukan-ip'],
                'rate_limit' => 0,
            ], 'form')
            ->call('create')
            ->assertHasFormErrors(['allowed_ips', 'rate_limit']);
    }
}
