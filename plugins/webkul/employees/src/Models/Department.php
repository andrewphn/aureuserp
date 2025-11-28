<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Employee\Database\Factories\DepartmentFactory;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Department Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property int $manager_id
 * @property int $company_id
 * @property int $parent_id
 * @property int $master_department_id
 * @property string|null $complete_name
 * @property string|null $parent_path
 * @property int $creator_id
 * @property string|null $color
 * @property-read \Illuminate\Database\Eloquent\Collection $jobPositions
 * @property-read \Illuminate\Database\Eloquent\Collection $employees
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Model|null $masterDepartment
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $manager
 *
 */
class Department extends Model
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, SoftDeletes;

    protected $table = 'employees_departments';

    protected $fillable = [
        'name',
        'manager_id',
        'company_id',
        'parent_id',
        'master_department_id',
        'complete_name',
        'parent_path',
        'creator_id',
        'color',
    ];

    /**
     * Created By
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Parent
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Master Department
     *
     * @return BelongsTo
     */
    public function masterDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'master_department_id');
    }

    /**
     * Job Positions
     *
     * @return HasMany
     */
    public function jobPositions(): HasMany
    {
        return $this->hasMany(EmployeeJobPosition::class);
    }

    /**
     * Employees
     *
     * @return HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Manager
     *
     * @return BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * New Factory
     *
     * @return DepartmentFactory
     */
    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }

    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($department) {
            if (! static::validateNoRecursion($department)) {
                throw new InvalidArgumentException('Circular reference detected in department hierarchy');
            }

            static::handleDepartmentData($department);
        });

        static::updating(function ($department) {
            if (! static::validateNoRecursion($department)) {
                throw new InvalidArgumentException('Circular reference detected in department hierarchy');
            }

            static::handleDepartmentData($department);
        });
    }

    /**
     * Validate No Recursion
     *
     * @param mixed $department
     */
    protected static function validateNoRecursion($department)
    {
        if (! $department->parent_id) {
            return true;
        }

        if ($department->exists && $department->id == $department->parent_id) {
            return false;
        }

        $visitedIds = [$department->exists ? $department->id : -1];
        $currentParentId = $department->parent_id;

        while ($currentParentId) {
            if (in_array($currentParentId, $visitedIds)) {
                return false;
            }

            $visitedIds[] = $currentParentId;
            $parent = static::find($currentParentId);

            if (! $parent) {
                break;
            }

            $currentParentId = $parent->parent_id;
        }

        return true;
    }

    /**
     * Handle Department Data
     *
     * @param mixed $department
     */
    protected static function handleDepartmentData($department)
    {
        if ($department->parent_id) {
            $parent = static::find($department->parent_id);
            $department->parent_path = $parent?->parent_path.$parent?->id.'/';

            $department->master_department_id = static::findTopLevelParentId($parent);
        } else {
            $department->parent_path = '/';
            $department->master_department_id = null;
        }

        $department->complete_name = static::getCompleteName($department);
    }

    /**
     * Find Top Level Parent Id
     *
     * @param mixed $department
     */
    protected static function findTopLevelParentId($department)
    {
        $currentDepartment = $department;

        while ($currentDepartment->parent_id) {
            $currentDepartment = static::find($currentDepartment->parent_id);
        }

        return $currentDepartment->id;
    }

    /**
     * Get Complete Name
     *
     * @param mixed $department
     */
    protected static function getCompleteName($department)
    {
        $names = [];
        $names[] = $department->name;

        $currentDepartment = $department;
        while ($currentDepartment->parent_id) {
            $currentDepartment = static::find($currentDepartment->parent_id);
            array_unshift($names, $currentDepartment->name);
        }

        return implode(' / ', $names);
    }
}
