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
            \Log::info('FooterApiController::getProject called', ['projectId' => $projectId]);

            $project = Project::with(['partner', 'tags'])->findOrFail($projectId);

            \Log::info('Project loaded successfully', ['project_id' => $project->id]);

            $response = [
                'id' => $project->id,
                'name' => $project->name,
                'partner_id' => $project->partner_id,
                'customer_id' => $project->partner_id, // Alias for compatibility
                'partner_name' => $project->partner?->name,
                'customer_name' => $project->partner?->name, // Alias for compatibility
                'location' => $project->location,
                'status' => $project->status,
                'tags' => $project->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'type' => $tag->type,
                    'color' => $tag->color,
                ])->toArray(),
            ];

            \Log::info('Returning project response', ['response' => $response]);

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('FooterApiController::getProject error', [
                'projectId' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Project not found',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
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

    /**
     * Get project list with health metrics for project selector
     * ADHD-Friendly: Returns filtered, prioritized list
     */
    public function getProjectList(Request $request)
    {
        try {
            // Get active projects only (not archived/completed)
            $projects = Project::with(['partner'])
                ->whereIn('status', ['active', 'in_progress', 'planning', 'on_hold'])
                ->orderBy('updated_at', 'desc')
                ->get();

            $projectList = $projects->map(function ($project) {
                // Format project address
                $address = '';
                if ($project->project_address) {
                    $addressData = is_string($project->project_address)
                        ? json_decode($project->project_address, true)
                        : $project->project_address;

                    if ($addressData) {
                        $parts = [];
                        if (!empty($addressData['street1'])) $parts[] = $addressData['street1'];
                        if (!empty($addressData['city'])) $parts[] = $addressData['city'];
                        if (!empty($addressData['state'])) $parts[] = $addressData['state'];
                        $address = implode(', ', $parts);
                    }
                }

                // Calculate health indicators
                // TODO: Implement actual budget/schedule variance calculations
                // For now, use placeholder logic based on project age and status
                $isCritical = false;
                $statusIndicator = 'on_track';
                $budgetVariance = null;
                $scheduleVariance = null;

                // Simple heuristic: mark as critical if status is 'on_hold'
                if ($project->status === 'on_hold') {
                    $isCritical = true;
                    $statusIndicator = 'off_track';
                    $scheduleVariance = 'On Hold';
                }

                // Check if project has desired completion date in the past (overdue)
                if ($project->desired_completion_date && $project->desired_completion_date < now()) {
                    $isCritical = true;
                    $statusIndicator = 'off_track';
                    $daysOverdue = now()->diffInDays($project->desired_completion_date);
                    $scheduleVariance = $daysOverdue . ' days overdue';
                }

                return [
                    'id' => $project->id,
                    'project_number' => $project->project_number ?? 'P-' . str_pad($project->id, 4, '0', STR_PAD_LEFT),
                    'customer_name' => $project->partner?->name ?? 'Unknown Customer',
                    'address' => $address ?: '—',
                    'status_indicator' => $statusIndicator,
                    'is_critical' => $isCritical,
                    'budget_variance' => $budgetVariance,
                    'schedule_variance' => $scheduleVariance,
                    'project_type' => $project->project_type,
                    'estimated_linear_feet' => $project->estimated_linear_feet,
                ];
            });

            return response()->json([
                'projects' => $projectList->values(),
            ]);
        } catch (\Exception $e) {
            \Log::error('FooterApiController::getProjectList error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load projects',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
