<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Webkul\Employee\Models\Employee;

class EmployeeController extends BaseResourceController
{
    protected string $modelClass = Employee::class;

    protected array $searchableFields = [
        'name',
        'job_title',
        'work_email',
        'work_phone',
    ];

    protected array $filterableFields = [
        'id',
        'department_id',
        'job_position_id',
        'user_id',
        'company_id',
        'manager_id',
        'is_active',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'job_title',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'department',
        'user',
        'company',
        'manager',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer|exists:employees_departments,id',
            'job_position_id' => 'nullable|integer|exists:employees_job_positions,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'manager_id' => 'nullable|integer|exists:employees_employees,id',
            'work_email' => 'nullable|email|max:255',
            'work_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer|exists:employees_departments,id',
            'job_position_id' => 'nullable|integer|exists:employees_job_positions,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'manager_id' => 'nullable|integer|exists:employees_employees,id',
            'work_email' => 'nullable|email|max:255',
            'work_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        return $data;
    }
}
