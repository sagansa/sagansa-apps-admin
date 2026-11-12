<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Halaman & daftar API key terbuka untuk semua user panel yang login.
 * Mutasi kredensial (create/update/delete/dst.) HANYA super_admin —
 * keras via hasRole, tidak lewat permission Shield, sehingga pemberian
 * permission lewat RoleResource tidak dapat menembusnya.
 */
class ApiKeyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ApiKey $apiKey): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('super_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function replicate(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('super_admin');
    }

    public function reorder(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
