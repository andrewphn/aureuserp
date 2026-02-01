<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;

class ApiInfoController extends BaseApiController
{
    /**
     * Get API information and available endpoints
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'version' => 'v1',
            'documentation' => url('/docs/api/V1_API_DOCUMENTATION.md'),
            'authentication' => [
                'type' => 'Bearer Token',
                'header' => 'Authorization: Bearer {token}',
                'description' => 'Create tokens in admin panel under Settings > API Tokens',
            ],
            'rate_limit' => [
                'requests' => 60,
                'period' => '1 minute',
            ],
            'features' => [
                'pagination' => '?page=1&per_page=25',
                'filtering' => '?filter[field]=value',
                'sorting' => '?sort=-created_at,name',
                'search' => '?search=keyword',
                'includes' => '?include=relation1,relation2',
            ],
            'resources' => $this->getResources(),
        ], 'API V1 Information');
    }

    /**
     * Get list of available resources and their endpoints
     */
    protected function getResources(): array
    {
        return [
            'projects' => [
                'endpoints' => [
                    'GET /projects' => 'List all projects',
                    'POST /projects' => 'Create a project',
                    'GET /projects/{id}' => 'Get a project',
                    'PUT /projects/{id}' => 'Update a project',
                    'DELETE /projects/{id}' => 'Delete a project',
                ],
                'filterable' => ['id', 'is_active', 'is_converted', 'stage_id', 'partner_id', 'company_id', 'user_id', 'project_type'],
                'searchable' => ['name', 'project_number', 'draft_number', 'description'],
                'sortable' => ['id', 'name', 'project_number', 'created_at', 'updated_at', 'start_date', 'end_date'],
                'includable' => ['rooms', 'cabinets', 'partner', 'creator', 'user', 'stage', 'company', 'tags'],
            ],
            'rooms' => [
                'endpoints' => [
                    'GET /rooms' => 'List all rooms',
                    'GET /projects/{id}/rooms' => 'List rooms for a project',
                    'POST /projects/{id}/rooms' => 'Create room in a project',
                    'GET /rooms/{id}' => 'Get a room',
                    'PUT /rooms/{id}' => 'Update a room',
                    'DELETE /rooms/{id}' => 'Delete a room',
                ],
                'filterable' => ['id', 'project_id', 'name'],
                'searchable' => ['name', 'description'],
                'includable' => ['project', 'locations', 'cabinetRuns'],
            ],
            'cabinets' => [
                'endpoints' => [
                    'GET /cabinets' => 'List all cabinets',
                    'GET /cabinet-runs/{id}/cabinets' => 'List cabinets in a run',
                    'POST /cabinet-runs/{id}/cabinets' => 'Create cabinet in a run',
                    'GET /cabinets/{id}' => 'Get a cabinet',
                    'PUT /cabinets/{id}' => 'Update a cabinet',
                    'DELETE /cabinets/{id}' => 'Delete a cabinet',
                ],
                'filterable' => ['id', 'cabinet_run_id', 'cabinet_number', 'door_count', 'drawer_count'],
                'searchable' => ['cabinet_number', 'notes'],
                'sortable' => ['id', 'cabinet_number', 'length_inches', 'depth_inches', 'height_inches', 'created_at'],
                'includable' => ['cabinetRun', 'sections', 'drawers', 'doors', 'stretchers', 'faceframes', 'shelves'],
            ],
            'drawers' => [
                'endpoints' => [
                    'GET /drawers' => 'List all drawers',
                    'GET /sections/{id}/drawers' => 'List drawers in a section',
                    'POST /sections/{id}/drawers' => 'Create drawer in a section',
                    'GET /drawers/{id}' => 'Get a drawer',
                    'PUT /drawers/{id}' => 'Update a drawer',
                    'DELETE /drawers/{id}' => 'Delete a drawer',
                ],
            ],
            'doors' => [
                'endpoints' => [
                    'GET /doors' => 'List all doors',
                    'GET /sections/{id}/doors' => 'List doors in a section',
                    'POST /sections/{id}/doors' => 'Create door in a section',
                    'GET /doors/{id}' => 'Get a door',
                    'PUT /doors/{id}' => 'Update a door',
                    'DELETE /doors/{id}' => 'Delete a door',
                ],
            ],
            'stretchers' => [
                'endpoints' => [
                    'GET /stretchers' => 'List all stretchers',
                    'GET /cabinets/{id}/stretchers' => 'List stretchers for a cabinet',
                    'POST /cabinets/{id}/stretchers' => 'Create stretcher',
                    'GET /stretchers/{id}' => 'Get a stretcher',
                    'PUT /stretchers/{id}' => 'Update a stretcher',
                    'DELETE /stretchers/{id}' => 'Delete a stretcher',
                ],
            ],
            'faceframes' => [
                'endpoints' => [
                    'GET /faceframes' => 'List all faceframes',
                    'GET /cabinets/{id}/faceframes' => 'List faceframes for a cabinet',
                    'POST /cabinets/{id}/faceframes' => 'Create faceframe',
                    'GET /faceframes/{id}' => 'Get a faceframe',
                    'PUT /faceframes/{id}' => 'Update a faceframe',
                    'DELETE /faceframes/{id}' => 'Delete a faceframe',
                ],
            ],
            'partners' => [
                'endpoints' => [
                    'GET /partners' => 'List all partners',
                    'POST /partners' => 'Create a partner',
                    'GET /partners/{id}' => 'Get a partner',
                    'PUT /partners/{id}' => 'Update a partner',
                    'DELETE /partners/{id}' => 'Delete a partner',
                ],
                'filterable' => ['id', 'sub_type', 'is_company', 'is_active'],
                'searchable' => ['name', 'email', 'phone'],
                'includable' => ['projects', 'contacts', 'company'],
            ],
            'employees' => [
                'endpoints' => [
                    'GET /employees' => 'List all employees',
                    'POST /employees' => 'Create an employee',
                    'GET /employees/{id}' => 'Get an employee',
                    'PUT /employees/{id}' => 'Update an employee',
                    'DELETE /employees/{id}' => 'Delete an employee',
                ],
                'filterable' => ['id', 'department_id', 'user_id', 'is_active'],
                'searchable' => ['name', 'work_email', 'job_title'],
                'includable' => ['department', 'user', 'calendar'],
            ],
            'products' => [
                'endpoints' => [
                    'GET /products' => 'List all products',
                    'POST /products' => 'Create a product',
                    'GET /products/{id}' => 'Get a product',
                    'PUT /products/{id}' => 'Update a product',
                    'DELETE /products/{id}' => 'Delete a product',
                ],
                'filterable' => ['id', 'type', 'category_id', 'is_active'],
                'searchable' => ['name', 'sku', 'description'],
            ],
            'batch' => [
                'endpoints' => [
                    'POST /batch/{resource}' => 'Batch create/update/delete',
                ],
                'supported_resources' => ['projects', 'rooms', 'cabinets', 'drawers', 'doors', 'partners', 'employees', 'products'],
                'operations' => ['create', 'update', 'delete'],
            ],
            'webhooks' => [
                'endpoints' => [
                    'GET /webhooks' => 'List your subscriptions',
                    'POST /webhooks/subscribe' => 'Create a subscription',
                    'GET /webhooks/{id}' => 'Get a subscription',
                    'PUT /webhooks/{id}' => 'Update a subscription',
                    'DELETE /webhooks/{id}' => 'Delete a subscription',
                    'GET /webhooks/events' => 'List available events',
                    'POST /webhooks/{id}/test' => 'Send test webhook',
                    'GET /webhooks/{id}/deliveries' => 'View delivery history',
                ],
                'events' => [
                    'project.*', 'room.*', 'cabinet.*', 'cabinet_run.*',
                    'drawer.*', 'door.*', 'task.*', 'employee.*',
                    'product.*', 'partner.*',
                ],
            ],
        ];
    }
}
