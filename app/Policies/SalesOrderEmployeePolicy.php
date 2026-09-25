<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SalesOrderEmployee;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesOrderEmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SalesOrderEmployee $salesOrderEmployee): bool
    {
        return $user->can('view_panel::sales::order::employees') && $salesOrderEmployee->payment_status === 2;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SalesOrderEmployee $salesOrderEmployee): bool
    {
        // super_admin adalah akses darurat: boleh mengedit order terkunci sekalipun
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('update_panel::sales::order::employees') && $salesOrderEmployee->payment_status !== 2;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SalesOrderEmployee $salesOrderEmployee): bool
    {
        return $user->can('delete_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, SalesOrderEmployee $salesOrderEmployee): bool
    {
        return $user->can('force_delete_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, SalesOrderEmployee $salesOrderEmployee): bool
    {
        return $user->can('restore_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, SalesOrderEmployee $salesOrderEmployee): bool
    {
        return $user->can('replicate_panel::sales::order::employees');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_panel::sales::order::employees');
    }
}
