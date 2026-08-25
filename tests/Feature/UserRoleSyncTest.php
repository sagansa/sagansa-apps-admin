<?php

namespace Tests\Feature;

use App\Filament\Resources\Panel\UserResource\Pages\CreateUser;
use App\Filament\Resources\Panel\UserResource\Pages\EditUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserRoleSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('sales', 'web');
        Role::findOrCreate('customer', 'web');

        foreach (['view_any_panel::user', 'update_panel::user', 'create_panel::user'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    protected function superAdmin(): User
    {
        $user = $this->createUser();
        $user->assignRole('super_admin');
        $user->givePermissionTo(['view_any_panel::user', 'update_panel::user', 'create_panel::user']);

        return $user;
    }

    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'User Test '.uniqid(),
            'email' => 'user.'.uniqid().'@example.test',
            'password' => Hash::make('secret123'),
        ], $attributes));
    }

    public function test_edit_page_keeps_roles_selected_by_super_admin(): void
    {
        $target = $this->createUser();
        $adminRoleId = Role::where('name', 'admin')->value('id');
        $salesRoleId = Role::where('name', 'sales')->value('id');

        $this->actingAs($this->superAdmin());

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm([
                'roles' => [$adminRoleId, $salesRoleId],
            ], 'form')
            ->call('save')
            ->assertHasNoFormErrors();

        $target->refresh();

        $this->assertTrue($target->hasRole('admin'), 'role admin harus tersimpan');
        $this->assertTrue($target->hasRole('sales'), 'role sales harus tersimpan');
    }

    public function test_create_page_assigns_selected_roles_and_customer(): void
    {
        $adminRoleId = Role::where('name', 'admin')->value('id');

        $this->actingAs($this->superAdmin());

        $email = 'karyawan.'.uniqid().'@example.test';

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Karyawan Baru',
                'email' => $email,
                'password' => 'secret123',
                'roles' => [$adminRoleId],
            ], 'form')
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', $email)->firstOrFail();

        $this->assertTrue($user->hasRole('admin'), 'role pilihan harus tersimpan');
        $this->assertTrue($user->hasRole('customer'), 'role customer harus tetap ada');
    }
}
