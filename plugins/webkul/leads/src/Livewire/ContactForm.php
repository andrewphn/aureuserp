<?php

namespace Webkul\Lead\Livewire;

use Coderflex\LaravelTurnstile\Facades\LaravelTurnstile;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Webkul\Lead\Jobs\SendLeadNotificationJob;
use Webkul\Lead\Jobs\SyncLeadToHubSpotJob;
use Webkul\Lead\Models\Lead;

class ContactForm extends Component
{
    use WithRateLimiting;

    // Turnstile token
    public ?string $turnstileToken = null;

    // Step management
    public int $currentStep = 1;
    public int $totalSteps = 3;

    // Step 1: Personal Information
    public string $firstname = '';
    public string $lastname = '';
    public string $email = '';
    public string $phone = '';
    public string $company = '';
    public string $contactpreferred = '';
    public array $source = [];
    public string $referralsourceother = '';
    public bool $processing_consent = false;
    public bool $communication_consent = false;

    // Step 2: Project Information
    public array $project_type = [];
    public string $project_type_other = '';
    public string $project_phase = '';
    public string $project_description = '';
    public array $design_style = [];
    public string $design_style_other = '';
    public string $wood_species = '';
    public string $budget_range = '';
    public string $timeline_start_date = '';
    public string $timeline_completion_date = '';

    // Address fields
    public string $project_address_street1 = '';
    public string $project_address_street2 = '';
    public string $project_address_city = '';
    public string $project_address_state = '';
    public string $project_address_zip = '';
    public string $project_address_country = 'United States';
    public string $project_address_notes = '';

    // Step 3: Additional Info & Submit
    public string $additional_information = '';

    // Honeypot fields
    public string $website = '';
    public string $url = '';
    public string $_gotcha = '';

    // Submission state
    public bool $submitted = false;
    public string $errorMessage = '';

    // Timestamp for spam check
    public int $_timestamp;

    public function mount(): void
    {
        $this->_timestamp = time();

        // Pre-fill from authenticated user if available
        if (auth()->check()) {
            $user = auth()->user();
            $this->firstname = $user->name ? explode(' ', $user->name)[0] ?? '' : '';
            $this->lastname = $user->name && str_contains($user->name, ' ')
                ? substr($user->name, strpos($user->name, ' ') + 1)
                : '';
            $this->email = $user->email ?? '';
        }
    }

    /**
     * Validation rules per step
     */
    protected function rules(): array
    {
        return [
            // Step 1
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[\d\s\-\+\(\)]+$/'],
            'processing_consent' => 'accepted',

            // Optional step 1
            'company' => 'nullable|string|max:255',
            'contactpreferred' => 'nullable|string|max:50',
            'source' => 'nullable|array',
            'communication_consent' => 'nullable|boolean',

            // Step 2
            'project_type' => 'nullable|array',
            'project_description' => 'nullable|string|max:5000',
            'budget_range' => 'nullable|string|max:100',
            'design_style' => 'nullable|array',
            'wood_species' => 'nullable|string|max:100',
            'timeline_start_date' => 'nullable|date',
            'timeline_completion_date' => 'nullable|date',

            // Address
            'project_address_street1' => 'nullable|string|max:255',
            'project_address_city' => 'nullable|string|max:255',
            'project_address_state' => 'nullable|string|max:255',
            'project_address_zip' => 'nullable|string|max:20',

            // Honeypots - must be empty
            'website' => 'nullable|max:0',
            'url' => 'nullable|max:0',
            '_gotcha' => 'nullable|max:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'firstname.required' => 'Please enter your first name.',
            'lastname.required' => 'Please enter your last name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Please enter your phone number.',
            'phone.regex' => 'Please enter a valid phone number.',
            'processing_consent.accepted' => 'You must consent to data processing to continue.',
        ];
    }

    /**
     * Move to next step
     */
    public function nextStep(): void
    {
        $this->validateStep($this->currentStep);
        $this->currentStep++;
    }

