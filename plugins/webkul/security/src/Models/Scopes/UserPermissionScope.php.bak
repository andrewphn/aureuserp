<?php

namespace Webkul\Security\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Webkul\Security\Enums\PermissionType;

class UserPermissionScope implements Scope
{
    protected $ownerRelation;

    /**
     * Create a new scope instance.
     */
    public function __construct(string $ownerRelation)
    {
        $this->ownerRelation = $ownerRelation;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Skip scope if no authenticated user (e.g., CLI/tinker context)
        if (!$user) {
            return;
        }

        // Skip scope if user doesn't have resource_permission set
        if (!isset($user->resource_permission)) {
            return;
        }

        if ($user->resource_permission === PermissionType::GLOBAL->value) {
            return;
        }

        if ($user->resource_permission === PermissionType::INDIVIDUAL->value) {
            $builder->whereHas($this->ownerRelation, function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });

            $builder->orWhereHas('followers', function ($q) use ($user) {
                $q->where('chatter_followers.partner_id', $user->partner_id);
            });
        }

        if ($user->resource_permission === PermissionType::GROUP->value) {
            $teamIds = $user->teams()->pluck('id');

            $builder->whereHas("$this->ownerRelation.teams", function ($q) use ($teamIds) {
                $q->whereIn('teams.id', $teamIds);
            });
        }
    }
}
