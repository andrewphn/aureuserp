<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Employee\Database\Factories\EmployeeEmployeeCategoryFactory;

/**
 * Employee Employee Category Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $employee_id
 * @property int $category_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $employee
 * @property-read \Illuminate\Database\Eloquent\Model|null $category
 *
 */
class EmployeeEmployeeCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'employees_employee_categories';

    protected $fillable = ['employee_id', 'category_id'];

    /**
     * Relationship to fetch the employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relationship to fetch the category (skill).
     */
    public function category()
    {
        return $this->belongsTo(EmployeeCategory::class, 'category_id');
    }

    /**
     * Get the factory instance for the model.
     */
    protected static function newFactory(): EmployeeEmployeeCategoryFactory
    {
        return EmployeeEmployeeCategoryFactory::new();
    }
}
