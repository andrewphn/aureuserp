<?php

namespace Webkul\Security\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as BaseRole;

/**
 * Role Eloquent model
 *
 */
class Role extends BaseRole
{
    /**
     * Get the Name attribute
     *
     * @param mixed $value The value to set
     * @return mixed
     */
    public function getNameAttribute($value)
    {
        return Str::ucfirst($value);
    }
}
