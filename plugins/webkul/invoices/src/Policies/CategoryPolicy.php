<?php

namespace Webkul\Invoice\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Invoice\Models\Category;
use Webkul\Security\Models\User;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any::invoice::category');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->can('view::invoice::category');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create::invoice::category');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->can('update::invoice::category');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->can('delete::invoice::category');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any::invoice::category');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->can('force_delete::invoice::category');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any::invoice::category');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Category $category): bool
    {
        return $user->can('restore::invoice::category');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any::invoice::category');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Category $category): bool
    {
        return $user->can('replicate::invoice::category');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder::invoice::category');
    }
}
