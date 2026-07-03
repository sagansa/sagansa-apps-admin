<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SalaryPenalty;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalaryPenaltyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_panel::salary::penalty');
    }

    public function view(User $user, SalaryPenalty $model): bool
    {
        return $user->can('view_panel::salary::penalty');
    }

    public function create(User $user): bool
    {
        return $user->can('create_panel::salary::penalty');
    }

    public function update(User $user, SalaryPenalty $model): bool
    {
        return $user->can('update_panel::salary::penalty');
    }

    public function delete(User $user, SalaryPenalty $model): bool
    {
        return $user->can('delete_panel::salary::penalty');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_panel::salary::penalty');
    }
}
