<?php

namespace Webkul\Lead\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Lead\Enums\LeadStatus;
use Webkul\Lead\Jobs\SyncLeadToHubSpotJob;
use Webkul\Lead\Models\Lead;
use Webkul\Partner\Enums\AccountType;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectAddress;
use Webkul\Project\Models\ProjectStage;

/**
 * Service for converting leads to Partners and Projects
 */
class LeadConversionService
{
    /**
     * Convert a lead to a Partner and Project
     *
     * @throws \Exception
     */
    public function convert(Lead $lead): array
    {
        if (! $lead->canConvert()) {
            throw new \Exception('This lead cannot be converted. It may already be converted or disqualified.');
        }

        return DB::transaction(function () use ($lead) {
            // Step 1: Find or create Partner
            $partner = $this->findOrCreatePartner($lead);

            // Step 2: Create Project
            $project = $this->createProject($lead, $partner);

            // Step 3: Create Project Address if available
            if ($this->hasAddress($lead)) {
                $this->createProjectAddress($lead, $project);
            }

            // Step 4: Transfer media files from lead to project
            if ($lead->hasMedia()) {
                $this->transferMediaToProject($lead, $project);
            }

            // Step 5: Update lead status
            $lead->update([
                'status' => LeadStatus::CONVERTED,
                'partner_id' => $partner->id,
                'project_id' => $project->id,
                'converted_at' => now(),
            ]);

            Log::info('Lead converted successfully', [
                'lead_id' => $lead->id,
                'partner_id' => $partner->id,
                'project_id' => $project->id,
            ]);

            // Dispatch HubSpot sync to update deal stage
            SyncLeadToHubSpotJob::dispatch($lead);

            return [
                'lead' => $lead->fresh(),
                'partner' => $partner,
                'project' => $project,
            ];
        });
    }

    /**
     * Find existing partner by email or create new one
     */
    protected function findOrCreatePartner(Lead $lead): Partner
    {
        // Try to find existing partner by email
        if ($lead->email) {
            $existingPartner = Partner::where('email', $lead->email)->first();
            if ($existingPartner) {
                Log::info('Found existing partner for lead', [
                    'lead_id' => $lead->id,
                    'partner_id' => $existingPartner->id,
                ]);

                return $existingPartner;
            }
        }

        // Create new partner
        $partner = Partner::create([
            'account_type' => $lead->company_name ? AccountType::COMPANY : AccountType::INDIVIDUAL,
            'sub_type' => 'customer',
            'name' => $lead->company_name ?: $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'street1' => $lead->street1,
            'street2' => $lead->street2,
            'city' => $lead->city,
            'zip' => $lead->zip,
            'creator_id' => Auth::id(),
            'user_id' => $lead->assigned_user_id ?? Auth::id(),
        ]);

        Log::info('Created new partner from lead', [
            'lead_id' => $lead->id,
            'partner_id' => $partner->id,
        ]);

        return $partner;
    }

    /**
     * Create project from lead
     */
    protected function createProject(Lead $lead, Partner $partner): Project
    {
        // Get the "To Do" / Inbox stage
        $inboxStage = ProjectStage::where('stage_key', 'todo')
            ->orWhere('name', 'like', '%To Do%')
            ->orWhere('name', 'like', '%Inbox%')
            ->first();

        if (! $inboxStage) {
            // Fallback to first stage
            $inboxStage = ProjectStage::orderBy('sort')->first();
        }

        // Generate project name
        $projectName = $this->generateProjectName($lead, $partner);

        $project = Project::create([
            'name' => $projectName,
            'partner_id' => $partner->id,
            'stage_id' => $inboxStage?->id,
            'lead_source' => $lead->source?->value ?? 'website',
            'project_type' => $lead->project_type,
            'budget_range' => $lead->budget_range,
            'description' => $this->buildProjectDescription($lead),
            // Map timeline dates
            'start_date' => $lead->timeline_start_date,
            'desired_completion_date' => $lead->timeline_completion_date,
            'user_id' => $lead->assigned_user_id ?? Auth::id(),
            'creator_id' => Auth::id(),
            'company_id' => $lead->company_id ?? Auth::user()?->default_company_id,
            'is_active' => true,
            'is_converted' => false, // Start as draft
        ]);

        Log::info('Created project from lead', [
            'lead_id' => $lead->id,
            'project_id' => $project->id,
        ]);

        return $project;
    }

    /**
     * Generate a descriptive project name
     */
    protected function generateProjectName(Lead $lead, Partner $partner): string
    {
        $parts = [];

        // Add project type if available
        if ($lead->project_type) {
            // Take first project type if multiple
            $projectType = explode(',', $lead->project_type)[0];
            $parts[] = trim($projectType);
        }

        // Add customer name
        $parts[] = 'for ' . ($partner->name ?? $lead->full_name);

        // Add city if available
        if ($lead->city) {
            $parts[] = '- ' . $lead->city;
        }

        return implode(' ', $parts);
    }

