<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webkul\Partner\Models\Industry;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;
use Exception;

/**
 * Service class for AI-powered vendor lookup and auto-fill
 *
 * Uses Gemini AI to:
 * - Look up vendor information by company name
 * - Extract company details from website URLs
 * - Suggest industry categories
 * - Validate and enhance vendor data
 */
class VendorAiService
{
    protected GeminiService $geminiService;

    /**
     * Cache TTL for vendor lookups (24 hours)
     */
    protected int $cacheTtl = 86400;

    /**
     * Initialize the vendor AI service
     */
    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Look up vendor information by company name
     *
     * @param string $name The company name to search for
     * @return array Structured vendor data
     */
    public function lookupByName(string $name): array
    {
        $cacheKey = 'vendor_ai_name_' . md5(strtolower(trim($name)));

        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            Log::info('VendorAiService: Cache hit for name lookup', ['name' => $name]);
            return $cached;
        }

        try {
            $prompt = $this->buildNameLookupPrompt($name);
            $response = $this->geminiService->generateResponse($prompt);
            $result = $this->parseAiResponse($response, 'name', $name);

            // Cache successful results
            if ($result['success']) {
                Cache::put($cacheKey, $result, $this->cacheTtl);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('VendorAiService: Name lookup failed', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to look up vendor: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Look up vendor information by website URL
     *
     * @param string $url The website URL to analyze
     * @return array Structured vendor data
     */
    public function lookupByWebsite(string $url): array
    {
        // Normalize URL
        $url = $this->normalizeUrl($url);
        $cacheKey = 'vendor_ai_url_' . md5($url);

        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            Log::info('VendorAiService: Cache hit for URL lookup', ['url' => $url]);
            return $cached;
        }

        try {
            $prompt = $this->buildWebsiteLookupPrompt($url);
            $response = $this->geminiService->generateResponse($prompt);
            $result = $this->parseAiResponse($response, 'website', $url);

            // Cache successful results
            if ($result['success']) {
                Cache::put($cacheKey, $result, $this->cacheTtl);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('VendorAiService: Website lookup failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to analyze website: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Suggest industry ID based on company name and description
     *
     * @param string $name Company name
     * @param string|null $description Optional description
     * @return int|null Industry ID or null
     */
    public function suggestIndustry(string $name, ?string $description = null): ?int
    {
        try {
            // Get available industries from database
            $industries = Industry::pluck('name', 'id')->toArray();

            if (empty($industries)) {
                return null;
            }

            $prompt = $this->buildIndustrySuggestionPrompt($name, $description, $industries);
            $response = $this->geminiService->generateResponse($prompt);

            // Extract industry name from response
            $suggestedIndustry = $this->extractIndustryFromResponse($response, $industries);

            return $suggestedIndustry;

        } catch (Exception $e) {
            Log::error('VendorAiService: Industry suggestion failed', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Build the prompt for name-based lookup
     */
    protected function buildNameLookupPrompt(string $name): string
    {
        return <<<PROMPT
You are an AI assistant helping find vendor/supplier business information for a woodworking company's ERP system.

Search for this company: "{$name}"

Return a JSON object with the following fields. Use null for any field you cannot find:

{
  "company_name": "Official legal company name",
  "account_type": "company" or "individual",
  "phone": "Main business phone number",
  "email": "General contact or sales email",
  "website": "Company website URL",
  "street1": "Street address line 1",
  "street2": "Street address line 2 (suite, unit, etc.)",
  "city": "City name",
  "state": "State/Province name",
  "zip": "ZIP/Postal code",
  "country": "Country name",
  "industry": "Type of business (e.g., Hardware Retail, Lumber Supply, Building Materials, Industrial Supply)",
  "tax_id": "Tax ID if publicly available, otherwise null",
  "description": "Brief company description (1-2 sentences)",
  "confidence": 0.0 to 1.0 (how confident you are in this data)
}

Focus on US and Canadian businesses. If multiple companies match, return the most relevant one for a woodworking/cabinet shop supplier.

Return ONLY the JSON object, no additional text.
PROMPT;
    }

    /**
     * Build the prompt for website-based lookup
     */
    protected function buildWebsiteLookupPrompt(string $url): string
    {
        return <<<PROMPT
You are an AI assistant helping extract business information from a company website for a woodworking company's ERP system.

Analyze this website: {$url}

Extract and return a JSON object with the following fields. Use null for any field you cannot determine:

{
  "company_name": "Official company name from the website",
  "account_type": "company" or "individual",
  "phone": "Main contact phone number",
  "email": "General contact or sales email",
  "website": "{$url}",
  "street1": "Street address line 1",
  "street2": "Street address line 2 (suite, unit, etc.)",
  "city": "City name",
  "state": "State/Province name",
  "zip": "ZIP/Postal code",
  "country": "Country name",
  "industry": "Type of business based on products/services",
  "tax_id": null,
  "description": "Brief company description based on their services/products",
  "confidence": 0.0 to 1.0 (how confident you are in this data)
}

Return ONLY the JSON object, no additional text.
PROMPT;
    }

    /**
     * Build the prompt for industry suggestion
     */
    protected function buildIndustrySuggestionPrompt(string $name, ?string $description, array $industries): string
    {
        $industryList = implode(', ', array_values($industries));

        $context = $description ? "Description: {$description}" : "";

        return <<<PROMPT
Given a company name and optional description, suggest the most appropriate industry category.

Company Name: {$name}
{$context}

Available industries: {$industryList}

Return ONLY the exact industry name from the list above that best matches this company. If none match well, return "null".
PROMPT;
    }

    /**
     * Parse AI response and extract structured data
     */
    protected function parseAiResponse(string $response, string $lookupType, string $query): array
    {
        try {
            // Extract JSON from response
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');

            if ($jsonStart === false || $jsonEnd === false) {
                throw new Exception('No JSON found in AI response');
            }

            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $data = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in AI response: ' . json_last_error_msg());
            }

            // Map country and state to IDs
            $data = $this->mapLocationIds($data);

            // Map industry to ID
            $data = $this->mapIndustryId($data);

            // Filter out null values to prevent "null" strings in form fields
            $data = $this->filterNullValues($data);

            Log::info('VendorAiService: Successfully parsed AI response', [
                'lookup_type' => $lookupType,
                'query' => $query,
                'confidence' => $data['confidence'] ?? 0
            ]);

            return [
                'success' => true,
                'data' => $data,
                'lookup_type' => $lookupType,
                'query' => $query
            ];

        } catch (Exception $e) {
            Log::warning('VendorAiService: Failed to parse AI response', [
                'lookup_type' => $lookupType,
                'query' => $query,
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500)
            ]);

            return [
                'success' => false,
                'error' => 'Could not parse vendor information',
                'data' => null
            ];
        }
    }

    /**
     * Map country and state names to database IDs
     */
    protected function mapLocationIds(array $data): array
    {
        // Map country - prefer exact match, then partial match
        if (!empty($data['country'])) {
            $countryName = trim($data['country']);

            // Try exact match first
            $country = Country::where('name', $countryName)->first();

            // Try common variations for United States
            if (!$country && in_array(strtolower($countryName), ['united states', 'usa', 'us', 'u.s.', 'u.s.a.', 'america', 'united states of america'])) {
                $country = Country::where('name', 'United States')->first();
            }

            // Try by code
            if (!$country && strlen($countryName) <= 3) {
                $country = Country::where('code', strtoupper($countryName))->first();
            }

            // Fall back to partial match, but exclude "Minor Outlying Islands" variants
            if (!$country) {
                $country = Country::where('name', 'like', $countryName . '%')
                    ->where('name', 'not like', '%Minor%')
                    ->where('name', 'not like', '%Outlying%')
                    ->first();
            }

            $data['country_id'] = $country?->id;
            $data['country_name'] = $data['country'];
        }

        // Map state - prefer exact match, then partial match
        if (!empty($data['state'])) {
            $stateName = trim($data['state']);

            // Try exact match first
            $stateQuery = State::where('name', $stateName);

            // Filter by country if we have it
            if (!empty($data['country_id'])) {
                $stateQuery->where('country_id', $data['country_id']);
            }

            $state = $stateQuery->first();

            // Try by code (e.g., "GA" for Georgia)
            if (!$state && strlen($stateName) <= 3) {
                $stateQuery = State::where('code', strtoupper($stateName));
                if (!empty($data['country_id'])) {
                    $stateQuery->where('country_id', $data['country_id']);
                }
                $state = $stateQuery->first();
            }

            // Fall back to partial match
            if (!$state) {
                $stateQuery = State::where('name', 'like', $stateName . '%');
                if (!empty($data['country_id'])) {
                    $stateQuery->where('country_id', $data['country_id']);
                }
                $state = $stateQuery->first();
            }

            $data['state_id'] = $state?->id;
            $data['state_name'] = $data['state'];
        }

        return $data;
    }

    /**
     * Map industry name to database ID
     */
    protected function mapIndustryId(array $data): array
    {
        if (!empty($data['industry'])) {
            $industry = Industry::where('name', 'like', '%' . $data['industry'] . '%')->first();
            $data['industry_id'] = $industry?->id;
            $data['industry_name'] = $data['industry'];
        }

        return $data;
    }

    /**
     * Filter out null values and the string "null" to prevent form field pollution
     *
     * Keeps important fields like confidence even if 0
     */
    protected function filterNullValues(array $data): array
    {
        $keysToPreserve = ['confidence', 'account_type'];

        return array_filter($data, function ($value, $key) use ($keysToPreserve) {
            // Always preserve certain keys
            if (in_array($key, $keysToPreserve)) {
                return true;
            }

            // Filter out null, empty strings, and the literal string "null"
            if ($value === null || $value === '' || $value === 'null') {
                return false;
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Extract industry ID from AI response
     */
    protected function extractIndustryFromResponse(string $response, array $industries): ?int
    {
        $response = trim($response);

        if (strtolower($response) === 'null') {
            return null;
        }

        // Find matching industry
        foreach ($industries as $id => $name) {
            if (stripos($response, $name) !== false || stripos($name, $response) !== false) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Normalize URL to standard format
     */
    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);

        // Add https:// if no protocol specified
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Clear cache for a specific vendor lookup
     */
    public function clearCache(string $type, string $query): void
    {
        if ($type === 'name') {
            Cache::forget('vendor_ai_name_' . md5(strtolower(trim($query))));
        } else {
            Cache::forget('vendor_ai_url_' . md5($this->normalizeUrl($query)));
        }
    }
}
