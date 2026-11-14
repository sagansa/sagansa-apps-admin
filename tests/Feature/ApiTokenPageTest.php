<?php

namespace Tests\Feature;

use App\Filament\Pages\ApiTokens;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokenPageTest extends TestCase
{
    use DatabaseTransactions;

    /** Token Sanctum ditulis ke koneksi mysql_auth — transaksi test harus membungkusnya juga. */
    protected array $connectionsToTransact = [null, 'mysql_auth'];

    protected function panelUser(): User
    {
        return User::create([
            'name' => 'Test '.uniqid(),
            'email' => 'apitoken.'.uniqid().'@example.test',
            'password' => Hash::make('secret123'),
        ]);
    }

    public function test_halaman_api_token_render_dengan_form_buat(): void
    {
        $this->actingAs($this->panelUser());

        Livewire::test(ApiTokens::class)
            ->assertSuccessful()
            ->assertSee('Buat token baru')
            ->assertSee('Nama token')
            ->assertSee('Generate token');
    }

    public function test_create_token_menampilkan_plaintext_sekali(): void
    {
        $this->actingAs($this->panelUser());

        $component = Livewire::test(ApiTokens::class)
            ->set('tokenName', 'esta-uji')
            ->call('createToken')
            ->assertSuccessful()
            ->assertSee('salin sekarang');

        $this->assertStringContainsString('|', (string) $component->instance()->plainToken);
    }

    public function test_tabel_token_dipaginasi(): void
    {
        $user = $this->panelUser();
        $this->actingAs($user);

        // 30 token → halaman 1 berisi 25 (default per page), sisanya di halaman 2.
        for ($i = 0; $i < 30; $i++) {
            $user->createToken("tok-$i");
        }

        Livewire::test(ApiTokens::class)
            ->assertSuccessful()
            ->assertSee('tok-29')
            ->assertDontSee('tok-0')
            ->call('gotoPage', 2)
            ->assertSee('tok-0')
            ->assertDontSee('tok-29');
    }
}
