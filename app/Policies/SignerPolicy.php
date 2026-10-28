<?php

namespace App\Policies;

use App\Models\Signer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Hak akses resource "Penandatangan Surat" mengikuti pola Shield yang sama
 * dengan resource lain (permission `*_panel::signer`). Super admin tetap
 * melewati seluruh gate (Filament Shield intercept `before`).
 */
class SignerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_panel::signer');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Signer $signer): bool
    {
        return $user->can('view_panel::signer');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_panel::signer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Signer $signer): bool
    {
        return $user->can('update_panel::signer');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Signer $signer): bool
    {
        return $user->can('delete_panel::signer');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_panel::signer');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Signer $signer): bool
    {
        return $user->can('force_delete_panel::signer');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_panel::signer');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Signer $signer): bool
    {
        return $user->can('restore_panel::signer');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_panel::signer');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Signer $signer): bool
    {
        return $user->can('replicate_panel::signer');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_panel::signer');
    }
}
