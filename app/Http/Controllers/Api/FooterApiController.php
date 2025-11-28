<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FooterFieldRegistry;
use App\Services\FooterPreferenceService;
use Illuminate\Http\Request;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;

/**
 * Footer Api Controller controller
 *
 */
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
     * Get full project details for form auto-population and footer display
     */
    public function getProject($projectId)
    {
        try {
            \Log::info('FooterApiController::getProject called', ['projectId' => $projectId]);

            $project = Project::with(['partner', 'tags'])->findOrFail($projectId);

            \Log::info('Project loaded successfully', ['project_id' => $project->id]);

            // Format project address if it exists
            $projectAddress = null;
            if ($project->project_address) {
                $addressData = is_string($project->project_address)
                    ? json_decode($project->project_address, true)
                    : $project->project_address;

                if ($addressData) {
                    $parts = [];
                    if (!empty($addressData['street1'])) $parts[] = $addressData['street1'];
                    if (!empty($addressData['city'])) $parts[] = $addressData['city'];
                    if (!empty($addressData['state'])) $parts[] = $addressData['state'];
                    $projectAddress = implode(', ', $parts);
                }
            }

            $response = [
                // Basic info
                'id' => $project->id,
                'name' => $project->name,
                'partner_id' => $project->partner_id,
                'customer_id' => $project->partner_id, // Alias for compatibility
                'partner_name' => $project->partner?->name,
                'customer_name' => $project->partner?->name, // Alias for compatibility
                '_customerName' => $project->partner?->name, // Footer-specific alias

                // Project details (actual database columns)
                'project_number' => $project->project_number,
                'project_type' => $project->project_type,
                'project_type_other' => $project->project_type_other,
                'description' => $project->description,
                'visibility' => $project->visibility,
                'color' => $project->color,

                // Dates
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'desired_completion_date' => $project->desired_completion_date,

                // Estimates
                'allocated_hours' => $project->allocated_hours,
                'estimated_linear_feet' => $project->estimated_linear_feet,

                // Address
                'project_address' => $projectAddress,
                'use_customer_address' => $project->use_customer_address,

                // Settings
                'allow_timesheets' => $project->allow_timesheets,
                'allow_milestones' => $project->allow_milestones,
                'allow_task_dependencies' => $project->allow_task_dependencies,
                'is_active' => $project->is_active,

                // Relations
                'stage_id' => $project->stage_id,
                'company_id' => $project->company_id,
                'user_id' => $project->user_id,
                'creator_id' => $project->creator_id,

                // Tags
                'tags' => $project->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'type' => $tag->type,
                    'color' => $tag->color,
                ])->toArray(),

                // Timestamps
                'created_at' => $project->created_at?->toISOString(),
                'updated_at' => $project->updated_at?->toISOString(),
            ];

            \Log::info('Returning project response', ['response_keys' => array_keys($response)]);

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
     * Returns filtered, prioritized list
     */
    public function getProjectList(Request $request)
    {
        try {
            // Get active projects only (not deleted)
            $projects = Project::with(['partner'])
                ->where('is_active', true)
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
                // For now, use placeholder logic based on project age and completion date
                $isCritical = false;
                $statusIndicator = 'on_track';
                $budgetVariance = null;
                $scheduleVariance = null;

                // Check if project has desired completion date in the past (overdue)
                if ($project->desired_completion_date) {
                    $completionDate = \Carbon\Carbon::parse($project->desired_completion_date);
                    if ($completionDate < now()) {
                        $isCritical = true;
                        $statusIndicator = 'off_track';
                        $daysOverdue = now()->diffInDays($completionDate);
                        $scheduleVariance = $daysOverdue . ' days overdue';
                    } elseif ($completionDate->diffInDays(now()) <= 7) {
                        // Due within 7 days
                        $statusIndicator = 'at_risk';
                        $scheduleVariance = 'Due in ' . $completionDate->diffInDays(now()) . ' days';
                    }
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

    /**
     * Get footer preferences for the authenticated user
     */
    public function getFooterPreferences(Request $request)
    {
        try {
            $service = app(FooterPreferenceService::class);
            $user = $request->user();

            $preferences = $service->getAllUserPreferences($user);

            return response()->json($preferences);
        } catch (\Exception $e) {
            \Log::error('FooterApiController::getFooterPreferences error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load preferences',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save footer preferences for a specific context
     */
    public function saveFooterPreferences(Request $request)
    {
        try {
            $validated = $request->validate([
                'context_type' => 'required|in:project,sale,inventory,production',
                'minimized_fields' => 'required|array',
                'expanded_fields' => 'required|array',
                'field_order' => 'nullable|array'
            ]);

            $service = app(FooterPreferenceService::class);
            $user = $request->user();

            $preference = $service->saveUserPreferences(
                $user,
                $validated['context_type'],
                $validated
            );

            return response()->json([
                'success' => true,
                'preference' => [
                    'context_type' => $preference->context_type,
                    'minimized_fields' => $preference->minimized_fields,
                    'expanded_fields' => $preference->expanded_fields,
                    'field_order' => $preference->field_order,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('FooterApiController::saveFooterPreferences error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to save preferences',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available fields for a context type
     */
    public function getAvailableFields(Request $request, string $contextType)
    {
        try {
            $registry = app(FooterFieldRegistry::class);

            $fields = $registry->getAvailableFields($contextType);

            return response()->json([
                'context_type' => $contextType,
                'fields' => $fields,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid context type',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Apply a persona template to user
     */
    public function applyPersonaTemplate(Request $request, string $persona)
    {
        try {
            $validPersonas = ['owner', 'project_manager', 'sales', 'inventory'];

            if (!in_array($persona, $validPersonas)) {
                return response()->json([
                    'error' => 'Invalid persona',
                    'valid_personas' => $validPersonas,
                ], 400);
            }

            $service = app(FooterPreferenceService::class);
            $user = $request->user();

            $appliedContexts = $service->applyPersonaTemplate($user, $persona);

            return response()->json([
                'success' => true,
                'persona' => $persona,
                'applied_contexts' => $appliedContexts,
                'message' => "Applied {$persona} template to " . count($appliedContexts) . " contexts"
            ]);
        } catch (\Exception $e) {
            \Log::error('FooterApiController::applyPersonaTemplate error', [
                'persona' => $persona,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to apply persona template',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset preferences to defaults for a context
     */
    public function resetToDefaults(Request $request, string $contextType)
    {
        try {
            $service = app(FooterPreferenceService::class);
            $user = $request->user();

            $preference = $service->resetToDefaults($user, $contextType);

            return response()->json([
                'success' => true,
                'context_type' => $contextType,
                'preference' => [
                    'minimized_fields' => $preference->minimized_fields,
                    'expanded_fields' => $preference->expanded_fields,
                ],
                'message' => "Reset {$contextType} preferences to defaults"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reset preferences',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
