<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class BaseApiController extends Controller
{
    /**
     * Success response method
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    /**
     * Error response method
     */
    protected function error(string $message = 'Error', $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        // Log errors for monitoring
        Log::channel('api')->error('API Error', [
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
            'request' => request()->all(),
            'user_id' => auth()->id(),
        ]);

        return response()->json($response, $code);
    }

    /**
     * Validation error response
     */
    protected function validationError($errors): JsonResponse
    {
        return $this->error('Validation failed', $errors, 422);
    }

    /**
     * Not found response
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, null, 404);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, null, 401);
    }

    /**
     * Forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, null, 403);
    }

    /**
     * Paginated response
     */
    protected function paginated($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'links' => [
                    'next' => $paginator->nextPageUrl(),
                    'previous' => $paginator->previousPageUrl(),
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission(string $permission): bool
    {
        return auth()->user()->can($permission);
    }

    /**
     * Check multiple permissions (requires all)
     */
    protected function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check multiple permissions (requires any)
     */
    protected function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get request with validation
     */
    protected function getValidatedData(Request $request, array $rules): array
    {
        return $request->validate($rules);
    }

    /**
     * Log API activity
     */
    protected function logActivity(string $action, $data = null): void
    {
        Log::channel('api')->info('API Activity', [
            'action' => $action,
            'data' => $data,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}