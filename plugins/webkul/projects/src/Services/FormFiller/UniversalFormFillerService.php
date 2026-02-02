<?php

namespace Webkul\Project\Services\FormFiller;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Project;
use Webkul\Partner\Models\Partner;
use Carbon\Carbon;

/**
 * Universal Form Filler Service
 *
 * Handles template parsing, field extraction, and AI-powered auto-fill.
 */
class UniversalFormFillerService
{
    /**
     * Parse editable fields from HTML template content
     * Looks for {{VARIABLE}} patterns and blank/underscore fields
     */
    public function parseEditableFields(string $content): array
    {
        $fields = [];

        // Match {{VARIABLE_NAME}} patterns
        preg_match_all('/\{\{([A-Z][A-Z0-9_]*)\}\}/', $content, $matches);

        foreach ($matches[1] as $fieldName) {
            if (!isset($fields[$fieldName])) {
                $fields[$fieldName] = '';
            }
        }

        // Match common blank patterns (underscores, empty inputs)
        // These indicate fillable fields in hand-written templates
        $blankPatterns = [
            'BOL_NUMBER' => '/BOL Number.*?<div[^>]*class="value"[^>]*>([^<]*)<\/div>/is',
            'SHIP_DATE' => '/Ship Date.*?<div[^>]*class="value"[^>]*>([^<]*)<\/div>/is',
            'PURCHASE_ORDER' => '/Purchase Order.*?<div[^>]*class="value"[^>]*>([^<]*)<\/div>/is',
            'PRO_NUMBER' => '/Pro Number.*?<div[^>]*class="value"[^>]*>([^<]*)<\/div>/is',
        ];

        foreach ($blankPatterns as $fieldName => $pattern) {
            if (preg_match($pattern, $content) && !isset($fields[$fieldName])) {
                $fields[$fieldName] = '';
            }
        }

        return $fields;
    }

    /**
     * Fill fields from a project's data
     */
    public function fillFromProject(array $fields, Project $project): array
    {
        $partner = $project->partner;
        $order = $project->orders()->latest()->first();

        $mappings = [
            // Project fields
            'PROJECT_NUMBER' => $project->project_number ?? $project->id,
            'PROJECT_NAME' => $project->name,
            'PROJECT_DATE' => $project->created_at?->format('F j, Y'),
            'PROJECT_TYPE' => $project->name,
            'PROJECT_NOTES' => $project->description,

            // Client/Partner fields
            'CLIENT_NAME' => $partner?->name,
            'CLIENT_COMPANY' => $partner?->name,
            'CLIENT_STREET' => $partner?->street1,
            'CLIENT_CITY' => $partner?->city,
            'CLIENT_STATE' => $partner?->state?->name ?? $partner?->state?->code,
            'CLIENT_ZIP' => $partner?->zip,
            'CLIENT_PHONE' => $partner?->phone,
            'CLIENT_EMAIL' => $partner?->email,

            // Consignee (same as client for delivery)
            'CONSIGNEE_NAME' => $partner?->name,
            'CONSIGNEE_STREET' => $partner?->street1,
            'CONSIGNEE_CITY' => $partner?->city,
            'CONSIGNEE_STATE' => $partner?->state?->name ?? $partner?->state?->code,
            'CONSIGNEE_ZIP' => $partner?->zip,
            'CONSIGNEE_PHONE' => $partner?->phone,

            // Order fields (if available)
            'ORDER_NUMBER' => $order?->name,
            'TOTAL_PRICE' => $order ? number_format($order->amount_total, 2) : null,
            'DEPOSIT_AMOUNT' => $order ? number_format(($order->amount_total * 0.30), 2) : null,

            // BOL specific
            'BOL_NUMBER' => $this->generateBolNumber($project),
            'SHIP_DATE' => now()->format('m/d/Y'),

            // Company info (static)
            'SHIPPER_NAME' => "The Carpenter's Son Fine Woodworking Inc",
            'SHIPPER_STREET' => '392 N Montgomery St, Building B',
            'SHIPPER_CITY' => 'Newburgh',
            'SHIPPER_STATE' => 'NY',
            'SHIPPER_ZIP' => '12550',
            'SHIPPER_PHONE' => '(845) 816-2388',
            'SHIPPER_EMAIL' => 'info@tcswoodwork.com',
        ];

        // Only fill fields that exist in the template and have mappings
        foreach ($fields as $fieldName => $currentValue) {
            if (isset($mappings[$fieldName]) && $mappings[$fieldName] !== null) {
                $fields[$fieldName] = $mappings[$fieldName];
            }
        }

        return $fields;
    }

