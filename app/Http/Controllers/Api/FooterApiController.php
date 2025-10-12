<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;

class FooterApiController extends Controller
{
    /**
     * Get partner/customer information
     */
    public function getPartner($partnerId)
    {
        try {
            $partner = Partner::findOrFail($partnerId);

            return response()->json([
                'id' => $partner->id,
                'name' => $partner->name,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Partner not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get full project details for form auto-population
     */
    public function getProject($projectId)
    {
        try {
            $project = Project::with(['customer', 'tags'])->findOrFail($projectId);

            return response()->json([
                'id' => $project->id,
                'name' => $project->name,
                'customer_id' => $project->customer_id,
                'customer_name' => $project->customer?->name,
                'location' => $project->location,
                'status' => $project->status,
                'tags' => $project->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'type' => $tag->type,
                    'color' => $tag->color,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Project not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get project tags
     */
    public function getProjectTags($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);

            return response()->json(
                $project->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'type' => $tag->type,
                    'color' => $tag->color,
                ])
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Project not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get production estimate
     */
    public function getProductionEstimate(Request $request)
    {
        $linearFeet = $request->input('linear_feet');
        $companyId = $request->input('company_id');

        if (!$linearFeet || !$companyId) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        // TODO: Implement ProductionEstimatorService
        // For now, return basic estimate
        $estimate = [
            'hours' => $linearFeet * 2.5,
            'days' => ($linearFeet * 2.5) / 8,
            'weeks' => (($linearFeet * 2.5) / 8) / 5,
            'months' => ((($linearFeet * 2.5) / 8) / 5) / 4,
        ];

        return response()->json($estimate);
    }
}
