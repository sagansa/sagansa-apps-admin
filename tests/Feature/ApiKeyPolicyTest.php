<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiKeyPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('staff', 'web');
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

    #[DataProvider('roleBukanSuperAdmin')]
    public function test_role_biasa_hanya_bisa_melihat(string $role): void
    {
        $user = $this->userWithRole($role);
        $apiKey = ApiKey::make();

        $this->assertTrue($user->can('viewAny', ApiKey::class));
        $this->assertTrue($user->can('view', $apiKey));
        $this->assertFalse($user->can('create', ApiKey::class));
        $this->assertFalse($user->can('update', $apiKey));
        $this->assertFalse($user->can('delete', $apiKey));
        $this->assertFalse($user->can('deleteAny', ApiKey::class));
    }

    public static function roleBukanSuperAdmin(): array
    {
        return [['admin'], ['staff']];
    }

    public function test_super_admin_bisa_semua(): void
    {
        $user = $this->userWithRole('super_admin');
        $apiKey = ApiKey::make();

        $this->assertTrue($user->can('viewAny', ApiKey::class));
        $this->assertTrue($user->can('view', $apiKey));
        $this->assertTrue($user->can('create', ApiKey::class));
        $this->assertTrue($user->can('update', $apiKey));
        $this->assertTrue($user->can('delete', $apiKey));
        $this->assertTrue($user->can('deleteAny', ApiKey::class));
        $this->assertTrue($user->can('forceDelete', $apiKey));
        $this->assertTrue($user->can('replicate', $apiKey));
    }

    public function test_user_tanpa_role_juga_bisa_melihat(): void
    {
        $user = User::create([
            'name' => 'No Role '.uniqid(),
            'email' => 'norole.'.uniqid().'@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $this->assertTrue($user->can('viewAny', ApiKey::class));
        $this->assertFalse($user->can('create', ApiKey::class));
    }
}
