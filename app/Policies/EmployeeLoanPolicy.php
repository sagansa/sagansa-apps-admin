<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EmployeeLoan;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeLoanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_panel::employee::loan');
    }

    public function view(User $user, EmployeeLoan $model): bool
    {
        return $user->can('view_panel::employee::loan');
    }

    public function create(User $user): bool
    {
        return $user->can('create_panel::employee::loan');
    }

    public function update(User $user, EmployeeLoan $model): bool
    {
        return $user->can('update_panel::employee::loan');
    }

    public function delete(User $user, EmployeeLoan $model): bool
    {
        return $user->can('delete_panel::employee::loan');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_panel::employee::loan');
    }
}
