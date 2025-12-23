<?php

namespace Webkul\TcsHubspot\Services;

use Carbon\Carbon;
use HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInputForCreate;
use HubSpot\Client\Crm\Deals\Model\SimplePublicObjectInputForCreate as DealSimplePublicObjectInputForCreate;
use HubSpot\Factory;
use Illuminate\Support\Facades\Log;

class HubSpotService
{
    private ?\HubSpot\Discovery\Discovery $hubspot = null;

    private const DEFAULT_COUNTRY = 'United States';

    private const NOT_SPECIFIED = 'Not specified';

    public function __construct()
    {
        try {
            $apiKey = config('services.hubspot.api_key', '');
            $accessToken = config('services.hubspot.access_token', '');
            Log::debug('Attempting HubSpot init...', ['accessToken_set' => ! empty($accessToken), 'apiKey_set' => ! empty($apiKey)]);

            if (! empty($accessToken)) {
                Log::info('Attempting to initialize HubSpot with Access Token.');
                $this->hubspot = Factory::createWithAccessToken($accessToken);
                Log::info('HubSpot client instance CREATED with access token. Testing connection...');
            } elseif (! empty($apiKey)) {
                Log::info('Attempting to initialize HubSpot with API Key.');
                $this->hubspot = Factory::createWithApiKey($apiKey);
                Log::info('HubSpot client instance CREATED with API key. Testing connection...');
            } else {
                Log::warning('No HubSpot API key or access token found in environment variables. HubSpot integration disabled.');
                $this->hubspot = null;

                return;
            }

            if (isset($this->hubspot)) {
                Log::info('Testing HubSpot connection with getPage(1)...');
                $this->hubspot->crm()->contacts()->basicApi()->getPage(1);
                Log::info('HubSpot connection test successful.');
            }
        } catch (\HubSpot\Client\Crm\Contacts\ApiException $e) {
            Log::error('HubSpot API Exception during initialization/test: '.$e->getMessage().' | Response Body: '.$e->getResponseBody(), ['trace' => $e->getTraceAsString()]);
            $this->hubspot = null;
        } catch (\Exception $e) {
            Log::error('Generic Exception during HubSpot initialization: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->hubspot = null;
        }
    }

    public function isInitialized(): bool
    {
        return isset($this->hubspot) && $this->hubspot !== null;
    }

    public function findCustomerByEmail(string $email): array
    {
        $response = [
            'exists' => false,
            'message' => 'Customer not found',
        ];

        if (! $this->isInitialized()) {
            $response['message'] = 'HubSpot client not initialized';

            return $response;
        }

        try {
            $filterGroups = [
                [
                    'filters' => [
                        [
                            'propertyName' => 'email',
                            'operator' => 'EQ',
                            'value' => $email,
                        ],
                    ],
                ],
            ];

            $searchRequest = new PublicObjectSearchRequest;
            $searchRequest->setFilterGroups($filterGroups);
            $searchRequest->setProperties([
                'firstname',
                'lastname',
                'email',
                'phone',
                'company',
                'preferred_communication_method',
                'address',
                'address2',
                'city',
                'state',
                'zip',
                'country',
                'previous_woodworking_experience',
            ]);
            $searchRequest->setLimit(1);

            $contactSearch = $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);

            if (! empty($contactSearch->getResults())) {
                $contact = $contactSearch->getResults()[0];
                $contactProperties = $contact->getProperties();

                $response = [
                    'exists' => true,
                    'message' => 'Customer found',
                    'hubspotId' => $contact->getId(),
                    'customer' => [
                        'firstname' => $contactProperties['firstname'] ?? '',
                        'lastname' => $contactProperties['lastname'] ?? '',
                        'email' => $contactProperties['email'] ?? '',
                        'phone' => $contactProperties['phone'] ?? '',
                        'company' => $contactProperties['company'] ?? '',
                        'contactpreferred' => $contactProperties['preferred_communication_method'] ?? '',
                        'address' => $contactProperties['address'] ?? '',
                        'address2' => $contactProperties['address2'] ?? '',
                        'city' => $contactProperties['city'] ?? '',
                        'state' => $contactProperties['state'] ?? '',
                        'zip' => $contactProperties['zip'] ?? '',
                        'country' => $contactProperties['country'] ?? self::DEFAULT_COUNTRY,
                        'previous_woodworking_experience' => $contactProperties['previous_woodworking_experience'] ?? 'Yes, with TCS Woodworking',
                    ],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error checking customer in HubSpot: '.$e->getMessage());
            $response['message'] = 'Error checking customer. Please provide your details below.';
        }

        return $response;
    }

    public function createOrUpdateContact(array $contactProperties, string $hubspotContactId, string $email)
    {
        try {
            if (! $this->isInitialized()) {
                Log::warning('HubSpot client is not initialized');

                return null;
            }

            if (! empty($hubspotContactId)) {
                return $this->updateExistingContact($hubspotContactId, $contactProperties);
            } else {
                return $this->findOrCreateContact($email, $contactProperties);
            }
        } catch (\Exception $e) {
            Log::error('Error creating or updating contact in HubSpot: '.$e->getMessage());

            return null;
        }
    }

    protected function updateExistingContact(string $contactId, array $properties): string
    {
        $input = new SimplePublicObjectInput;
        $input->setProperties($properties);

        $this->hubspot->crm()->contacts()->basicApi()->update(
            $contactId,
            $input
        );

        return $contactId;
    }

    protected function findOrCreateContact(string $email, array $properties): ?string
    {
        $filterGroups = [
            [
                'filters' => [
                    [
                        'propertyName' => 'email',
                        'operator' => 'EQ',
                        'value' => $email,
                    ],
                ],
            ],
        ];

        $searchRequest = new PublicObjectSearchRequest;
        $searchRequest->setFilterGroups($filterGroups);
        $contactSearch = $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);

        if (! empty($contactSearch->getResults())) {
            $contact = $contactSearch->getResults()[0];

            return $this->updateExistingContact($contact->getId(), $properties);
        } else {
            $input = new SimplePublicObjectInputForCreate;
            $input->setProperties($properties);

            $contact = $this->hubspot->crm()->contacts()->basicApi()->create($input);

            return $contact->getId();
        }
    }

    public function createContactNote(
        string $contactId,
        array $validated,
        array $fileUrls,
        bool $isReturningCustomer,
        array $projectType,
        array $otherProfessionals
    ): bool {
        try {
            if (! $this->isInitialized()) {
                Log::warning('HubSpot client is not initialized - cannot create note');

                return false;
            }

            $noteBody = $this->buildNoteContent(
                $validated,
                $fileUrls,
                $isReturningCustomer,
                $projectType,
                $otherProfessionals
            );

            $noteProperties = [
                'hs_note_body' => $noteBody,
                'hs_timestamp' => time() * 1000,
                'hs_note_body_html' => '<div>'.nl2br($noteBody).'</div>',
            ];

            $input = new SimplePublicObjectInputForCreate;
            $input->setProperties($noteProperties);
            $input->setAssociations([
                'contactIds' => [$contactId],
            ]);

            Log::info('SKIPPED: Created note for contact in HubSpot (temporarily disabled)');

            return true;
        } catch (\Exception $e) {
            Log::error('Error creating contact note in HubSpot: '.$e->getMessage());

            return false;
        }
    }

    protected function buildNoteContent(
        array $validated,
        array $fileUrls,
        bool $isReturningCustomer,
        array $projectType,
        array $otherProfessionals
    ): string {
        $noteBody = "## TCS Woodworking Website Contact Form Submission\n\n";

        if ($isReturningCustomer) {
            $noteBody .= "**RETURNING CUSTOMER**\n\n";
        }

        $noteBody .= "### Contact Information\n";
        $noteBody .= '**Name:** '.$validated['firstname'].' '.$validated['lastname']."\n";
        $noteBody .= '**Email:** '.$validated['email']."\n";
        $noteBody .= '**Phone:** '.$validated['phone']."\n";
        if (! empty($validated['company'])) {
            $noteBody .= '**Company:** '.$validated['company']."\n";
        }
        $noteBody .= '**Preferred Contact Method:** '.($validated['contactpreferred'] ?? self::NOT_SPECIFIED)."\n";
        if (! empty($validated['source'])) {
            $noteBody .= '**Lead Source:** '.implode(', ', $validated['source'])."\n";
        }
        if (! empty($validated['referralsourceother'])) {
            $noteBody .= '**Other Source:** '.$validated['referralsourceother']."\n";
        }
        if (! empty($validated['previous_woodworking_experience'])) {
            $noteBody .= '**Previous Woodworking Experience:** '.$validated['previous_woodworking_experience']."\n";
        }
        $noteBody .= "\n";

        $noteBody .= "### Project Information\n";
        if (! empty($projectType)) {
            $noteBody .= '**Project Type:** '.implode(', ', $projectType)."\n";
        }
        if (! empty($validated['project_description'])) {
            $noteBody .= '**Project Description:** '.$validated['project_description']."\n";
        }
        if (! empty($validated['project_phase'])) {
            $noteBody .= '**Project Phase:** '.$validated['project_phase']."\n";
        }
        if (! empty($otherProfessionals)) {
            $noteBody .= '**Other Professionals Involved:** '.implode(', ', $otherProfessionals)."\n";
        }
        $noteBody .= "\n";

        $noteBody .= "### Design Preferences\n";
        if (! empty($validated['design_style'])) {
            $noteBody .= '**Design Style:** '.implode(', ', $validated['design_style'])."\n";
        }
        if (! empty($validated['design_style_other'])) {
            $noteBody .= '**Other Style:** '.$validated['design_style_other']."\n";
        }
        if (! empty($validated['finish_choices'])) {
            $noteBody .= '**Finish Preferences:** '.implode(', ', $validated['finish_choices'])."\n";
        }
        if (! empty($validated['wood_species'])) {
            $noteBody .= '**Wood Species Interest:** '.$validated['wood_species']."\n";
        }
        $noteBody .= "\n";

        $noteBody .= "### Budget & Timeline\n";
        if (! empty($validated['budget_range'])) {
            $noteBody .= '**Budget Range:** '.$this->formatBudgetRangeForHubSpot($validated['budget_range'])."\n";
        }
        if (! empty($validated['timeline_start_date'])) {
            $noteBody .= '**Desired Start Date:** '.$validated['timeline_start_date']."\n";
        }
        if (! empty($validated['timeline_completion_date'])) {
            $noteBody .= '**Desired Completion Date:** '.$validated['timeline_completion_date']."\n";
        }
        $noteBody .= "\n";

        if (! empty($validated['project_address_street1']) || ! empty($validated['project_address_city'])) {
            $noteBody .= "### Project Location\n";
            if (! empty($validated['project_address_street1'])) {
                $noteBody .= '**Address:** '.$validated['project_address_street1']."\n";
                if (! empty($validated['project_address_street2'])) {
                    $noteBody .= '**Address Line 2:** '.$validated['project_address_street2']."\n";
                }
            }
            if (! empty($validated['project_address_city'])) {
                $city = $validated['project_address_city'];
                $state = ($validated['project_address_state'] === 'NY') ? 'New York' : ($validated['project_address_state'] ?? '');
                $zip = $validated['project_address_zip'] ?? '';
                $noteBody .= '**Location:** '.trim("$city $state $zip")."\n";
            }
            if (! empty($validated['project_address_country'])) {
                $noteBody .= '**Country:** '.$validated['project_address_country']."\n";
            }
            if (! empty($validated['project_address_notes'])) {
                $noteBody .= '**Location Notes:** '.$validated['project_address_notes']."\n";
            }
            $noteBody .= "\n";
        }

        $noteBody .= "### Privacy & Consent\n";
        $noteBody .= '**Processing Consent:** '.(isset($validated['processing_consent']) ? 'Yes' : 'No')."\n";
        $noteBody .= '**Marketing Consent:** '.(isset($validated['communication_consent']) ? 'Yes' : 'No')."\n\n";

        $this->addFileUrlsToNote($noteBody, $fileUrls);

        $noteBody .= "\n---\n";
        $noteBody .= "Submitted via TCS Woodworking Website Contact Form\n";
        $noteBody .= 'Date: '.Carbon::now()->format('Y-m-d H:i:s')."\n";
        $noteBody .= "Questionnaire Status: Completed\n";

        return $noteBody;
    }

    protected function addFileUrlsToNote(string &$noteBody, array $fileUrls): void
    {
        if (empty($fileUrls)) {
            return;
        }

        $noteBody .= "### Uploaded Files\n";

        if (! empty($fileUrls['inspiration_images'])) {
            $noteBody .= "**Inspiration Images:**\n";
            foreach ($fileUrls['inspiration_images'] as $index => $url) {
                $noteBody .= ($index + 1).'. '.$url."\n";
            }
            $noteBody .= "\n";
        }

        if (! empty($fileUrls['technical_drawings'])) {
            $noteBody .= "**Technical Drawings:**\n";
            foreach ($fileUrls['technical_drawings'] as $index => $url) {
                $noteBody .= ($index + 1).'. '.$url."\n";
            }
            $noteBody .= "\n";
        }

        if (! empty($fileUrls['project_documents'])) {
            $noteBody .= "**Project Documents:**\n";
            foreach ($fileUrls['project_documents'] as $index => $url) {
                $noteBody .= ($index + 1).'. '.$url."\n";
            }
        }
    }

    public function formatBudgetRangeForHubSpot(string $budgetRange): string
    {
        $formats = [
            'budget_under_10k' => 'Under $10,000',
            'budget_10k_25k' => '$10,000 - $25,000',
            'budget_25k_50k' => '$25,000 - $50,000',
            'budget_50k_plus' => '$50,000+',
            'budget_unsure' => 'Needs guidance',
        ];

        return $formats[$budgetRange] ?? self::NOT_SPECIFIED;
    }

    public function estimateBudgetAmount(string $budgetRange): int
    {
        $amounts = [
            'budget_under_10k' => 5000,
            'budget_10k_25k' => 15000,
            'budget_25k_50k' => 35000,
            'budget_50k_plus' => 75000,
        ];

        return $amounts[$budgetRange] ?? 0;
    }

    public function prepareContactProperties(array $validated, array $projectType, array $otherProfessionals, bool $isReturningCustomer): array
    {
        $lifecycleStage = $isReturningCustomer ? 'customer' : 'lead';

        return [
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'company' => $validated['company'] ?? '',
            'preferred_communication_method' => $this->mapPreferredCommunicationMethod($validated['contactpreferred'] ?? ''),
            'lead_source' => $this->mapLeadSourceValue($validated['source'] ?? []),
            'referralsourceother' => $validated['referralsourceother'] ?? '',
            'hs_lead_status' => $isReturningCustomer ? 'OPEN_DEAL' : 'NEW',
            'lifecyclestage' => $lifecycleStage,
            'previous_woodworking_experience' => $this->mapPreviousExperience($validated['previous_woodworking_experience'] ?? ''),
            'hs_latest_source' => 'DIRECT_TRAFFIC',
            'hs_latest_source_timestamp' => time() * 1000,
            'hs_analytics_source' => 'DIRECT_TRAFFIC',
            'hs_legal_basis' => isset($validated['processing_consent']) ? 'Legitimate interest – prospect/lead' : 'Not applicable',
            'address' => $validated['project_address_street1'] ?? '',
            'city' => $validated['project_address_city'] ?? '',
            'state' => ($validated['project_address_state'] === 'NY') ? 'New York' : ($validated['project_address_state'] ?? ''),
            'zip' => $validated['project_address_zip'] ?? '',
            'country' => $validated['project_address_country'] ?? self::DEFAULT_COUNTRY,
        ];
    }

    public function createDeal(
        string $contactId,
        array $validated,
        bool $isReturningCustomer,
        array $projectType,
        array $otherProfessionals,
        array $fileUrls
    ): ?string {
        try {
            if (! $this->isInitialized()) {
                Log::warning('HubSpot client is not initialized - cannot create deal');

                return null;
            }

            [$isQuestionnaireComplete, $dealStage, $taskPriority, $taskDueDate, $questionnaire_status, $deal_progress_stage] =
                $this->determineDealStage($validated, $isReturningCustomer);

            $dealName = $this->generateTCSFormattedDealName($validated, $projectType, $isReturningCustomer);

            Log::info("Creating deal: $dealName in stage: $dealStage");

            $dealProperties = $this->prepareDealProperties(
                $dealName,
                $dealStage,
                $isReturningCustomer,
                $validated,
                $projectType,
                $otherProfessionals,
                $questionnaire_status,
                $deal_progress_stage,
                $isQuestionnaireComplete
            );

            $input = new DealSimplePublicObjectInputForCreate;
            $input->setProperties($dealProperties);

            $deal = $this->hubspot->crm()->deals()->basicApi()->create($input);
            $dealId = $deal->getId();

            $this->setupDealAssociations($dealId, $contactId, $fileUrls, $isQuestionnaireComplete, $isReturningCustomer, $taskPriority, $taskDueDate);

            return $dealId;
        } catch (\Exception $e) {
            Log::error('Error creating deal in HubSpot: '.$e->getMessage());

            return null;
        }
    }

    protected function determineDealStage(array $validated, bool $isReturningCustomer): array
    {
        $isQuestionnaireComplete = false;
        $formStep = $validated['form_step'] ?? 1;

        if (! empty($validated['project_description']) && $formStep > 2) {
            $isQuestionnaireComplete = true;
            $dealStage = '1314694843';
            $taskPriority = 'HIGH';
            $taskDueDate = strtotime('+1 day') * 1000;
            $questionnaire_status = 'COMPLETED';
            $deal_progress_stage = 'QUESTIONNAIRE_COMPLETED';
        } else {
            $dealStage = '1314693881';
            $taskPriority = 'MEDIUM';
            $taskDueDate = strtotime('+2 days') * 1000;
            $questionnaire_status = 'PARTIAL';
            $deal_progress_stage = 'INITIAL_CONTACT';
        }

        Log::info('Form completion status: '.($isQuestionnaireComplete ? 'COMPLETE' : 'PARTIAL').' at step '.$formStep);

        return [$isQuestionnaireComplete, $dealStage, $taskPriority, $taskDueDate, $questionnaire_status, $deal_progress_stage];
    }

    protected function prepareDealProperties(
        string $dealName,
        string $dealStage,
        bool $isReturningCustomer,
        array $validated,
        array $projectType,
        array $otherProfessionals,
        string $questionnaire_status,
        string $deal_progress_stage,
        bool $isQuestionnaireComplete
    ): array {
        return [
            'dealname' => $dealName,
            'dealstage' => $dealStage,
            'pipeline' => 'default',
            'amount' => $this->estimateBudgetAmount($validated['budget_range'] ?? self::NOT_SPECIFIED),
            'closedate' => ! empty($validated['timeline_completion_date'])
                ? strtotime($validated['timeline_completion_date']) * 1000
                : strtotime('+3 months') * 1000,
            'hubspot_owner_id' => config('services.hubspot.owner_id'),
            'deal_progress_stage' => $deal_progress_stage,
            'lead_source_detail' => $validated['referralsourceother'] ?? ($validated['source'][0] ?? self::NOT_SPECIFIED),
            'dealtype' => 'newbusiness',
            'project_type' => implode(';', $this->mapProjectType($projectType)),
            'project_description' => ! empty($validated['project_description']) ? $validated['project_description'] : 'Initial contact - follow up required',
            'project_room' => $validated['project_room'] ?? '',
            'project_scope' => $validated['project_scope'] ?? '',
            'design_style' => isset($validated['design_style']) && is_array($validated['design_style']) ? implode(';', $this->mapDesignStyle($validated['design_style'])) : '',
            'wood_species' => $this->mapWoodSpecies($validated['wood_species'] ?? ''),
            'timeline_start_date' => ! empty($validated['timeline_start_date']) ? strtotime($validated['timeline_start_date']) * 1000 : null,
            'timeline_completion_date' => ! empty($validated['timeline_completion_date']) ? strtotime($validated['timeline_completion_date']) * 1000 : null,
            'project_phase' => $this->mapProjectPhase($validated['project_phase'] ?? ''),
            'project_address_street1' => $validated['project_address_street1'] ?? '',
            'project_address_street2' => $validated['project_address_street2'] ?? '',
            'project_address_city' => $validated['project_address_city'] ?? '',
            'project_address_state' => $validated['project_address_state'] ?? '',
            'project_address_zip' => $validated['project_address_zip'] ?? '',
            'project_address_country' => $validated['project_address_country'] ?? self::DEFAULT_COUNTRY,
            'project_address_notes' => $validated['project_address_notes'] ?? '',
            'form_submission_details' => $isQuestionnaireComplete ? 'Complete project questionnaire' : 'Partial contact form submission',
            'form_submission_category' => $isQuestionnaireComplete ? 'QUESTIONNAIRE' : 'CONTACT',
            'other_professionals' => ! empty($otherProfessionals) ? implode(';', $this->mapOtherProfessionals($otherProfessionals)) : '',
            'project_budget' => $this->estimateBudgetAmount($validated['budget_range'] ?? ''),
            'lead_source_category' => $this->mapLeadSourceValue($validated['source'] ?? ['Other']),
            'additional_information' => $validated['additional_information'] ?? '',
        ];
    }

    protected function setupDealAssociations(
        string $dealId,
        string $contactId,
        array $fileUrls,
        bool $isQuestionnaireComplete,
        bool $isReturningCustomer,
        string $taskPriority,
        int $taskDueDate
    ): bool {
        try {
            $this->associateContactWithDeal($dealId, $contactId);
            Log::info('Associated contact with deal in HubSpot');

            if (! empty($fileUrls)) {
                $this->createFileReviewTask($dealId, $fileUrls);
            }

            $this->createFollowUpTask(
                $dealId,
                $contactId,
                $isQuestionnaireComplete,
                $isReturningCustomer,
                $taskPriority,
                $taskDueDate
            );

            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to set up deal associations in HubSpot: '.$e->getMessage());

            return false;
        }
    }

    public function associateContactWithDeal(string $dealId, string $contactId): bool
    {
        if (! $this->isInitialized()) {
            Log::warning('HubSpot client is not initialized - cannot associate contact with deal');

            return false;
        }

        try {
            $this->hubspot->crm()->deals()->associationsApi()->create(
                $dealId,
                'contacts',
                $contactId,
                'deal_to_contact'
            );

            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to associate contact with deal in HubSpot: '.$e->getMessage());

            return false;
        }
    }

    public function createFileReviewTask(string $dealId, array $fileUrls): bool
    {
        if (! $this->isInitialized()) {
            Log::warning('HubSpot client is not initialized - cannot create file review task');

            return false;
        }

        try {
            $taskBody = "**Uploaded files from website form:**\n\n";

            foreach ($fileUrls as $fileType => $files) {
                if (! empty($files)) {
                    $taskBody .= '### '.ucfirst(str_replace('_', ' ', $fileType))."\n";
                    foreach ($files as $file) {
                        $taskBody .= "- [View File]($file)\n";
                    }
                    $taskBody .= "\n";
                }
            }

            $taskProperties = [
                'hs_task_body' => $taskBody,
                'hs_task_subject' => 'Review uploaded files from website form',
                'hs_task_status' => 'NOT_STARTED',
                'hs_task_priority' => 'HIGH',
                'hs_timestamp' => time() * 1000,
                'hs_task_type' => 'REVIEW_FILES',
            ];

            $input = new SimplePublicObjectInputForCreate;
            $input->setProperties($taskProperties);
            $input->setAssociations([
                'dealIds' => [$dealId],
            ]);

            $this->hubspot->crm()->tasks()->basicApi()->create($input);
            Log::info('Created file review task for deal in HubSpot');

            return true;
        } catch (\Exception $e) {
            Log::warning('Could not create file review task in HubSpot: '.$e->getMessage());

            return false;
        }
    }

    public function createFollowUpTask(
        string $dealId,
        string $contactId,
        bool $isQuestionnaireComplete,
        bool $isReturningCustomer,
        string $taskPriority,
        int $taskDueDate
    ): bool {
        if (! $this->isInitialized()) {
            Log::warning('HubSpot client is not initialized - cannot create follow-up task');

            return false;
        }

        try {
            [$taskSubject, $taskBody, $taskType] = $this->determineFollowUpTaskContent(
                $isQuestionnaireComplete,
                $isReturningCustomer
            );

            $taskProperties = [
                'hs_task_body' => $taskBody,
                'hs_task_subject' => $taskSubject,
                'hs_task_status' => 'NOT_STARTED',
                'hs_task_priority' => $taskPriority,
                'hs_timestamp' => time() * 1000,
                'hs_task_type' => $taskType,
                'hs_task_for_object_type' => 'DEAL',
                'hs_task_due_date' => $taskDueDate,
            ];

            $input = new SimplePublicObjectInputForCreate;
            $input->setProperties($taskProperties);
            $input->setAssociations([
                'contactIds' => [$contactId],
                'dealIds' => [$dealId],
            ]);

            $this->hubspot->crm()->tasks()->basicApi()->create($input);

            Log::info('Created follow-up task in HubSpot');

            return true;
        } catch (\Exception $e) {
            Log::warning('Could not create follow-up task in HubSpot: '.$e->getMessage());

            return false;
        }
    }

    protected function determineFollowUpTaskContent(bool $isQuestionnaireComplete, bool $isReturningCustomer): array
    {
        if ($isQuestionnaireComplete) {
            $taskSubject = $isReturningCustomer ?
                'QUESTIONNAIRE COMPLETED: RETURNING CUSTOMER - Follow Up Required' :
                'QUESTIONNAIRE COMPLETED: Review and Schedule Call';

            $taskBody = $isReturningCustomer ?
                'A returning customer has submitted a completed questionnaire with new project details. Please review their submission and reach out to discuss their requirements. This lead should be prioritized as they have existing history with us. Previous project records should be referenced during your call.' :
                'Customer has completed the project questionnaire. Please review their detailed requirements and schedule a discovery call to qualify the lead further. Their information is now in the Questionnaire Completed stage.';

            $taskType = 'CALL';
        } else {
            $taskSubject = $isReturningCustomer ?
                'INITIAL CONTACT: RETURNING CUSTOMER - Gather More Information' :
                'INITIAL CONTACT: Follow Up to Complete Questionnaire';

            $taskBody = $isReturningCustomer ?
                'A returning customer has initiated contact but did not complete the full project questionnaire. Please reach out to gather more information about their project needs. Since they are a returning customer, refer to their previous projects for context.' :
                'Customer has submitted initial contact information but did not complete the full questionnaire. Please reach out to help them complete the questionnaire and gather project details. Their information is currently in the Initial Contact stage.';

            $taskType = 'EMAIL';
        }

        return [$taskSubject, $taskBody, $taskType];
    }

    public function triggerWorkflows(string $dealId, string $contactId, bool $isQuestionnaireComplete): bool
    {
        try {
            if (! $this->isInitialized()) {
                Log::warning('HubSpot client is not initialized - cannot trigger workflows');

                return false;
            }

            $this->updateContactForWorkflow($contactId);
            $this->createWorkflowTriggerNote($contactId, $dealId);

            if ($isQuestionnaireComplete) {
                try {
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

                    $hubspotStageName = $stageMapping['questionnairecompleted'];

                    $dealProperties = [
                        'pipeline' => 'tcsprojects',
                        'dealstage' => $hubspotStageName,
                        'questionnaire_status' => 'COMPLETE',
                        'questionnaire_completed_date' => time() * 1000,
                        'hs_pipeline_stage' => $hubspotStageName,
                        'dealstage_timestamp' => time() * 1000,
                    ];

                    $hubspotOwnerId = config('services.hubspot.owner_id', '');
                    if (! empty($hubspotOwnerId)) {
                        $dealProperties['hubspot_owner_id'] = $hubspotOwnerId;
                    }

                    $input = new \HubSpot\Client\Crm\Deals\Model\SimplePublicObjectInput;
                    $input->setProperties($dealProperties);

                    $this->hubspot->crm()->deals()->basicApi()->update(
                        $dealId,
                        $input
                    );

                    Log::info("Successfully updated deal {$dealId} to stage {$hubspotStageName} (questionnaire completed)");

                    try {
                        $workflowService = app(HubSpotWorkflowService::class);
                        if ($workflowService->isInitialized()) {
                            $workflowService->moveDealToStage($dealId, 'questionnairecompleted');
                            Log::info("Used workflow service to move deal {$dealId} to stage questionnairecompleted");
                        }
                    } catch (\Exception $workflowError) {
                        Log::error('Error using workflow service: '.$workflowError->getMessage());
                    }

                    $workflowId = config('services.hubspot.questionnaire_workflow_id');
                    if (! empty($workflowId)) {
                        try {
                            $workflowService = app(HubSpotWorkflowService::class);
                            if ($workflowService->isInitialized()) {
                                $workflowService->enrollDealInWorkflow($dealId, $workflowId);
                                Log::info("Enrolled deal {$dealId} in workflow {$workflowId}");
                            }
                        } catch (\Exception $workflowError) {
                            Log::error('Error enrolling in workflow: '.$workflowError->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error updating deal stage: '.$e->getMessage());
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error triggering workflows: '.$e->getMessage());

            return false;
        }
    }

    protected function updateContactForWorkflow(string $contactId): bool
    {
        $contactProperties = [
            'questionnaire_completed' => 'true',
            'questionnaire_completion_date' => time() * 1000,
            'hs_lead_status' => 'QUESTIONNAIRE_COMPLETED',
            'form_submission_category' => 'QUESTIONNAIRE',
            'form_status' => 'COMPLETE',
            'questionnaire_status' => 'COMPLETED',
            'leadprogressstage' => 'QUESTIONNAIRE_COMPLETED',
        ];

        $contactInput = new SimplePublicObjectInput;
        $contactInput->setProperties($contactProperties);

        $this->hubspot->crm()->contacts()->basicApi()->update(
            $contactId,
            $contactInput
        );

        return true;
    }

    protected function createWorkflowTriggerNote(string $contactId, string $dealId): bool
    {
        $noteProperties = [
            'hs_note_body' => 'WORKFLOW_TRIGGER: Customer completed questionnaire',
            'hs_timestamp' => time() * 1000,
        ];

        $noteInput = new SimplePublicObjectInputForCreate;
        $noteInput->setProperties($noteProperties);
        $noteInput->setAssociations([
            'contactIds' => [$contactId],
            'dealIds' => [$dealId],
        ]);

        $this->hubspot->crm()->notes()->basicApi()->create($noteInput);
        Log::info('Created workflow trigger note in HubSpot');

        return true;
    }

    protected function generateTCSFormattedDealName(array $validated, array $projectType, bool $isReturningCustomer): string
    {
        $lastName = $validated['lastname'] ?? 'Customer';

        $projectTypeCode = 'GN';
        if (! empty($projectType)) {
            $firstType = reset($projectType);

            $typeMap = [
                'Kitchen Cabinetry' => 'KT',
                'Bathroom Vanity' => 'BT',
                'Built-ins' => 'BU',
                'Furniture' => 'FN',
                'Commercial Space' => 'CM',
                'Other' => 'OT',
            ];

            $projectTypeCode = $typeMap[$firstType] ?? 'GN';
        }

        $orderNumber = $isReturningCustomer ? '002' : '001';

        return "TCS-{$projectTypeCode}-{$orderNumber}-{$lastName}";
    }

    private function mapLeadSourceValue(array $formSources): string
    {
        $hubspotValueMap = [
            'Google' => 'source_google',
            'Social Media' => 'source_social_media',
            'Referral' => 'source_referral',
            'Previous Client' => 'source_previous_client',
            'Houzz' => 'source_houzz',
            'Home Show/Event' => 'source_event',
            'Interior Designer/Architect' => 'source_designer',
            'Other' => 'source_other',
        ];

        $hubspotValues = [];
        foreach ($formSources as $formValue) {
            if (isset($hubspotValueMap[$formValue])) {
                $hubspotValues[] = $hubspotValueMap[$formValue];
            } else {
                Log::warning("Unmapped lead source value from form: {$formValue}");
            }
        }

        return ! empty($hubspotValues) ? implode(';', $hubspotValues) : 'source_other';
    }

    private function mapPreferredCommunicationMethod(string $formValue): string
    {
        $map = [
            'Email' => 'comm_email',
            'Phone' => 'comm_phone',
            'Text Message' => 'comm_text',
            'Video Call' => 'comm_video',
        ];

        return $map[$formValue] ?? '';
    }

    private function mapPreviousExperience(string $formValue): string
    {
        $map = [
            'Yes, with TCS Woodworking' => 'experience_tcs',
            'Yes, with another company' => 'experience_other',
            'No, this is my first custom woodworking project' => 'experience_none',
            'None' => 'experience_none',
        ];

        return $map[$formValue] ?? 'experience_none';
    }

    private function mapProjectType(array $formTypes): array
    {
        $map = [
            'Kitchen Cabinetry' => 'custom_cabinetry',
            'Bathroom Vanity' => 'custom_cabinetry',
            'Built-ins' => 'built_ins',
            'Furniture' => 'furniture',
            'Commercial Space' => 'commercial',
            'Other' => 'other',
            'Countertops' => 'countertops',
        ];
        $hubspotValues = [];
        foreach ($formTypes as $formValue) {
            if (isset($map[$formValue])) {
                $hubspotValues[] = $map[$formValue];
            } else {
                Log::warning("Unmapped project type value from form: {$formValue}");
            }
        }

        return $hubspotValues;
    }

    private function mapDesignStyle(array $formStyles): array
    {
        $map = [
            'Traditional' => 'style_traditional',
            'Modern' => 'style_modern',
            'Contemporary' => 'style_modern',
            'Farmhouse' => 'style_rustic',
            'Industrial' => 'style_industrial',
            'Craftsman' => 'style_craftsman',
            'Scandinavian' => 'style_minimalist',
            'Rustic' => 'style_rustic',
            'Mid-Century Modern' => 'style_midcentury',
            'Minimalist' => 'style_minimalist',
            'Transitional' => 'style_transitional',
            'Eclectic/Mixed' => 'style_eclectic',
            'Not sure/would like recommendations' => 'style_unsure',
            'Other' => 'style_other',
        ];
        $hubspotValues = [];
        foreach ($formStyles as $formValue) {
            if (isset($map[$formValue])) {
                $hubspotValues[] = $map[$formValue];
            } else {
                Log::warning("Unmapped design style value from form: {$formValue}");
            }
        }

        return array_unique($hubspotValues);
    }

    private function mapWoodSpecies(string $formValue): string
    {
        $validSpecies = ['ash', 'birch', 'cherry', 'hickory', 'mahogany', 'maple', 'multiple', 'oak', 'other', 'pine', 'walnut'];
        $lowerFormValue = strtolower($formValue);

        return in_array($lowerFormValue, $validSpecies) ? $lowerFormValue : 'other';
    }

    private function mapProjectPhase(string $formValue): string
    {
        $map = [
            'Just Exploring' => 'phase_concept',
            'Planning Phase with Floor Plans' => 'phase_planning',
            'During Renovation/Construction' => 'phase_renovation',
            'Ready for Immediate Installation' => 'phase_ready',
            'Replacing Existing Items' => 'phase_replacing',
        ];

        return $map[$formValue] ?? 'phase_concept';
    }

    private function mapOtherProfessionals(array $formValues): array
    {
        $map = [
            'Architect' => 'prof_architect',
            'General Contractor' => 'prof_contractor',
            'Interior Designer' => 'prof_designer',
            'Kitchen Designer' => 'prof_kitchen',
            'None/DIY' => 'prof_none',
            'Other' => 'prof_other',
        ];
        $hubspotValues = [];
        foreach ($formValues as $formValue) {
            if (isset($map[$formValue])) {
                $hubspotValues[] = $map[$formValue];
            } else {
                Log::warning("Unmapped other professional value from form: {$formValue}");
            }
        }

        return $hubspotValues;
    }
}