    /**
     * Move to previous step
     */
    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Go to specific step
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->currentStep + 1) {
            // Only allow going to completed steps or next step
            if ($step < $this->currentStep) {
                $this->currentStep = $step;
            } elseif ($step === $this->currentStep + 1) {
                $this->nextStep();
            }
        }
    }

    /**
     * Validate current step
     */
    protected function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validate([
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => ['required', 'string', 'max:20', 'regex:/^[\d\s\-\+\(\)]+$/'],
                'processing_consent' => 'accepted',
            ]),
            2 => true, // Step 2 is optional
            3 => true, // Step 3 is review
            default => true,
        };
    }

    /**
     * Check if step is complete
     */
    public function isStepComplete(int $step): bool
    {
        return match ($step) {
            1 => ! empty($this->firstname)
                && ! empty($this->lastname)
                && ! empty($this->email)
                && ! empty($this->phone)
                && $this->processing_consent,
            2 => true, // Always "complete" since optional
            3 => true,
            default => false,
        };
    }

    /**
     * Submit the form
     */
    public function submit(): void
    {
        try {
            // Rate limiting
            $this->rateLimit(5);
        } catch (TooManyRequestsException $e) {
            $this->errorMessage = 'Too many submissions. Please try again in '.$e->secondsUntilAvailable.' seconds.';

            return;
        }

        // Check honeypot fields
        if (! empty($this->website) || ! empty($this->url) || ! empty($this->_gotcha)) {
            // Silently "succeed" for bots
            $this->submitted = true;

            return;
        }

        // Check timing (less than 3 seconds is suspicious)
        if (time() - $this->_timestamp < 3) {
            // Silently "succeed" for bots
            $this->submitted = true;

            return;
        }

        // Validate Turnstile if configured
        if (config('services.turnstile.secret_key')) {
            if (empty($this->turnstileToken)) {
                $this->errorMessage = 'Please complete the security verification.';

                return;
            }

            $response = LaravelTurnstile::validate($this->turnstileToken);
            if (! $response) {
                $this->errorMessage = 'Security verification failed. Please try again.';
                $this->turnstileToken = null; // Reset token for retry

                return;
            }
        }

        $this->validate();

        try {
            // Create the lead
            $lead = Lead::createFromContactForm($this->getFormData());

            Log::info('Lead created from Livewire contact form', [
                'lead_id' => $lead->id,
                'email' => $lead->email,
            ]);

            // Dispatch async jobs
            SyncLeadToHubSpotJob::dispatch($lead);
            SendLeadNotificationJob::dispatch($lead);

            $this->submitted = true;

        } catch (\Exception $e) {
            Log::error('Failed to create lead from contact form', [
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'An error occurred. Please try again.';
        }
    }

    /**
     * Get form data as array
     */
    protected function getFormData(): array
    {
        return [
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'contactpreferred' => $this->contactpreferred,
            'source' => $this->source,
            'referralsourceother' => $this->referralsourceother,
            'processing_consent' => $this->processing_consent,
            'communication_consent' => $this->communication_consent,
            'project_type' => $this->project_type,
            'project_type_other' => $this->project_type_other,
            'project_phase' => $this->project_phase,
            'project_description' => $this->project_description,
            'design_style' => $this->design_style,
            'design_style_other' => $this->design_style_other,
            'wood_species' => $this->wood_species,
            'budget_range' => $this->budget_range,
            'timeline_start_date' => $this->timeline_start_date,
            'timeline_completion_date' => $this->timeline_completion_date,
            'project_address_street1' => $this->project_address_street1,
            'project_address_street2' => $this->project_address_street2,
            'project_address_city' => $this->project_address_city,
            'project_address_state' => $this->project_address_state,
            'project_address_zip' => $this->project_address_zip,
            'project_address_country' => $this->project_address_country,
            'project_address_notes' => $this->project_address_notes,
            'additional_information' => $this->additional_information,
        ];
    }

    public function render()
    {
        return view('leads::livewire.contact-form');
    }
}
