<?php

namespace Webkul\TcsHubspot\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubSpotWorkflowService
{
    private string $apiKey;

    private string $baseUrl = 'https://api.hubapi.com';

    private bool $isInitialized = false;

    public function __construct()
    {
        $this->apiKey = config('services.hubspot.api_key', '');
        $this->isInitialized = ! empty($this->apiKey);
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    public function enrollDealInWorkflow(string $dealId, string $workflowId): bool
    {
        if (! $this->isInitialized()) {
            Log::error('HubSpot Workflow Service not initialized');

            return false;
        }

        try {
            $endpoint = "{$this->baseUrl}/automation/v4/workflows/{$workflowId}/enrollments/batch";

            $payload = [
                'inputs' => [
                    [
                        'id' => $dealId,
                        'type' => 'DEAL',
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("Successfully enrolled deal {$dealId} in workflow {$workflowId}");

                return true;
            } else {
                Log::error("Failed to enroll deal in workflow. Status: {$response->status()}, Response: {$response->body()}");

                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error enrolling deal in workflow: {$e->getMessage()}");

            return false;
        }
    }

    public function moveDealToStage(string $dealId, string $stageName): bool
    {
        if (! $this->isInitialized()) {
            Log::error('HubSpot Workflow Service not initialized');

            return false;
        }

        try {
            $endpoint = "{$this->baseUrl}/crm/v3/objects/deals/{$dealId}";

            $stageMapping = [
                'initialcontact' => 'initialcontact',
                'questionnairecompleted' => 'questionnairecompleted',
                'discovery' => 'discovery',
                'proposal' => 'proposal',
                'inproduction' => 'inproduction',
                'final' => 'final',
                'complete' => 'complete',
                'lost' => 'lost',
            ];

            $actualStageName = $stageMapping[$stageName] ?? $stageName;

            Log::info("Moving deal to stage: requested=$stageName, actual=$actualStageName");

            $payload = [
                'properties' => [
                    'dealstage' => $actualStageName,
                    'hs_pipeline_stage' => $actualStageName,
                    'questionnaire_status' => ($stageName === 'questionnairecompleted') ? 'COMPLETE' : null,
                    'questionnaire_completed_date' => ($stageName === 'questionnairecompleted') ? (time() * 1000) : null,
                    'dealstage_timestamp' => time() * 1000,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->patch($endpoint, $payload);

            if ($response->successful()) {
                Log::info("Successfully moved deal {$dealId} to stage {$actualStageName}");

                return true;
            } else {
                Log::error("Failed to move deal to stage. Status: {$response->status()}, Response: {$response->body()}");

                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error moving deal to stage: {$e->getMessage()}");

            return false;
        }
    }

    public function getWorkflows(): array
    {
        if (! $this->isInitialized()) {
            Log::error('HubSpot Workflow Service not initialized');

            return [];
        }

        try {
            $endpoint = "{$this->baseUrl}/automation/v4/workflows";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->get($endpoint);

            if ($response->successful()) {
                return $response->json()['workflows'] ?? [];
            } else {
                Log::error("Failed to get workflows. Status: {$response->status()}, Response: {$response->body()}");

                return [];
            }
        } catch (\Exception $e) {
            Log::error("Error getting workflows: {$e->getMessage()}");

            return [];
        }
    }
}
