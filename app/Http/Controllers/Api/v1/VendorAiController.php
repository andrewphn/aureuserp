<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\AI\VendorAiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * API Controller for AI-powered vendor lookup
 *
 * Provides endpoints for looking up vendor information using Gemini AI
 * to auto-fill vendor creation forms.
 */
class VendorAiController extends BaseApiController
{
    protected VendorAiService $vendorAiService;

    public function __construct(VendorAiService $vendorAiService)
    {
        $this->vendorAiService = $vendorAiService;
    }

    /**
     * Look up vendor information by name or website URL
     *
     * POST /api/v1/vendor/ai-lookup
     *
     * Request body:
     * {
     *   "type": "name" | "website",
     *   "query": "Home Depot" | "https://homedepot.com"
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "company_name": "The Home Depot, Inc.",
     *     "account_type": "company",
     *     "phone": "1-800-466-3337",
     *     ...
     *   }
     * }
     */
    public function lookup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:name,website',
            'query' => 'required|string|min:2|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $type = $request->input('type');
        $query = $request->input('query');

        $this->logActivity('vendor_ai_lookup', [
            'type' => $type,
            'query' => $query,
        ]);

        try {
            if ($type === 'name') {
                $result = $this->vendorAiService->lookupByName($query);
            } else {
                $result = $this->vendorAiService->lookupByWebsite($query);
            }

            if (!$result['success']) {
                return $this->error(
                    $result['error'] ?? 'Could not find vendor information',
                    null,
                    404
                );
            }

            return $this->success($result['data'], 'Vendor information retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('AI lookup failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Suggest industry for a vendor based on name and description
     *
     * POST /api/v1/vendor/suggest-industry
     *
     * Request body:
     * {
     *   "name": "Home Depot",
     *   "description": "Building materials and home improvement retailer"
     * }
     */
    public function suggestIndustry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $name = $request->input('name');
        $description = $request->input('description');

        try {
            $industryId = $this->vendorAiService->suggestIndustry($name, $description);

            if ($industryId === null) {
                return $this->success([
                    'industry_id' => null,
                    'message' => 'Could not determine industry category',
                ], 'No industry suggestion available');
            }

            return $this->success([
                'industry_id' => $industryId,
            ], 'Industry suggestion retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Industry suggestion failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Clear cache for a vendor lookup
     *
     * DELETE /api/v1/vendor/ai-cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:name,website',
            'query' => 'required|string|min:2|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $type = $request->input('type');
        $query = $request->input('query');

        try {
            $this->vendorAiService->clearCache($type, $query);

            return $this->success(null, 'Cache cleared successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to clear cache: ' . $e->getMessage(), null, 500);
        }
    }
}
