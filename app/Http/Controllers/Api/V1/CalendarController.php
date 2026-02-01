<?php

namespace App\Http\Controllers\Api\V1;

use Webkul\Employee\Models\Calendar;

class CalendarController extends BaseResourceController
{
    protected string $modelClass = Calendar::class;

    protected array $searchableFields = ['name'];

    protected array $filterableFields = [
        'id',
        'company_id',
        'is_active',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'company',
        'attendances',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'is_active' => 'nullable|boolean',
            'hours_per_day' => 'nullable|numeric|min:0|max:24',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'is_active' => 'nullable|boolean',
            'hours_per_day' => 'nullable|numeric|min:0|max:24',
        ];
    }
}
