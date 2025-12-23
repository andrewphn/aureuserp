<?php

namespace Webkul\Lead\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Lead\Enums\LeadStatus;
use Webkul\Lead\Models\Lead;

class SyncLeadToHubSpotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Lead $lead
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if HubSpot service is available
        if (! class_exists(\Webkul\TcsHubSpot\Services\HubSpotService::class)) {
            Log::info('HubSpot service not available, skipping sync', [
                'lead_id' => $this->lead->id,
            ]);

            return;
        }

        try {
            $hubspotService = app(\Webkul\TcsHubSpot\Services\HubSpotService::class);

            if (! $hubspotService->isInitialized()) {
                Log::info('HubSpot not configured, skipping sync', [
                    'lead_id' => $this->lead->id,
                ]);

                return;
            }

            // Prepare contact properties from lead
            $contactProperties = $this->prepareContactProperties();

            // Create or update contact
            $contactResult = $hubspotService->createOrUpdateContact($contactProperties);

            if ($contactResult && isset($contactResult['id'])) {
                $this->lead->update([
                    'hubspot_contact_id' => $contactResult['id'],
                ]);

                // Create deal if contact was created successfully
                if (! $this->lead->hubspot_deal_id) {
                    $dealResult = $hubspotService->createDeal(
                        $contactResult['id'],
                        $this->prepareDealData()
                    );

                    if ($dealResult && isset($dealResult['id'])) {
                        $this->lead->update([
                            'hubspot_deal_id' => $dealResult['id'],
                        ]);
                    }
                } elseif ($this->lead->status === LeadStatus::CONVERTED) {
                    // Update deal stage if lead is converted
                    $hubspotService->updateDealStage(
                        $this->lead->hubspot_deal_id,
                        'discovery' // Move to discovery stage
                    );
                }

                Log::info('Lead synced to HubSpot successfully', [
                    'lead_id' => $this->lead->id,
                    'hubspot_contact_id' => $contactResult['id'],
                    'hubspot_deal_id' => $this->lead->hubspot_deal_id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to sync lead to HubSpot', [
                'lead_id' => $this->lead->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Prepare contact properties for HubSpot
     */
    protected function prepareContactProperties(): array
    {
        return [
            'firstname' => $this->lead->first_name,
            'lastname' => $this->lead->last_name,
            'email' => $this->lead->email,
            'phone' => $this->lead->phone,
            'company' => $this->lead->company_name,
            'hs_lead_status' => $this->mapLeadStatus(),
            'leadsource' => $this->lead->source?->value ?? 'website',
            'address' => $this->lead->street1,
            'city' => $this->lead->city,
            'state' => $this->lead->state,
            'zip' => $this->lead->zip,
            'country' => $this->lead->country,
            // Custom properties
            'project_type' => $this->lead->project_type,
            'budget_range' => $this->lead->budget_range,
            'design_style' => $this->lead->design_style,
            'wood_species' => $this->lead->wood_species,
            'preferred_contact_method' => $this->lead->preferred_contact_method,
        ];
    }

    /**
     * Prepare deal data for HubSpot
     */
    protected function prepareDealData(): array
    {
        return [
            'dealname' => $this->generateDealName(),
            'amount' => $this->estimateBudgetValue(),
            'pipeline' => 'default',
            'dealstage' => 'initialcontact',
            'description' => $this->lead->message ?? $this->lead->project_description,
        ];
    }

    /**
     * Generate deal name following TCS convention
     */
    protected function generateDealName(): string
    {
        $projectTypeCode = $this->getProjectTypeCode();
        $lastName = $this->lead->last_name ?? 'Unknown';

        return "TCS-{$projectTypeCode}-{$lastName}";
    }

    /**
     * Get project type code for deal naming
     */
    protected function getProjectTypeCode(): string
    {
        $projectType = strtolower($this->lead->project_type ?? '');

        return match (true) {
            str_contains($projectType, 'kitchen') => 'KT',
            str_contains($projectType, 'bathroom') => 'BT',
            str_contains($projectType, 'built-in') => 'BI',
            str_contains($projectType, 'closet') => 'CL',
            str_contains($projectType, 'office') => 'OF',
            str_contains($projectType, 'commercial') => 'CM',
            str_contains($projectType, 'furniture') => 'FN',
            default => 'GN', // General
        };
    }

    /**
     * Estimate budget value from range
     */
    protected function estimateBudgetValue(): int
    {
        return match ($this->lead->budget_range) {
            'under_10k' => 7500,
            '10k_25k' => 17500,
            '25k_50k' => 37500,
            '50k_100k' => 75000,
            'over_100k' => 125000,
            default => 0,
        };
    }

    /**
     * Map lead status to HubSpot status
     */
    protected function mapLeadStatus(): string
    {
        return match ($this->lead->status) {
            LeadStatus::NEW => 'NEW',
            LeadStatus::CONTACTED => 'IN_PROGRESS',
            LeadStatus::QUALIFIED => 'OPEN',
            LeadStatus::CONVERTED => 'CONVERTED',
            LeadStatus::DISQUALIFIED => 'UNQUALIFIED',
            default => 'NEW',
        };
    }
}