    /**
     * Build project description from lead data
     */
    protected function buildProjectDescription(Lead $lead): string
    {
        $description = [];

        // Primary inquiry message
        if ($lead->message) {
            $description[] = "## Original Inquiry\n{$lead->message}";
        }

        // Project details section
        $projectDetails = [];
        if ($lead->project_description) {
            $projectDetails[] = "**Project Description:**\n{$lead->project_description}";
        }
        if ($lead->project_phase) {
            $projectDetails[] = "**Project Phase:** {$lead->project_phase}";
        }
        if ($lead->additional_information) {
            $projectDetails[] = "**Additional Information:**\n{$lead->additional_information}";
        }
        if (! empty($projectDetails)) {
            $description[] = "## Project Details\n" . implode("\n\n", $projectDetails);
        }

        // Design preferences section
        $designPrefs = [];
        if ($lead->design_style) {
            $style = $lead->design_style;
            if ($lead->design_style_other) {
                $style .= " ({$lead->design_style_other})";
            }
            $designPrefs[] = "**Design Style:** {$style}";
        }
        if ($lead->wood_species) {
            $designPrefs[] = "**Wood Species:** {$lead->wood_species}";
        }
        if ($lead->finish_choices && is_array($lead->finish_choices)) {
            $designPrefs[] = "**Finish Preferences:** " . implode(', ', $lead->finish_choices);
        }
        if (! empty($designPrefs)) {
            $description[] = "## Design Preferences\n" . implode("\n", $designPrefs);
        }

        // Budget and timeline section
        $budgetTimeline = [];
        if ($lead->budget_range) {
            $budgetLabel = match ($lead->budget_range) {
                'under_10k' => 'Under $10,000',
                '10k_25k' => '$10,000 - $25,000',
                '25k_50k' => '$25,000 - $50,000',
                '50k_100k' => '$50,000 - $100,000',
                'over_100k' => 'Over $100,000',
                'unsure' => 'Not Sure',
                default => $lead->budget_range,
            };
            $budgetTimeline[] = "**Budget Range:** {$budgetLabel}";
        }
        if ($lead->timeline_start_date || $lead->timeline_completion_date) {
            $timeline = '';
            if ($lead->timeline_start_date) {
                $timeline .= "Start: " . $lead->timeline_start_date->format('M d, Y');
            }
            if ($lead->timeline_completion_date) {
                $timeline .= ($timeline ? ' | ' : '') . "Complete by: " . $lead->timeline_completion_date->format('M d, Y');
            }
            $budgetTimeline[] = "**Timeline:** {$timeline}";
        } elseif ($lead->timeline) {
            $budgetTimeline[] = "**Timeline:** {$lead->timeline}";
        }
        if (! empty($budgetTimeline)) {
            $description[] = "## Budget & Timeline\n" . implode("\n", $budgetTimeline);
        }

        // Contact preferences
        if ($lead->preferred_contact_method) {
            $description[] = "## Contact Preference\n**Preferred Method:** " . ucfirst($lead->preferred_contact_method);
        }

        // Lead source info
        $sourceInfo = [];
        if ($lead->source) {
            $sourceInfo[] = "**Lead Source:** " . $lead->source->getLabel();
        }
        if ($lead->referral_source_other) {
            $sourceInfo[] = "**Referral Details:** {$lead->referral_source_other}";
        }
        if ($lead->lead_source_detail) {
            $sourceInfo[] = "**Previous Experience:** {$lead->lead_source_detail}";
        }
        if (! empty($sourceInfo)) {
            $description[] = "## Lead Source\n" . implode("\n", $sourceInfo);
        }

        // Footer
        $description[] = "---\n*Converted from Lead #{$lead->id} on " . now()->format('M d, Y') . '*';

        return implode("\n\n", $description);
    }

    /**
     * Check if lead has address information
     */
    protected function hasAddress(Lead $lead): bool
    {
        return ! empty($lead->street1) || ! empty($lead->city);
    }

    /**
     * Create project address from lead
     */
    protected function createProjectAddress(Lead $lead, Project $project): ProjectAddress
    {
        return ProjectAddress::create([
            'project_id' => $project->id,
            'name' => 'Project Site',
            'street1' => $lead->street1,
            'street2' => $lead->street2,
            'city' => $lead->city,
            'state' => $lead->state,
            'zip' => $lead->zip,
            'country' => $lead->country ?? 'United States',
            'notes' => $lead->project_address_notes,
            'is_primary' => true,
        ]);
    }

    /**
     * Transfer media files from lead to project
     */
    protected function transferMediaToProject(Lead $lead, Project $project): void
    {
        // Copy inspiration images
        foreach ($lead->getMedia('inspiration_images') as $media) {
            $media->copy($project, 'inspiration_images');
        }

        // Copy technical drawings
        foreach ($lead->getMedia('technical_drawings') as $media) {
            $media->copy($project, 'technical_drawings');
        }

        // Copy project documents
        foreach ($lead->getMedia('project_documents') as $media) {
            $media->copy($project, 'project_documents');
        }
    }
}
