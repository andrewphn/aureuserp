<?php

namespace App\Http\Controllers\Api\V1;

use Webkul\Employee\Models\Department;

class DepartmentController extends BaseResourceController
{
    protected string $modelClass = Department::class;

    protected array $searchableFields = [
        'name',
        'complete_name',
    ];

    protected array $filterableFields = [
        'id',
        'parent_id',
        'manager_id',
        'company_id',
        'is_active',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'complete_name',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'parent',
        'manager',
        'company',
        'employees',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:employees_departments,id',
            'manager_id' => 'nullable|integer|exists:users,id',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|integer|exists:employees_departments,id',
            'manager_id' => 'nullable|integer|exists:users,id',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'is_active' => 'nullable|boolean',
        ];
    }
}
