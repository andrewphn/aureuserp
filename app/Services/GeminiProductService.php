<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered product data generation service using Google Gemini
 * Uses Google Search grounding to fetch real product data from the web
 */
class GeminiProductService
{
    protected ?string $geminiKey;

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Generate product details using Gemini with web search grounding
     *
     * @param string $productName The name of the product to research
     * @param string|null $existingDescription Optional existing description for context
     * @return array Structured product data
     */
    public function generateProductDetails(string $productName, ?string $existingDescription = null): array
    {
        if (empty($this->geminiKey)) {
            Log::warning('GeminiProductService: No Gemini API key configured');
            return ['error' => 'Gemini API key not configured. Add GOOGLE_API_KEY to .env'];
        }

        $prompt = $this->buildPrompt($productName, $existingDescription);

        Log::info("GeminiProductService: Generating details for product: {$productName}");

        $maxRetries = 3;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("GeminiProductService: Attempt {$attempt}/{$maxRetries}");

                $response = Http::timeout(90)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->geminiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'tools' => [
                        ['googleSearch' => new \stdClass()] // Enable Google Search grounding
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topP' => 0.95,
                        'maxOutputTokens' => 4096,
                    ],
                ]);

                if ($response->successful()) {
                    $content = $response->json('candidates.0.content.parts.0.text');

                    Log::info("GeminiProductService: Response received: " . substr($content ?? '', 0, 200) . "...");

                    if (empty($content)) {
                        Log::warning("GeminiProductService: Empty response from Gemini");
                        continue;
                    }

                    return $this->parseResponse($content);
                }

                $statusCode = $response->status();
                $errorBody = $response->json();
                Log::warning("GeminiProductService: API error (attempt {$attempt}): status={$statusCode}");

                // If rate limited (429), wait and retry
                if ($statusCode === 429) {
                    $waitTime = pow(2, $attempt) * 2; // 4s, 8s, 16s
                    Log::info("GeminiProductService: Rate limited, waiting {$waitTime} seconds before retry...");
                    sleep($waitTime);
                    continue;
                }

                $lastError = [
                    'error' => 'Gemini API request failed',
                    'status' => $statusCode,
                    'body' => $errorBody
                ];
                break;

            } catch (\Exception $e) {
                Log::error("GeminiProductService error (attempt {$attempt}): " . $e->getMessage());
                $lastError = ['error' => $e->getMessage()];

                if ($attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                }
            }
        }

        Log::error('GeminiProductService: All attempts failed');
        return $lastError ?? ['error' => 'All attempts failed'];
    }

    /**
     * Build the prompt for Gemini
     */
    protected function buildPrompt(string $productName, ?string $existingDescription): string
    {
        $contextSection = '';
        if (!empty($existingDescription)) {
            $contextSection = "\nExisting Description: {$existingDescription}\n";
        }

        return <<<PROMPT
You are a product data specialist for TCS Woodwork, a professional cabinet and furniture shop.
Search the web for accurate, real-world information about the following product.

Product Name: {$productName}
{$contextSection}

Generate product details that a PROFESSIONAL WOODWORKER would actually need to know.
NOT marketing fluff - practical shop floor information.

Return this exact JSON format:

{
    "description": "HTML description for woodworkers (see guidelines below)",
    "brand": "Manufacturer/brand name",
    "sku": "Manufacturer SKU or part number",
    "barcode": "UPC or EAN barcode",
    "product_type": "adhesive|hardware|finish|material|tool|consumable|safety",
    "category_suggestion": "Best category path like 'Adhesives / Epoxy' or 'Hardware / Hinges / Euro'",
    "suggested_price": 0.00,
    "suggested_cost": 0.00,
    "weight": 0.0,
    "volume": 0.0,
    "technical_specs": "Key specifications as plain text",
    "tags": ["tag1", "tag2", "tag3"],
    "source_url": "URL where you found the main product info"
}

TAG GUIDELINES - Use specific, searchable tags:
Include tags for:
- Brand name (e.g., "west-system", "blum", "titebond")
- Product type (e.g., "epoxy", "hinge", "slide", "stain")
- Material compatibility (e.g., "wood-glue", "metal-bond", "plastic-safe")
- Application (e.g., "cabinet", "marine", "structural", "furniture")
- Special properties (e.g., "waterproof", "food-safe", "fast-cure", "gap-filling")
- Size/format (e.g., "quart", "gallon", "35mm", "full-overlay")

DESCRIPTION GUIDELINES - Include what woodworkers need:
Use HTML <p> tags. Focus on PRACTICAL information:

For ADHESIVES/GLUES:
- Open time / working time
- Cure time / clamp time
- Temperature requirements (min shop temp)
- Mix ratio (if 2-part)
- Gap-filling capability
- Water resistance rating (Type I, II, III)
- Cleanup method (water, solvent, acetone)
- Compatible materials

For HARDWARE (hinges, slides, fasteners):
- Exact dimensions and mounting requirements
- Weight capacity / load rating
- Required boring patterns (32mm, 35mm cup)
- Compatible overlay/inset specs
- Soft-close included?

For FINISHES (stains, sealers, topcoats):
- Coverage rate (sq ft per gallon)
- Dry time between coats
- Recoat window
- VOC content / compliance
- Application method (spray, brush, wipe)

For MATERIALS (sheet goods, lumber, edgebanding):
- Actual vs nominal dimensions
- Core type and thickness
- Machining notes

For ALL products:
- Safety/PPE requirements
- Storage requirements
- Shelf life
- Common issues to avoid

Field guidelines:
- brand: Manufacturer name (e.g., "West System", "Blum", "Titebond")
- sku: Manufacturer part number (empty string if not found)
- barcode: UPC/EAN (empty string if not found)
- product_type: One of: adhesive, hardware, finish, material, tool, consumable, safety
- category_suggestion: Category path with " / " separator
- suggested_price: Retail price USD (0 if unknown)
- suggested_cost: Wholesale ~40-60% of retail (0 if unknown)
- weight: In kilograms (0 if unknown)
- volume: In liters (0 if unknown)
- source_url: Primary source URL for this product info

Return ONLY valid JSON, no markdown.
PROMPT;
    }

    /**
     * Parse and validate the API response
     */
    protected function parseResponse(string $content): array
    {
        // Clean up markdown code blocks if present
        $content = preg_replace('/^```json\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        // Try to parse as JSON
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('GeminiProductService: Response not valid JSON: ' . json_last_error_msg());
            return [
                'error' => 'Failed to parse AI response',
                'raw_response' => $content,
                'parse_error' => json_last_error_msg()
            ];
        }

        // Validate and sanitize the response
        return $this->sanitizeResponse($decoded);
    }

    /**
     * Sanitize and validate response data
     */
    protected function sanitizeResponse(array $data): array
    {
        return [
            // Core product info
            'description' => $this->sanitizeHtml($data['description'] ?? ''),
            'description_sale' => $this->sanitizeHtml($data['description_sale'] ?? $data['description'] ?? ''),
            'description_purchase' => trim($data['description_purchase'] ?? ''),

            // Identifiers
            'brand' => trim($data['brand'] ?? ''),
            'sku' => trim($data['sku'] ?? ''),
            'barcode' => trim($data['barcode'] ?? ''),

            // Classification
            'product_type' => trim($data['product_type'] ?? ''),
            'category_suggestion' => trim($data['category_suggestion'] ?? ''),

            // Pricing
            'suggested_price' => $this->sanitizeNumber($data['suggested_price'] ?? 0),
            'suggested_cost' => $this->sanitizeNumber($data['suggested_cost'] ?? 0),

            // Physical properties
            'weight' => $this->sanitizeNumber($data['weight'] ?? 0),
            'volume' => $this->sanitizeNumber($data['volume'] ?? 0),

            // Additional info
            'technical_specs' => trim($data['technical_specs'] ?? ''),
            'tags' => is_array($data['tags'] ?? null) ? $data['tags'] : [],
            'source_url' => trim($data['source_url'] ?? ''),
        ];
    }

    /**
     * Sanitize HTML content (basic cleanup)
     */
    protected function sanitizeHtml(string $html): string
    {
        // Allow only safe HTML tags for RichEditor
        $allowed = '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6>';
        return strip_tags(trim($html), $allowed);
    }

    /**
     * Sanitize numeric values
     */
    protected function sanitizeNumber($value): float
    {
        if (is_numeric($value)) {
            return max(0, (float) $value);
        }
        return 0.0;
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->geminiKey);
    }
}
