<?php

namespace Webkul\Lead\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Webkul\Lead\Jobs\SendLeadNotificationJob;
use Webkul\Lead\Jobs\SyncLeadToHubSpotJob;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Rules\SpamProtection;

class ContactController extends Controller
{
    /**
     * Store a new contact form submission
     */
    public function store(Request $request): JsonResponse
    {
        // Check for trusted API key (bypasses Turnstile for server-to-server calls)
        $isTrustedSource = $this->isFromTrustedSource($request);

        // Validate the request
        $validator = Validator::make($request->all(), $this->validationRules($isTrustedSource));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create lead from form data
            $lead = Lead::createFromContactForm($request->all());

            // Handle file uploads
            if ($request->hasFile('project_inspiration_images')) {
                foreach ($request->file('project_inspiration_images') as $file) {
                    $lead->addMedia($file)->toMediaCollection('inspiration_images');
                }
            }
            if ($request->hasFile('project_technical_drawings')) {
                foreach ($request->file('project_technical_drawings') as $file) {
                    $lead->addMedia($file)->toMediaCollection('technical_drawings');
                }
            }
            if ($request->hasFile('project_documents')) {
                foreach ($request->file('project_documents') as $file) {
                    $lead->addMedia($file)->toMediaCollection('project_documents');
                }
            }

            Log::info('Lead created from contact form', [
                'lead_id' => $lead->id,
                'email' => $lead->email,
                'files_count' => $lead->getMedia()->count(),
            ]);

            // Dispatch async jobs
            SyncLeadToHubSpotJob::dispatch($lead);
            SendLeadNotificationJob::dispatch($lead);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your inquiry. We will be in touch soon.',
                'lead_id' => $lead->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create lead from contact form', [
                'error' => $e->getMessage(),
                'data' => $request->except(['password', 'password_confirmation']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
            ], 500);
        }
    }

    /**
     * Check if customer exists (for returning customer flow)
     */
    public function checkCustomer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'exists' => false,
            ]);
        }

        $email = $request->input('email');

        // Check in partners table
        $partner = \Webkul\Partner\Models\Partner::where('email', $email)->first();

        // Check in leads table
        $existingLead = Lead::where('email', $email)->first();

        return response()->json([
            'success' => true,
            'exists' => (bool) ($partner || $existingLead),
            'is_partner' => (bool) $partner,
            'is_previous_lead' => (bool) $existingLead,
            'name' => $partner?->name ?? ($existingLead ? "{$existingLead->first_name} {$existingLead->last_name}" : null),
        ]);
    }

    /**
     * Check if request is from a trusted source (has valid API key)
     */
    protected function isFromTrustedSource(Request $request): bool
    {
        $apiKey = $request->header('X-API-Key');
        $configuredKey = config('services.leads.api_key');

        if (empty($configuredKey)) {
            return false;
        }

        return $apiKey === $configuredKey;
    }

    /**
     * Get validation rules for contact form
     */
    protected function validationRules(bool $isTrustedSource = false): array
    {
        // Determine Turnstile requirement
        $turnstileRule = 'nullable';
        if (!$isTrustedSource && config('services.turnstile.secret_key')) {
            $turnstileRule = ['required', new \Coderflex\LaravelTurnstile\Rules\TurnstileCheck];
        }

        return [
            // Required fields
            'firstname' => ['required', 'string', 'max:255', new SpamProtection('name')],
            'lastname' => ['required', 'string', 'max:255', new SpamProtection('name')],
            'email' => ['required', 'email', 'max:255', new SpamProtection('email')],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[\d\s\-\+\(\)]+$/'],
            'processing_consent' => 'required|accepted',

            // CAPTCHA (required unless from trusted source)
            'cf-turnstile-response' => $turnstileRule,

            // Honeypot fields (must be empty)
            'website' => 'nullable|max:0',
            'url' => 'nullable|max:0',
            '_gotcha' => 'nullable|max:0',
            'honey_email' => 'nullable|max:0',
            'honey_name' => 'nullable|max:0',

            // Optional contact fields
            'company' => ['nullable', 'string', 'max:255', new SpamProtection('company')],
            'contactpreferred' => 'nullable|string|max:50',
            'source' => 'nullable|array',
            'source.*' => 'string|max:100',
            'referralsourceother' => 'nullable|string|max:255',

            // Optional project fields
            'project_type' => 'nullable|array',
            'project_type.*' => 'string|max:100',
            'project_type_other' => 'nullable|string|max:255',
            'project_phase' => 'nullable|string|max:100',
            'project_description' => ['nullable', 'string', 'max:5000', new SpamProtection('message')],
            'design_style' => 'nullable|array',
            'design_style.*' => 'string|max:100',
            'design_style_other' => 'nullable|string|max:255',
            'finish_choices' => 'nullable|array',
            'finish_choices.*' => 'string|max:100',
            'wood_species' => 'nullable|string|max:100',
            'budget_range' => 'nullable|string|max:100',
            'timeline_start_date' => 'nullable|date',
            'timeline_completion_date' => 'nullable|date',

            // Address fields
            'project_address_street1' => 'nullable|string|max:255',
            'project_address_street2' => 'nullable|string|max:255',
            'project_address_city' => 'nullable|string|max:255',
            'project_address_state' => 'nullable|string|max:255',
            'project_address_zip' => 'nullable|string|max:20',
            'project_address_country' => 'nullable|string|max:255',
            'project_address_notes' => 'nullable|string|max:1000',

            // File uploads
            'project_inspiration_images' => 'nullable|array',
            'project_inspiration_images.*' => 'file|mimes:jpeg,png,webp,gif|max:10240',
            'project_technical_drawings' => 'nullable|array',
            'project_technical_drawings.*' => 'file|mimes:pdf,jpeg,png|max:20480',
            'project_documents' => 'nullable|array',
            'project_documents.*' => 'file|mimes:pdf,doc,docx|max:20480',

            // Additional fields
            'additional_information' => ['nullable', 'string', 'max:5000', new SpamProtection('message')],
            'previous_woodworking_experience' => 'nullable|string|max:100',
            'communication_consent' => 'nullable|boolean',
            'is_returning_customer' => 'nullable|boolean',
        ];
    }
}