    /**
     * Apply field values to template content
     */
    public function applyFields(string $content, array $fields): string
    {
        // Replace {{VARIABLE}} patterns
        foreach ($fields as $fieldName => $value) {
            $content = str_replace("{{" . $fieldName . "}}", $value ?? '', $content);
        }

        // Replace underscore placeholders with actual values
        // Pattern: ______ or _____
        foreach ($fields as $fieldName => $value) {
            if (!empty($value)) {
                // Try to find and replace underscore placeholders near field labels
                $labelPatterns = [
                    // Label: _____ pattern
                    "/{$fieldName}:?\s*_{3,}/i" => "{$fieldName}: {$value}",
                ];
            }
        }

        return $content;
    }

    /**
     * Process AI prompt to fill fields
     */
    public function processAiPrompt(string $prompt, array $currentFields, ?Project $project = null): array
    {
        try {
            // Build context for AI
            $context = $this->buildAiContext($currentFields, $project);

            // Call internal AI endpoint or external API
            $result = $this->callAiService($prompt, $context, array_keys($currentFields));

            if ($result['success']) {
                return [
                    'success' => true,
                    'fields' => $result['fields'],
                    'message' => $result['message'] ?? 'Fields updated',
                ];
            }

            return [
                'success' => false,
                'error' => $result['error'] ?? 'AI processing failed',
            ];

        } catch (\Exception $e) {
            Log::error('AI Form Fill Error', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build context for AI processing
     */
    protected function buildAiContext(array $fields, ?Project $project): array
    {
        $context = [
            'current_fields' => $fields,
            'available_fields' => array_keys($fields),
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i'),
        ];

        if ($project) {
            $context['project'] = [
                'name' => $project->name,
                'number' => $project->project_number,
                'client' => $project->partner?->name,
                'address' => $project->partner?->full_address ?? null,
            ];
        }

        return $context;
    }

    /**
     * Call AI service (internal API or external)
     */
    protected function callAiService(string $prompt, array $context, array $availableFields): array
    {
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

        if (!$apiKey) {
            // Fallback: Try to parse prompt manually for simple cases
            return $this->parsePromptManually($prompt, $availableFields);
        }

        try {
            $systemPrompt = $this->buildSystemPrompt($availableFields);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Context: " . json_encode($context) . "\n\nUser request: " . $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                return $this->parseAiResponse($content);
            }

            return [
                'success' => false,
                'error' => 'API call failed: ' . $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'AI service error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build system prompt for AI
     */
    protected function buildSystemPrompt(array $availableFields): string
    {
        $fieldList = implode(', ', $availableFields);

        return <<<PROMPT
You are a form-filling assistant for The Carpenter's Son woodworking company.
You help fill out business documents like Bills of Lading, proposals, and invoices.

Available fields to fill: {$fieldList}

When the user describes information, extract relevant values and return them as JSON.
Only include fields that you have actual values for.

Response format:
```json
{
  "fields": {
    "FIELD_NAME": "value",
    "ANOTHER_FIELD": "value"
  },
  "message": "Brief description of what was filled"
}
```

Company Info (use if needed):
- Company: The Carpenter's Son Fine Woodworking Inc
- Address: 392 N Montgomery St, Building B, Newburgh, NY 12550
- Phone: (845) 816-2388
- Email: info@tcswoodwork.com

Date format: MM/DD/YYYY
Phone format: (XXX) XXX-XXXX
PROMPT;
    }

    /**
     * Parse AI response JSON
     */
    protected function parseAiResponse(string $content): array
    {
        // Extract JSON from response (may be wrapped in markdown code blocks)
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Failed to parse AI response',
            ];
        }

        return [
            'success' => true,
            'fields' => $data['fields'] ?? [],
            'message' => $data['message'] ?? 'Fields updated',
        ];
    }

    /**
     * Fallback: Parse prompt manually for simple patterns
     */
    protected function parsePromptManually(string $prompt, array $availableFields): array
    {
        $fields = [];
        $prompt = strtolower($prompt);

        // Simple pattern matching for common cases

        // Courier/carrier name
        if (preg_match('/courier|carrier|shipping company[:\s]+([a-zA-Z\s]+)/i', $prompt, $m)) {
            if (in_array('CARRIER_NAME', $availableFields) || in_array('CARRIER_COMPANY', $availableFields)) {
                $fields['CARRIER_NAME'] = trim($m[1]);
                $fields['CARRIER_COMPANY'] = trim($m[1]);
            }
        }

        // Date patterns
        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{2,4})/', $prompt, $m)) {
            if (in_array('SHIP_DATE', $availableFields)) {
                $fields['SHIP_DATE'] = $m[1];
            }
        }

        // Tomorrow/today
        if (str_contains($prompt, 'tomorrow')) {
            $fields['SHIP_DATE'] = Carbon::tomorrow()->format('m/d/Y');
        } elseif (str_contains($prompt, 'today')) {
            $fields['SHIP_DATE'] = Carbon::today()->format('m/d/Y');
        }

        // Time patterns
        if (preg_match('/(\d{1,2})\s*(am|pm)/i', $prompt, $m)) {
            if (in_array('PICKUP_TIME', $availableFields)) {
                $fields['PICKUP_TIME'] = $m[1] . strtoupper($m[2]);
            }
        }

        if (empty($fields)) {
            return [
                'success' => false,
                'error' => 'Could not parse instructions. Please try being more specific or configure an AI API key.',
            ];
        }

        return [
            'success' => true,
            'fields' => $fields,
            'message' => 'Parsed ' . count($fields) . ' field(s) from your instructions',
        ];
    }

    /**
     * Generate BOL number from project
     */
    protected function generateBolNumber(Project $project): string
    {
        $prefix = 'BOL';
        $projectNum = $project->project_number ?? str_pad($project->id, 4, '0', STR_PAD_LEFT);
        $date = now()->format('ymd');

        return "{$prefix}-{$projectNum}-{$date}";
    }

    /**
     * Get available variables for a template type
     */
    public static function getAvailableVariables(string $type = 'bol'): array
    {
        $common = [
            'PROJECT_NUMBER' => 'Project number',
            'PROJECT_NAME' => 'Project name',
            'PROJECT_DATE' => 'Project/order date',
            'CLIENT_NAME' => 'Client/company name',
            'CLIENT_STREET' => 'Client street address',
            'CLIENT_CITY' => 'Client city',
            'CLIENT_STATE' => 'Client state',
            'CLIENT_ZIP' => 'Client ZIP code',
            'CLIENT_PHONE' => 'Client phone',
            'CLIENT_EMAIL' => 'Client email',
        ];

        $bol = [
            'BOL_NUMBER' => 'Bill of Lading number',
            'SHIP_DATE' => 'Shipping date',
            'PURCHASE_ORDER' => 'Purchase order number',
            'PRO_NUMBER' => 'PRO/tracking number',
            'SHIPPER_NAME' => 'Shipper company name',
            'SHIPPER_STREET' => 'Shipper address',
            'CONSIGNEE_NAME' => 'Consignee name',
            'CONSIGNEE_STREET' => 'Consignee address',
            'CARRIER_NAME' => 'Carrier company name',
            'CARRIER_PHONE' => 'Carrier phone',
            'DRIVER_NAME' => 'Driver name',
            'DRIVER_PHONE' => 'Driver phone',
            'LICENSE_PLATE' => 'Vehicle license plate',
            'TOTAL_WEIGHT' => 'Total weight',
            'TOTAL_PIECES' => 'Total pieces count',
            'DECLARED_VALUE' => 'Declared value',
            'SPECIAL_INSTRUCTIONS' => 'Special handling instructions',
        ];

        if ($type === 'bol') {
            return array_merge($common, $bol);
        }

        return $common;
    }
}
