<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Webkul\Partner\Models\Partner;

class PartnerController extends BaseResourceController
{
    protected string $modelClass = Partner::class;

    protected array $searchableFields = [
        'name',
        'email',
        'phone',
        'city',
        'street1',
    ];

    protected array $filterableFields = [
        'id',
        'sub_type',
        'company_id',
        'parent_id',
        'user_id',
        'is_active',
        'is_company',
        'state',
        'country',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'email',
        'city',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'company',
        'parent',
        'children',
        'user',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'sub_type' => 'nullable|string|max:50',
            'street1' => 'nullable|string|max:255',
            'street2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'parent_id' => 'nullable|integer|exists:partners_partners,id',
            'is_company' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'sub_type' => 'nullable|string|max:50',
            'street1' => 'nullable|string|max:255',
            'street2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'parent_id' => 'nullable|integer|exists:partners_partners,id',
            'is_company' => 'nullable|boolean',
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
