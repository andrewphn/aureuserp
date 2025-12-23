<?php

namespace Webkul\TcsHubspot\Console\Commands;

use Illuminate\Console\Command;
use Webkul\TcsHubspot\Services\HubSpotWorkflowService;

class HubSpotMoveToQuestionnaire extends Command
{
    protected $signature = 'hubspot:move-to-questionnaire {deal_id}';

    protected $description = 'Move a HubSpot deal to the Questionnaire Completed stage';

    public function handle(HubSpotWorkflowService $workflowService): int
    {
        $dealId = $this->argument('deal_id');

        if (! $workflowService->isInitialized()) {
            $this->error('HubSpot Workflow Service is not initialized. Check your API key configuration.');

            return Command::FAILURE;
        }

        $this->info("Moving deal {$dealId} to Questionnaire Completed stage...");

        $success = $workflowService->moveDealToStage($dealId, 'questionnairecompleted');

        if ($success) {
            $this->info("Successfully moved deal {$dealId} to Questionnaire Completed stage.");

            return Command::SUCCESS;
        }

        $this->error("Failed to move deal {$dealId}. Check the logs for details.");

        return Command::FAILURE;
    }
}
