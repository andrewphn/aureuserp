<?php

namespace App\Filament\Forms\Components;

use Filament\Schemas\Components\Component;

/**
 * AI-Powered Vendor Lookup Component
 *
 * Provides a search interface that uses Gemini AI to look up
 * vendor information by name or website URL and auto-fill form fields.
 */
class AiVendorLookup extends Component
{
    protected string $view = 'filament.forms.components.ai-vendor-lookup';

    /**
     * Field mappings from AI response to form field names
     */
    protected array $fieldMappings = [
        'company_name' => 'name',
        'account_type' => 'account_type',
        'phone' => 'phone',
        'email' => 'email',
        'website' => 'website',
        'street1' => 'street1',
        'street2' => 'street2',
        'city' => 'city',
        'zip' => 'zip',
        'country_id' => 'country_id',
        'state_id' => 'state_id',
        'industry_id' => 'industry_id',
        'tax_id' => 'tax_id',
    ];

    /**
     * Create a new component instance
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Configure custom field mappings
     *
     * @param array $mappings Array of [ai_field => form_field] mappings
     */
    public function fieldMappings(array $mappings): static
    {
        $this->fieldMappings = array_merge($this->fieldMappings, $mappings);

        return $this;
    }

    /**
     * Get the field mappings for the view
     */
    public function getFieldMappings(): array
    {
        return $this->fieldMappings;
    }

    /**
     * Get the API endpoint URL
     */
    public function getApiEndpoint(): string
    {
        return route('api.vendor.ai-lookup');
    }

    /**
     * Get the API token for authenticated requests
     */
    public function getApiToken(): ?string
    {
        // Get the current user's API token if using Sanctum
        $user = auth()->user();

        if ($user && method_exists($user, 'createToken')) {
            // Use existing token or create a new one
            $token = $user->tokens()->where('name', 'vendor-ai-lookup')->first();

            if (!$token) {
                return $user->createToken('vendor-ai-lookup')->plainTextToken;
            }

            // Return existing token's ability to make API calls
            // Note: We'll use session-based auth instead for Filament
            return null;
        }

        return null;
    }
}
