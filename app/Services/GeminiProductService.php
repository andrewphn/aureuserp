<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * Get available categories for AI selection
     */
    protected function getCategories(): array
    {
        return Cache::remember('ai_product_categories', 3600, function () {
            return DB::table('products_categories')
                ->select('id', 'name')
                ->whereNotIn('name', ['All', 'Internal', 'Expenses'])
                ->orderBy('name')
                ->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
                ->toArray();
        });
    }

    /**
     * Get available reference type codes for AI selection
     */
    protected function getReferenceCodes(): array
    {
        return Cache::remember('ai_reference_type_codes', 3600, function () {
            return DB::table('products_reference_type_codes as r')
                ->join('products_categories as c', 'r.category_id', '=', 'c.id')
                ->select('r.id', 'r.code', 'r.name', 'r.category_id', 'c.name as category_name')
                ->where('r.is_active', true)
                ->orderBy('c.name')
                ->orderBy('r.name')
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'code' => $r->code,
                    'name' => $r->name,
                    'category_id' => $r->category_id,
                    'category' => $r->category_name,
                ])
                ->toArray();
        });
    }

    /**
     * Build category options string for prompt
     */
    protected function buildCategoryOptions(): string
    {
        $categories = $this->getCategories();
        $lines = [];
        foreach ($categories as $cat) {
            $lines[] = "  {$cat['id']}: {$cat['name']}";
        }
        return implode("\n", $lines);
    }

    /**
     * Build reference code options string for prompt
     */
    protected function buildReferenceCodeOptions(): string
    {
        $codes = $this->getReferenceCodes();
        $lines = [];
        $byCategory = [];
        foreach ($codes as $code) {
            $byCategory[$code['category']][] = $code;
        }
        foreach ($byCategory as $catName => $catCodes) {
            $lines[] = "\n  {$catName}:";
            foreach ($catCodes as $code) {
                $lines[] = "    {$code['id']}: {$code['code']} - {$code['name']}";
            }
        }
        return implode("\n", $lines);
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

        $categoryOptions = $this->buildCategoryOptions();
        $referenceCodeOptions = $this->buildReferenceCodeOptions();

        return <<<PROMPT
You are a product data specialist for TCS Woodwork, a professional cabinet and furniture shop.
Search the web for accurate, real-world information about the following product.

PRIORITY SOURCES - Search these FIRST for pricing and specs:
1. Richelieu.com (primary hardware supplier)
2. Woodworker's Supply
3. Rockler.com
4. Woodcraft.com
5. Manufacturer website (Blum, Titebond, West System, etc.)
AVOID Amazon/Home Depot pricing - we need TRADE/WHOLESALE prices, not retail.

Product Name: {$productName}
{$contextSection}

AVAILABLE CATEGORIES (select ONE by ID):
{$categoryOptions}

AVAILABLE REFERENCE TYPE CODES (select ONE by ID - must match category):
{$referenceCodeOptions}

Generate product details that a PROFESSIONAL WOODWORKER would actually need to know.
NOT marketing fluff - practical shop floor information.

Return this exact JSON format:

{
    "description": "SHORT HTML description - max 5 bullet points with KEY specs only (see guidelines below)",
    "brand": "Manufacturer/brand name",
    "sku": "Manufacturer SKU or part number",
    "barcode": "UPC or EAN barcode",
    "product_type": "adhesive|hardware|finish|material|tool|consumable|safety",
    "category_id": 0,
    "reference_type_code_id": 0,
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

DESCRIPTION GUIDELINES - KEEP IT SHORT:
Use <ul><li> bullet format. MAX 5 BULLETS with only the most critical specs:

For ADHESIVES: Open time, cure time, temp requirement, water resistance
For HARDWARE: Dimensions, weight capacity, bore pattern
For FINISHES: Coverage rate, dry time, application method
For MATERIALS: Actual dimensions, core type

Example format:
<ul>
<li>Open time: 10-15 min</li>
<li>Cure: 24 hours</li>
<li>Min temp: 50°F</li>
<li>Type II water resistant</li>
</ul>

DO NOT write paragraphs. Only bullet points with key specs.

For ALL products:
- Safety/PPE requirements
- Storage requirements
- Shelf life
- Common issues to avoid

Field guidelines:
- brand: Manufacturer name (e.g., "West System", "Blum", "Titebond")
- sku: Manufacturer part number - ALWAYS TRY TO FIND (e.g., "5004", "TB-II-16", "105-B")
- barcode: UPC/EAN barcode - SEARCH HARD FOR THIS (12-13 digit number like "037083050042")
- product_type: One of: adhesive, hardware, finish, material, tool, consumable, safety
- category_id: REQUIRED - Select ID from AVAILABLE CATEGORIES above (e.g., 58 for Hardware, 53 for Adhesives)
- reference_type_code_id: REQUIRED - Select ID from AVAILABLE REFERENCE TYPE CODES above (must match category)
- suggested_price: Retail price USD - ALWAYS ESTIMATE (wood glue pint ~$8-15, quart ~$15-25, gallon ~$30-50)
- suggested_cost: Wholesale - ALWAYS ESTIMATE as 50-60% of retail price
- weight: In kg - ALWAYS ESTIMATE (1 gallon liquid ~4kg, 1 quart ~1kg)
- volume: In liters - ALWAYS ESTIMATE (1 gallon = 3.78L, 1 quart = 0.95L, 1 pint = 0.47L)
- source_url: Primary source URL for this product info

CRITICAL:
- ALWAYS select a category_id and reference_type_code_id from the lists above
- Never return 0 for price, cost, weight, or volume - always estimate
- The reference_type_code MUST belong to the selected category

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
            // Image identification (for photo-based lookups)
            'identified_product_name' => trim($data['identified_product_name'] ?? ''),

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
            'category_id' => (int) ($data['category_id'] ?? 0),
            'reference_type_code_id' => (int) ($data['reference_type_code_id'] ?? 0),

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
     * Generate product details from an image using Gemini Vision
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param string $mimeType Image mime type (image/jpeg, image/png, etc.)
     * @param string|null $additionalContext Optional text context to help identify the product
     * @return array Structured product data
     */
    public function generateProductDetailsFromImage(string $imageBase64, string $mimeType = 'image/jpeg', ?string $additionalContext = null): array
    {
        if (empty($this->geminiKey)) {
            Log::warning('GeminiProductService: No Gemini API key configured');
            return ['error' => 'Gemini API key not configured. Add GOOGLE_API_KEY to .env'];
        }

        $prompt = $this->buildImagePrompt($additionalContext);

        Log::info("GeminiProductService: Generating details from image" . ($additionalContext ? " with context: {$additionalContext}" : ""));

        $maxRetries = 3;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("GeminiProductService (image): Attempt {$attempt}/{$maxRetries}");

                $response = Http::timeout(90)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->geminiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inlineData' => [
                                        'mimeType' => $mimeType,
                                        'data' => $imageBase64,
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'tools' => [
                        ['googleSearch' => new \stdClass()]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topP' => 0.95,
                        'maxOutputTokens' => 4096,
                    ],
                ]);

                if ($response->failed()) {
                    $errorBody = $response->json();
                    $errorMsg = $errorBody['error']['message'] ?? 'Unknown API error';
                    Log::error("GeminiProductService (image) API error: {$errorMsg}");

                    if ($response->status() === 429 && $attempt < $maxRetries) {
                        $waitTime = pow(2, $attempt + 1);
                        Log::info("Rate limited (image), waiting {$waitTime}s...");
                        sleep($waitTime);
                        continue;
                    }

                    return ['error' => "API error: {$errorMsg}"];
                }

                $content = $response->json('candidates.0.content.parts.0.text');
                if (empty($content)) {
                    Log::warning('GeminiProductService (image): Empty response from API');
                    return ['error' => 'No content generated from image'];
                }

                Log::info("GeminiProductService (image): Raw response received", ['length' => strlen($content)]);

                $result = $this->parseResponse($content);
                $sanitized = $this->sanitizeResponse($result);

                Log::info("GeminiProductService (image): Successfully processed image", [
                    'identified_product' => $sanitized['identified_product_name'] ?? 'unknown',
                    'brand' => $sanitized['brand'] ?? 'unknown',
                ]);

                return $sanitized;

            } catch (\Exception $e) {
                Log::error("GeminiProductService (image) error (attempt {$attempt}): " . $e->getMessage());
                $lastError = ['error' => $e->getMessage()];

                if ($attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                }
            }
        }

        Log::error('GeminiProductService (image): All attempts failed');
        return $lastError ?? ['error' => 'All attempts failed'];
    }

    /**
     * Build the prompt for image-based product identification
     */
    protected function buildImagePrompt(?string $additionalContext): string
    {
        $contextSection = '';
        if (!empty($additionalContext)) {
            $contextSection = "\nAdditional Context from User: {$additionalContext}\n";
        }

        $categoryOptions = $this->buildCategoryOptions();
        $referenceCodeOptions = $this->buildReferenceCodeOptions();

        return <<<PROMPT
You are a product identification specialist for TCS Woodwork, a professional cabinet and furniture shop.

Analyze this image and identify the product shown. Then search the web for accurate, real-world information about this product.

PRIORITY SOURCES - Search these FIRST for pricing and specs:
1. Richelieu.com (primary hardware supplier)
2. Woodworker's Supply
3. Rockler.com
4. Woodcraft.com
5. Manufacturer website (Blum, Titebond, West System, etc.)
AVOID Amazon/Home Depot pricing - we need TRADE/WHOLESALE prices, not retail.
{$contextSection}
IMPORTANT: First identify what product this is, including brand if visible. Then provide detailed information.

AVAILABLE CATEGORIES (select ONE by ID):
{$categoryOptions}

AVAILABLE REFERENCE TYPE CODES (select ONE by ID - must match category):
{$referenceCodeOptions}

DESCRIPTION MUST BE SHORT - MAX 5 BULLET POINTS:
Use <ul><li> format with only critical specs:
- ADHESIVES: Open time, cure time, temp, water resistance
- HARDWARE: Dimensions, weight capacity, bore pattern
- FINISHES: Coverage, dry time, application method
- MATERIALS: Actual dims, core type

Example:
<ul><li>Open time: 10-15 min</li><li>Cure: 24 hours</li><li>Min temp: 50°F</li></ul>

Return ONLY valid JSON with this exact structure:
{
    "identified_product_name": "Full product name as identified from image",
    "description": "SHORT bullet list - MAX 5 items with key specs only",
    "description_sale": "Brief sales-focused description",
    "description_purchase": "Purchasing notes (min order, lead time, supplier info)",
    "brand": "Manufacturer name",
    "sku": "Manufacturer part/model number if visible or findable",
    "barcode": "UPC/EAN if findable (12-13 digit number)",
    "product_type": "One of: adhesive, hardware, finish, material, tool, consumable, safety",
    "category_id": 0,
    "reference_type_code_id": 0,
    "suggested_price": 0.00,
    "suggested_cost": 0.00,
    "weight": 0.0,
    "volume": 0.0,
    "technical_specs": "Key specs as single line text",
    "tags": ["brand-name", "product-type", "material", "application"],
    "source_url": "Primary source URL"
}

CRITICAL:
- ALWAYS select a category_id and reference_type_code_id from the lists above
- The reference_type_code MUST belong to the selected category
- Always provide your best estimate for price, cost, weight, and volume based on the product type and size visible in the image
- Include the identified product name - this is essential for the user to confirm you identified it correctly

Return ONLY valid JSON, no markdown.
PROMPT;
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->geminiKey);
    }
}
