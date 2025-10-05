<?php

namespace App\Policies;

use App\Models\PdfDocument;
use Webkul\Security\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PdfDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the document.
     *
     * @param User $user
     * @param PdfDocument $document
     * @return bool
     */
    public function view(User $user, PdfDocument $document): bool
    {
        // Allow if user is the owner
        if ($document->uploaded_by === $user->id) {
            return true;
        }

        // Allow if user has admin role
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check polymorphic relationship permissions
        if ($document->documentable) {
            // If document is attached to a project, check project access
            if ($document->documentable_type === 'App\\Models\\Project') {
                return $user->can('view', $document->documentable);
            }

            // If document is attached to a partner, check partner access
            if ($document->documentable_type === 'App\\Models\\Partner') {
                return $user->can('view', $document->documentable);
            }

            // Add other polymorphic type checks as needed
        }

        return false;
    }

    /**
     * Determine whether the user can update the document.
     *
     * @param User $user
     * @param PdfDocument $document
     * @return bool
     */
    public function update(User $user, PdfDocument $document): bool
    {
        // Allow if user is the owner
        if ($document->uploaded_by === $user->id) {
            return true;
        }

        // Allow if user has admin role
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check polymorphic relationship permissions
        if ($document->documentable) {
            // If document is attached to a project, check project edit access
            if ($document->documentable_type === 'App\\Models\\Project') {
                return $user->can('update', $document->documentable);
            }

            // If document is attached to a partner, check partner edit access
            if ($document->documentable_type === 'App\\Models\\Partner') {
                return $user->can('update', $document->documentable);
            }

            // Add other polymorphic type checks as needed
        }

        return false;
    }

    /**
     * Determine whether the user can delete the document.
     *
     * @param User $user
     * @param PdfDocument $document
     * @return bool
     */
    public function delete(User $user, PdfDocument $document): bool
    {
        // Only owner or admin can delete
        return $document->uploaded_by === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the document.
     *
     * @param User $user
     * @param PdfDocument $document
     * @return bool
     */
    public function restore(User $user, PdfDocument $document): bool
    {
        // Only admin can restore soft-deleted documents
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the document.
     *
     * @param User $user
     * @param PdfDocument $document
     * @return bool
     */
    public function forceDelete(User $user, PdfDocument $document): bool
    {
        // Only admin can force delete
        return $user->hasRole('admin');
    }
}
