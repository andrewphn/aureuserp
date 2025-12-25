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
     * @param array $searchContext Additional search fields (supplier_sku, barcode, brand, etc.)
     * @return array Structured product data
     */
    public function generateProductDetails(string $productName, ?string $existingDescription = null, array $searchContext = []): array
    {
        if (empty($this->geminiKey)) {
            Log::warning('GeminiProductService: No Gemini API key configured');
            return ['error' => 'Gemini API key not configured. Add GOOGLE_API_KEY to .env'];
        }

        $prompt = $this->buildPrompt($productName, $existingDescription, $searchContext);

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
    protected function buildPrompt(string $productName, ?string $existingDescription, array $searchContext = []): string
    {
        $contextSection = '';
        if (!empty($existingDescription)) {
            $contextSection .= "\nExisting Description: {$existingDescription}\n";
        }

        // If source_url is provided, fetch the page content and include it
        $sourcePageContent = '';
        $sourceUrlSection = '';
        if (!empty($searchContext['source_url'])) {
            $sourceUrl = $searchContext['source_url'];

            // Different handling based on source
            if (str_contains($sourceUrl, 'amazon.com')) {
                // Amazon URL - use it to IDENTIFY product, but search Richelieu first
                $sourceUrlSection = "\n\n⚠️ AMAZON URL PROVIDED - USE TO IDENTIFY PRODUCT ONLY:\n{$sourceUrl}\n";
                $sourceUrlSection .= "\n🔍 IMPORTANT WORKFLOW:\n";
                $sourceUrlSection .= "1. Extract product name, brand, model, specs from the Amazon page below\n";
                $sourceUrlSection .= "2. FIRST search Richelieu.com for this SAME product (by name/model/brand)\n";
                $sourceUrlSection .= "3. If Richelieu has it → use Richelieu price, URL, and image\n";
                $sourceUrlSection .= "4. ONLY if Richelieu doesn't have it → use Amazon data as fallback\n";
                $sourceUrlSection .= "5. For image: prefer Richelieu image, Amazon image as fallback\n";
            } elseif (str_contains($sourceUrl, 'richelieu.com')) {
                // Richelieu URL - use directly as primary source
                $sourceUrlSection = "\n\n⚠️ RICHELIEU URL PROVIDED - USE THIS AS PRIMARY SOURCE:\n{$sourceUrl}\n";
            } else {
                // Other URL - use as reference but still check Richelieu
                $sourceUrlSection = "\n\n⚠️ SOURCE URL PROVIDED:\n{$sourceUrl}\n";
                $sourceUrlSection .= "Use this to identify the product, but ALSO check Richelieu.com for better pricing.\n";
            }

            // Fetch the page content to give Gemini the actual data
            $pageContent = $this->fetchPageContent($sourceUrl);
            if ($pageContent) {
                $sourcePageContent = "\n\n### PRODUCT PAGE CONTENT (extracted from source URL):\n" . $pageContent . "\n";
            }
        }

        // Build search context section for multi-field lookup
        $searchFields = [];
        $hasRichelieuCode = false;

        if (!empty($searchContext['supplier_sku'])) {
            $searchFields[] = "Supplier/Manufacturer SKU: {$searchContext['supplier_sku']}";
            // Check if it looks like a Richelieu code (alphanumeric patterns common for Richelieu)
            if (preg_match('/^[A-Z0-9]{5,15}$/i', $searchContext['supplier_sku'])) {
                $hasRichelieuCode = true;
            }
        }
        if (!empty($searchContext['barcode'])) {
            $searchFields[] = "Barcode/UPC: {$searchContext['barcode']}";
        }
        if (!empty($searchContext['brand'])) {
            $searchFields[] = "Brand: {$searchContext['brand']}";
        }
        if (!empty($searchContext['reference'])) {
            $searchFields[] = "Internal Reference: {$searchContext['reference']}";
        }

        $searchFieldsText = '';
        if (!empty($searchFields)) {
            $searchFieldsText = "\n\n### SEARCH WITH THESE IDENTIFIERS (use ALL of them):\n" . implode("\n", $searchFields) . "\n";
        }

        // If we have a supplier SKU, prioritize Richelieu even more
        $richelieuPriority = '';
        if ($hasRichelieuCode || !empty($searchContext['supplier_sku'])) {
            $richelieuPriority = <<<RICHELIEU

⚠️ CRITICAL: A Supplier SKU was provided. This is likely a Richelieu product code.
FIRST, search Richelieu.com directly for this SKU: {$searchContext['supplier_sku']}
URL pattern to try: https://www.richelieu.com/us/en/search?term={$searchContext['supplier_sku']}

If you find a match on Richelieu.com:
- Use the Richelieu price as the suggested_cost (this is our wholesale supplier)
- Use the Richelieu product description and specs
- Include the Richelieu URL as source_url

🖼️ IMAGE EXTRACTION - VERY IMPORTANT:
Richelieu product images are hosted on their CDN at static.richelieu.com
Common patterns:
- https://static.richelieu.com/documents/docsPr/XXXXX/YYYYYYY_300.jpg
- https://static.richelieu.com/documents/docsGr/XXXXX/YYYYYYY_300.jpg
Look for the main product image on the Richelieu product page and extract its direct URL.
The image URL should start with https://static.richelieu.com/
If you cannot find a Richelieu image, try the manufacturer's website for the product image.

RICHELIEU;
        }

        $categoryOptions = $this->buildCategoryOptions();
        $referenceCodeOptions = $this->buildReferenceCodeOptions();

        return <<<PROMPT
You are a product data specialist for TCS Woodwork, a professional cabinet and furniture shop.
{$sourceUrlSection}
{$sourcePageContent}
Product Name: {$productName}
{$searchFieldsText}{$contextSection}

PRIORITY SOURCES - If no source URL provided, search these FIRST for pricing and specs:
1. Richelieu.com (PRIMARY hardware supplier - check here FIRST)
2. Woodworker's Supply
3. Rockler.com
4. Woodcraft.com
5. Manufacturer website (Blum, Titebond, West System, etc.)
6. Amazon.com (LAST RESORT - use for product details, specs, and images)

PRICING: Use prices from whatever source you find - user will adjust later.
IMAGE: If source_url is provided, extract image from that source (Richelieu or Amazon).
{$richelieuPriority}

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
    "source_url": "URL where you found the main product info",
    "image_url": "REQUIRED: Direct URL to product image - look for richelieu CDN images like images.richelieu.com or manufacturer images. Must be a valid image URL ending in .jpg, .png, .webp"
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

            // Pricing (unit pricing)
            'suggested_price' => $this->sanitizeNumber($data['suggested_price'] ?? 0),
            'suggested_cost' => $this->sanitizeNumber($data['suggested_cost'] ?? 0),

            // Box/Package pricing (for bulk purchasing)
            'box_cost' => $this->sanitizeNumber($data['box_cost'] ?? 0),
            'units_per_box' => (int) ($data['units_per_box'] ?? 0),
            'package_description' => trim($data['package_description'] ?? ''),

            // Size/Variant info
            'size_variant' => trim($data['size_variant'] ?? ''),

            // Physical properties
            'weight' => $this->sanitizeNumber($data['weight'] ?? 0),
            'volume' => $this->sanitizeNumber($data['volume'] ?? 0),

            // Additional info
            'technical_specs' => trim($data['technical_specs'] ?? ''),
            'tags' => is_array($data['tags'] ?? null) ? $data['tags'] : [],
            'source_url' => trim($data['source_url'] ?? ''),
            'image_url' => trim($data['image_url'] ?? ''),

            // Suggested attributes for variant creation
            'suggested_attributes' => is_array($data['suggested_attributes'] ?? null) ? $data['suggested_attributes'] : [],
        ];
    }

    /**
     * Extract GPS coordinates from image EXIF data
     *
     * @param string $imagePath Full path to image file
     * @return array|null Array with 'latitude' and 'longitude', or null if not available
     */
    public static function extractGpsFromImage(string $imagePath): ?array
    {
        if (!file_exists($imagePath)) {
            return null;
        }

        // Check if EXIF extension is available
        if (!function_exists('exif_read_data')) {
            Log::warning('GeminiProductService: EXIF extension not available for GPS extraction');
            return null;
        }

        try {
            $exif = @exif_read_data($imagePath, 'GPS');
            if (!$exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
                return null;
            }

            // Convert GPS coordinates from DMS to decimal
            $lat = self::gpsToDecimal(
                $exif['GPSLatitude'],
                $exif['GPSLatitudeRef'] ?? 'N'
            );
            $lng = self::gpsToDecimal(
                $exif['GPSLongitude'],
                $exif['GPSLongitudeRef'] ?? 'W'
            );

            if ($lat === null || $lng === null) {
                return null;
            }

            Log::info('GeminiProductService: Extracted GPS from image', [
                'latitude' => $lat,
                'longitude' => $lng,
            ]);

            return [
                'latitude' => $lat,
                'longitude' => $lng,
            ];

        } catch (\Exception $e) {
            Log::warning('GeminiProductService: Error reading EXIF data', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Convert GPS coordinates from DMS (degrees, minutes, seconds) to decimal
     */
    protected static function gpsToDecimal(array $coordinate, string $hemisphere): ?float
    {
        if (count($coordinate) !== 3) {
            return null;
        }

        $degrees = self::gpsRationalToFloat($coordinate[0]);
        $minutes = self::gpsRationalToFloat($coordinate[1]);
        $seconds = self::gpsRationalToFloat($coordinate[2]);

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        // South and West are negative
        if (strtoupper($hemisphere) === 'S' || strtoupper($hemisphere) === 'W') {
            $decimal = -$decimal;
        }

        return round($decimal, 8);
    }

    /**
     * Convert GPS rational number (fraction string) to float
     */
    protected static function gpsRationalToFloat($rational): ?float
    {
        if (is_numeric($rational)) {
            return (float) $rational;
        }

        if (is_string($rational) && str_contains($rational, '/')) {
            $parts = explode('/', $rational);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1]) && $parts[1] != 0) {
                return (float) $parts[0] / (float) $parts[1];
            }
        }

        return null;
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
        $attributeOptions = $this->buildAttributeOptions();

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

SIZE/VARIANT DETECTION:
- Look for size markings on product or packaging (mm, inches, oz, ml, grit numbers, etc.)
- For hinges: Note the arm length (52mm, 578mm), cup size (35mm, 40mm)
- For fasteners: Note length, gauge, drive type
- For adhesives/finishes: Note container size (oz, gallon, etc.)
- For sanding: Note grit numbers, belt/disc sizes
- Include the specific size/variant in the product name

PACKAGING DETECTION:
- Look for "Box of X", "Case of X", packaging quantity labels
- If you can identify box/bulk pricing vs unit pricing, provide both

AVAILABLE CATEGORIES (select ONE by ID):
{$categoryOptions}

AVAILABLE REFERENCE TYPE CODES (select ONE by ID - must match category):
{$referenceCodeOptions}

AVAILABLE PRODUCT ATTRIBUTES (suggest only relevant ones):
{$attributeOptions}

Only suggest attributes that are DIRECTLY RELEVANT to this product type.
For hinges: Size is relevant (52mm, 578mm, etc.)
For sandpaper: Grit is relevant (80, 120, 220, etc.)
For glue: Pack Size is relevant (oz, gallon)
Do NOT suggest Brand as an attribute (it goes in the brand field instead)
If an existing option matches, use its ID. If not, suggest a NEW option name.

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
    "identified_product_name": "Full product name INCLUDING size/variant (e.g. 'Blum Compact 33 Hinge 578mm')",
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
    "box_cost": 0.00,
    "units_per_box": 0,
    "package_description": "e.g. 'Box of 100', 'Case of 12', 'Single unit'",
    "size_variant": "Specific size/variant (e.g. '578mm', '16oz', '120 grit')",
    "weight": 0.0,
    "volume": 0.0,
    "technical_specs": "Key specs as single line text",
    "tags": ["brand-name", "product-type", "material", "application", "size-variant"],
    "source_url": "Primary source URL",
    "image_url": "Direct URL to high-quality product image from manufacturer or supplier website",
    "suggested_attributes": [
        {
            "attribute_id": 8,
            "attribute_name": "Size",
            "option_id": 0,
            "option_name": "578mm"
        }
    ]
}

CRITICAL:
- ALWAYS select a category_id and reference_type_code_id from the lists above
- The reference_type_code MUST belong to the selected category
- Include the SIZE/VARIANT in the product name (this helps distinguish products like "Hinge 578mm" vs "Hinge 52mm")
- If you detect packaging info, provide box_cost and units_per_box so we can calculate unit cost
- Always provide your best estimate for price, cost, weight, and volume based on the product type and size visible in the image

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

    /**
     * Get relevant product attributes for AI to suggest
     * Only returns attributes commonly used for consumable/hardware products
     */
    protected function getRelevantAttributes(): array
    {
        return Cache::remember('ai_product_attributes', 3600, function () {
            // Only get attributes that make sense for consumables/hardware
            $relevantNames = ['Size', 'Brand', 'Color', 'Finish', 'Length', 'Width', 'Pack Size', 'Grit', 'Type'];

            $attributes = DB::table('products_attributes')
                ->whereIn('name', $relevantNames)
                ->whereNull('deleted_at')
                ->select('id', 'name', 'type')
                ->get();

            $result = [];
            foreach ($attributes as $attr) {
                $options = DB::table('products_attribute_options')
                    ->where('attribute_id', $attr->id)
                    ->select('id', 'name', 'extra_price')
                    ->orderBy('sort')
                    ->get()
                    ->map(fn($o) => [
                        'id' => $o->id,
                        'name' => $o->name,
                        'extra_price' => (float) $o->extra_price,
                    ])
                    ->toArray();

                $result[] = [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'type' => $attr->type,
                    'options' => $options,
                ];
            }
            return $result;
        });
    }

    /**
     * Build attribute options string for AI prompt
     */
    protected function buildAttributeOptions(): string
    {
        $attributes = $this->getRelevantAttributes();
        if (empty($attributes)) {
            return "No attributes available.";
        }

        $lines = [];
        foreach ($attributes as $attr) {
            $optionNames = array_map(fn($o) => $o['name'], $attr['options']);
            $optionsStr = empty($optionNames) ? '(no predefined options)' : implode(', ', array_slice($optionNames, 0, 10));
            $lines[] = "- {$attr['name']} (id={$attr['id']}): {$optionsStr}";
        }
        return implode("\n", $lines);
    }

    /**
     * Find existing products that might match the AI-identified product
     * This helps prevent creating duplicate products/variants
     *
     * @param string $identifiedName The product name identified by AI
     * @param string|null $brand Brand name if available
     * @param string|null $sku SKU if available
     * @param array $suggestedAttributes Attributes suggested by AI
     * @return array Array of potential matches with relevance info
     */
    public static function findSimilarProducts(
        string $identifiedName,
        ?string $brand = null,
        ?string $sku = null,
        array $suggestedAttributes = []
    ): array {
        $matches = [];

        // Clean the name for searching
        $searchName = trim(preg_replace('/\s+/', ' ', $identifiedName));
        $words = array_filter(explode(' ', strtolower($searchName)));

        // Remove common words that don't help matching
        $stopWords = ['the', 'a', 'an', 'for', 'with', 'and', 'or', 'mm', 'inch', 'oz', 'lb'];
        $searchWords = array_diff($words, $stopWords);

        // Strategy 1: Exact SKU match (highest priority)
        if (!empty($sku)) {
            $skuMatches = DB::table('products_products')
                ->where('reference', $sku)
                ->orWhere('barcode', $sku)
                ->whereNull('deleted_at')
                ->select('id', 'name', 'reference', 'barcode', 'parent_id', 'is_configurable', 'price', 'cost')
                ->limit(5)
                ->get();

            foreach ($skuMatches as $product) {
                $matches[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'reference' => $product->reference,
                    'barcode' => $product->barcode,
                    'parent_id' => $product->parent_id,
                    'is_configurable' => $product->is_configurable,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'match_type' => 'exact_sku',
                    'confidence' => 100,
                ];
            }
        }

        // Strategy 2: Brand + partial name match
        if (!empty($brand)) {
            $brandLower = strtolower($brand);
            $nameMatches = DB::table('products_products')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($brandLower, $searchWords) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$brandLower}%"]);
                    foreach (array_slice($searchWords, 0, 3) as $word) {
                        if (strlen($word) > 2) {
                            $query->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                        }
                    }
                })
                ->select('id', 'name', 'reference', 'barcode', 'parent_id', 'is_configurable', 'price', 'cost')
                ->limit(10)
                ->get();

            foreach ($nameMatches as $product) {
                // Avoid duplicates
                if (collect($matches)->where('id', $product->id)->isEmpty()) {
                    $matches[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'reference' => $product->reference,
                        'barcode' => $product->barcode,
                        'parent_id' => $product->parent_id,
                        'is_configurable' => $product->is_configurable,
                        'price' => $product->price,
                        'cost' => $product->cost,
                        'match_type' => 'brand_name',
                        'confidence' => 75,
                    ];
                }
            }
        }

        // Strategy 3: Configurable products (parents) that might be a match
        // Look for products with variants that could include this item
        $configurableMatches = DB::table('products_products')
            ->where('is_configurable', true)
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->where(function ($query) use ($searchWords) {
                foreach (array_slice($searchWords, 0, 2) as $word) {
                    if (strlen($word) > 2) {
                        $query->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                    }
                }
            })
            ->select('id', 'name', 'reference', 'barcode', 'parent_id', 'is_configurable', 'price', 'cost')
            ->limit(5)
            ->get();

        foreach ($configurableMatches as $product) {
            if (collect($matches)->where('id', $product->id)->isEmpty()) {
                // Get variant count
                $variantCount = DB::table('products_products')
                    ->where('parent_id', $product->id)
                    ->whereNull('deleted_at')
                    ->count();

                $matches[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'reference' => $product->reference,
                    'barcode' => $product->barcode,
                    'parent_id' => $product->parent_id,
                    'is_configurable' => $product->is_configurable,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'variant_count' => $variantCount,
                    'match_type' => 'configurable_parent',
                    'confidence' => 60,
                ];
            }
        }

        // Strategy 4: Fuzzy name match for any remaining products
        if (count($matches) < 5 && count($searchWords) >= 2) {
            $fuzzyMatches = DB::table('products_products')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($searchWords) {
                    foreach ($searchWords as $word) {
                        if (strlen($word) > 3) {
                            $query->orWhereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                        }
                    }
                })
                ->select('id', 'name', 'reference', 'barcode', 'parent_id', 'is_configurable', 'price', 'cost')
                ->limit(10)
                ->get();

            foreach ($fuzzyMatches as $product) {
                if (collect($matches)->where('id', $product->id)->isEmpty()) {
                    // Calculate similarity score
                    similar_text(strtolower($product->name), strtolower($identifiedName), $similarity);

                    if ($similarity > 30) {
                        $matches[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'reference' => $product->reference,
                            'barcode' => $product->barcode,
                            'parent_id' => $product->parent_id,
                            'is_configurable' => $product->is_configurable,
                            'price' => $product->price,
                            'cost' => $product->cost,
                            'match_type' => 'fuzzy',
                            'confidence' => (int) $similarity,
                        ];
                    }
                }
            }
        }

        // Sort by confidence descending
        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        // Limit to top 10 matches
        return array_slice($matches, 0, 10);
    }

    /**
     * Get existing variants for a configurable parent product
     *
     * @param int $parentId The parent product ID
     * @return array List of variants with their attributes
     */
    public static function getProductVariants(int $parentId): array
    {
        $variants = DB::table('products_products as p')
            ->where('p.parent_id', $parentId)
            ->whereNull('p.deleted_at')
            ->select('p.id', 'p.name', 'p.reference', 'p.price', 'p.cost')
            ->get();

        $result = [];
        foreach ($variants as $variant) {
            // Get attribute values for this variant
            $attributes = DB::table('products_product_attribute_values as pav')
                ->join('products_attribute_options as ao', 'pav.attribute_option_id', '=', 'ao.id')
                ->join('products_attributes as a', 'ao.attribute_id', '=', 'a.id')
                ->where('pav.product_id', $variant->id)
                ->select('a.name as attribute_name', 'ao.name as option_name')
                ->get()
                ->mapWithKeys(fn($item) => [$item->attribute_name => $item->option_name])
                ->toArray();

            $result[] = [
                'id' => $variant->id,
                'name' => $variant->name,
                'reference' => $variant->reference,
                'price' => $variant->price,
                'cost' => $variant->cost,
                'attributes' => $attributes,
            ];
        }

        return $result;
    }

    /**
     * Find a product image from alternative sources when Richelieu direct extraction fails
     *
     * @param string $sku The product SKU to search for
     * @return string|null A valid image URL, or null if not found
     */
    public static function findAlternateProductImage(string $sku): ?string
    {
        $sku = strtoupper(trim($sku));

        if (strlen($sku) < 4) {
            return null;
        }

        Log::info('GeminiProductService: Searching alternate sources for product image', ['sku' => $sku]);

        // Try build.com - they have good product images
        $buildComUrl = "https://www.build.com/blum-{$sku}/s869746";
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get("https://www.build.com/search?term={$sku}");

            if ($response->successful()) {
                $html = $response->body();
                // Look for product images
                if (preg_match('/src="(https:\/\/[^"]*img-b\.com[^"]*' . preg_quote($sku, '/') . '[^"]*\.(?:jpg|png|webp))"/i', $html, $matches)) {
                    Log::info('GeminiProductService: Found image from build.com', ['image_url' => $matches[1]]);
                    return $matches[1];
                }
            }
        } catch (\Exception $e) {
            Log::debug('GeminiProductService: build.com search failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Extract the product image URL from a Richelieu product page
     *
     * @param string $pageUrl The Richelieu product page URL
     * @return string|null The extracted image URL, or null if not found
     */
    public static function extractRichelieuImageUrl(string $pageUrl): ?string
    {
        if (empty($pageUrl) || !str_contains($pageUrl, 'richelieu.com')) {
            return null;
        }

        try {
            Log::info('GeminiProductService: Extracting image from Richelieu page', ['url' => $pageUrl]);

            // Extract SKU from URL first - we'll need it for direct image URL construction
            // Check patterns in order of specificity (sku= or sku- first, then term=, then generic)
            $sku = null;
            if (preg_match('/sku[=-]([A-Z0-9]{6,15})/i', $pageUrl, $skuMatch) ||
                preg_match('/term=([A-Z0-9]{6,15})/i', $pageUrl, $skuMatch) ||
                preg_match('/\/([A-Z][A-Z0-9]{5,14})(?:$|[\/\?])/', $pageUrl, $skuMatch)) {
                $sku = strtoupper($skuMatch[1]);
                Log::info('GeminiProductService: Extracted SKU from URL', ['sku' => $sku]);
            }

            // NOTE: Richelieu image URLs use internal product group IDs, NOT SKUs
            // We must fetch the product page to find the actual image URL
            // The image URL pattern is: static.richelieu.com/documents/docsGr/{groupId}/.../{imageId}_700.jpg

            // Use ScrapeOps for reliable fetching
            $apiKey = config('services.scrapeops.api_key');
            if (!empty($apiKey)) {
                Log::info('GeminiProductService: Using ScrapeOps for Richelieu page', ['url' => $pageUrl]);
                $response = Http::timeout(60)
                    ->get('https://proxy.scrapeops.io/v1/', [
                        'api_key' => $apiKey,
                        'url' => $pageUrl,
                        'render_js' => 'false',
                    ]);
            } else {
                // Fallback to direct fetch if no API key
                $response = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.5',
                    ])
                    ->get($pageUrl);
            }

            if (!$response->successful()) {
                Log::warning('GeminiProductService: Failed to fetch Richelieu page', [
                    'url' => $pageUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $html = $response->body();

            // If we have a SKU, try to find an image URL that contains it
            if ($sku) {
                // PRIORITY: Check og:image meta tag first - contains best quality product image
                $ogImagePattern = '/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']*static\.richelieu\.com[^"\']*' . preg_quote($sku, '/') . '[^"\']*\.(?:jpg|jpeg|png|webp))["\']/' ;
                if (preg_match($ogImagePattern, $html, $matches)) {
                    $imageUrl = $matches[1];
                    // Try to get _hd version (highest quality)
                    if (str_contains($imageUrl, '_700.') || str_contains($imageUrl, '_300.')) {
                        $hdUrl = preg_replace('/_(700|300)\./', '_hd.', $imageUrl);
                        $checkResponse = Http::timeout(5)->head($hdUrl);
                        if ($checkResponse->successful()) {
                            $imageUrl = $hdUrl;
                        }
                    }
                    Log::info('GeminiProductService: Found Richelieu og:image matching SKU', [
                        'sku' => $sku,
                        'image_url' => $imageUrl,
                    ]);
                    return $imageUrl;
                }

                // Look for image URLs containing our SKU in src attributes
                $skuPattern = '/src=["\']([^"\']*static\.richelieu\.com[^"\']*' . preg_quote($sku, '/') . '[^"\']*\.(?:jpg|jpeg|png|webp))["\']/' ;
                if (preg_match($skuPattern, $html, $matches)) {
                    $imageUrl = $matches[1];
                    if (str_starts_with($imageUrl, '//')) {
                        $imageUrl = 'https:' . $imageUrl;
                    }
                    Log::info('GeminiProductService: Found Richelieu image matching SKU', [
                        'sku' => $sku,
                        'image_url' => $imageUrl,
                    ]);
                    return $imageUrl;
                }

                // Also check data-src for lazy loaded images
                $dataSrcPattern = '/data-src=["\']([^"\']*static\.richelieu\.com[^"\']*' . preg_quote($sku, '/') . '[^"\']*\.(?:jpg|jpeg|png|webp))["\']/';
                if (preg_match($dataSrcPattern, $html, $matches)) {
                    $imageUrl = $matches[1];
                    if (str_starts_with($imageUrl, '//')) {
                        $imageUrl = 'https:' . $imageUrl;
                    }
                    Log::info('GeminiProductService: Found Richelieu lazy-loaded image matching SKU', [
                        'sku' => $sku,
                        'image_url' => $imageUrl,
                    ]);
                    return $imageUrl;
                }
            }

            // If this is a search results page, try to find a link to the actual product page
            // Richelieu product URLs have the pattern: /sku-{SKU} at the end
            if (str_contains($pageUrl, '/search?') || str_contains($pageUrl, '/search/')) {
                Log::info('GeminiProductService: Detected search page, looking for product link with SKU');

                $productPageUrl = null;

                // Look for product page links that contain our SKU
                if ($sku) {
                    // Pattern: href="...sku-{SKU}" or href=".../{SKU}"
                    $skuLinkPattern = '/href=["\']([^"\']*\/sku-' . preg_quote($sku, '/') . ')["\']|href=["\']([^"\']*\/' . preg_quote($sku, '/') . ')["\']/' ;
                    if (preg_match($skuLinkPattern, $html, $matches)) {
                        $productPageUrl = $matches[1] ?: $matches[2];
                        if (str_starts_with($productPageUrl, '/')) {
                            $productPageUrl = 'https://www.richelieu.com' . $productPageUrl;
                        }
                        Log::info('GeminiProductService: Found product page link with SKU', ['url' => $productPageUrl]);
                    }
                }

                // If no SKU-specific link found, search results are JS-rendered - try alternate sources
                if (!$productPageUrl && $sku) {
                    Log::info('GeminiProductService: Search results are JS-rendered, trying alternate image source');
                    $alternateImage = self::findAlternateProductImage($sku);
                    if ($alternateImage) {
                        return $alternateImage;
                    }
                }

                // If we found a product page, fetch it
                if ($productPageUrl) {
                    $productResponse = Http::timeout(30)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        ])
                        ->get($productPageUrl);

                    if ($productResponse->successful()) {
                        $html = $productResponse->body();
                        $pageUrl = $productPageUrl;
                        Log::info('GeminiProductService: Fetched product page successfully');
                    } else {
                        Log::warning('GeminiProductService: Failed to fetch product page', [
                            'url' => $productPageUrl,
                            'status' => $productResponse->status(),
                        ]);
                    }
                }
            }

            // Try multiple patterns to find the product image
            // Order matters - docsPr (product-specific) takes priority over docsGr (group)
            // Note: Some URLs are relative (start with docsPr/) while others are absolute (static.richelieu.com)
            $patterns = [
                // PRIORITY 1: og:image meta tag with docsPr (best quality product image)
                '/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']*static\.richelieu\.com\/documents\/docsPr[^"\']+)["\']/',
                '/<meta\s+content=["\']([^"\']*static\.richelieu\.com\/documents\/docsPr[^"\']+)["\']\s+property=["\']og:image["\']/',
                // PRIORITY 2: Product-specific images with _hd or _700 suffix (highest quality) - relative or absolute
                '/src=["\'](?:https?:\/\/static\.richelieu\.com\/documents\/)?(docsPr\/[^"\']+(?:_hd|_700)\.(?:jpg|jpeg|png|webp))["\']/',
                '/data-src=["\'](?:https?:\/\/static\.richelieu\.com\/documents\/)?(docsPr\/[^"\']+(?:_hd|_700)\.(?:jpg|jpeg|png|webp))["\']/',
                // PRIORITY 3: Product-specific images (docsPr) - any size
                '/src=["\'](?:https?:\/\/static\.richelieu\.com\/documents\/)?(docsPr\/[^"\']+\.(?:jpg|jpeg|png|webp))["\']/',
                '/data-src=["\'](?:https?:\/\/static\.richelieu\.com\/documents\/)?(docsPr\/[^"\']+\.(?:jpg|jpeg|png|webp))["\']/',
                // PRIORITY 4: Absolute URL with docsPr
                '/src=["\']([^"\']*static\.richelieu\.com\/documents\/docsPr[^"\']+\.(?:jpg|jpeg|png|webp))["\']/',
                // PRIORITY 5: og:image with any documents path (fallback)
                '/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']*static\.richelieu\.com\/documents[^"\']+)["\']/',
                // PRIORITY 6: Group images (docsGr) - fallback only
                '/src=["\'](?:https?:\/\/static\.richelieu\.com\/documents\/)?(docsGr\/[^"\']+\.(?:jpg|jpeg|png|webp))["\']/',
                '/data-src=["\'](?:https?:\/\/static\.richelieu\.com\/documents\/)?(docsGr\/[^"\']+\.(?:jpg|jpeg|png|webp))["\']/',
                // PRIORITY 7: Product image with size suffix
                '/src=["\']([^"\']*static\.richelieu\.com[^"\']*(?:_800|_600|_300|_veryBig)\.(?:jpg|jpeg|png|webp))["\']/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $imageUrl = $matches[1];

                    // Clean up the URL if needed
                    if (str_starts_with($imageUrl, '//')) {
                        $imageUrl = 'https:' . $imageUrl;
                    } elseif (str_starts_with($imageUrl, 'docsPr/') || str_starts_with($imageUrl, 'docsGr/')) {
                        // Handle relative URLs - prepend Richelieu CDN base
                        $imageUrl = 'https://static.richelieu.com/documents/' . $imageUrl;
                    }

                    // Skip if it's clearly a logo or icon
                    if (str_contains($imageUrl, 'Logo') || str_contains($imageUrl, 'logo') ||
                        str_contains($imageUrl, 'icon') || str_contains($imageUrl, 'Icon') ||
                        str_contains($imageUrl, '/images/') || str_contains($imageUrl, 'filiales')) {
                        Log::info('GeminiProductService: Skipping logo/icon image', ['url' => $imageUrl]);
                        continue;
                    }

                    // Prefer larger image sizes - try to get _hd or _800 version
                    // Order of preference: _hd > _800 > _700 > _300
                    if (str_contains($imageUrl, '_700.') || str_contains($imageUrl, '_300.')) {
                        // Try _hd first (highest quality)
                        $hdUrl = preg_replace('/_(700|300)\./', '_hd.', $imageUrl);
                        $checkResponse = Http::timeout(5)->head($hdUrl);
                        if ($checkResponse->successful()) {
                            $imageUrl = $hdUrl;
                        } elseif (str_contains($imageUrl, '_300.')) {
                            // Fallback to _800 for _300 images
                            $largerUrl = str_replace('_300.', '_800.', $imageUrl);
                            $checkResponse = Http::timeout(5)->head($largerUrl);
                            if ($checkResponse->successful()) {
                                $imageUrl = $largerUrl;
                            }
                        }
                    }

                    Log::info('GeminiProductService: Found Richelieu product image', [
                        'page_url' => $pageUrl,
                        'image_url' => $imageUrl,
                    ]);

                    return $imageUrl;
                }
            }

            Log::warning('GeminiProductService: No product image found on Richelieu page', ['url' => $pageUrl]);
            return null;

        } catch (\Exception $e) {
            Log::error('GeminiProductService: Error extracting Richelieu image', [
                'url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract high-quality product image URL from an Amazon product page using ScrapeOps API
     *
     * @param string $pageUrl The Amazon product page URL
     * @return string|null The extracted image URL, or null if not found
     */
    public static function extractAmazonImageUrl(string $pageUrl): ?string
    {
        if (empty($pageUrl) || !str_contains($pageUrl, 'amazon.com')) {
            return null;
        }

        try {
            Log::info('GeminiProductService: Extracting image from Amazon via ScrapeOps', ['url' => $pageUrl]);

            $apiKey = config('services.scrapeops.api_key');
            if (empty($apiKey)) {
                Log::warning('GeminiProductService: ScrapeOps API key not configured');
                return null;
            }

            // Use ScrapeOps Proxy API to fetch the Amazon page
            $response = Http::timeout(60)
                ->get('https://proxy.scrapeops.io/v1/', [
                    'api_key' => $apiKey,
                    'url' => $pageUrl,
                    'render_js' => 'false', // Don't need JS rendering for images
                ]);

            if (!$response->successful()) {
                Log::warning('GeminiProductService: ScrapeOps failed to fetch Amazon page', [
                    'url' => $pageUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $html = $response->body();

            // Priority 1: hiRes JSON field (highest quality main product image)
            if (preg_match('/"hiRes":"([^"]+)"/', $html, $matches)) {
                $imageUrl = stripslashes($matches[1]);
                Log::info('GeminiProductService: Found Amazon hiRes image via ScrapeOps', ['image_url' => $imageUrl]);
                return $imageUrl;
            }

            // Priority 2: data-old-hires attribute (fallback high-res)
            if (preg_match('/data-old-hires="([^"]+)"/', $html, $matches)) {
                $imageUrl = $matches[1];
                Log::info('GeminiProductService: Found Amazon data-old-hires via ScrapeOps', ['image_url' => $imageUrl]);
                return $imageUrl;
            }

            // Priority 3: mainUrl JSON field
            if (preg_match('/"mainUrl":"([^"]+)"/', $html, $matches)) {
                $imageUrl = stripslashes($matches[1]);
                Log::info('GeminiProductService: Found Amazon mainUrl via ScrapeOps', ['image_url' => $imageUrl]);
                return $imageUrl;
            }

            // Priority 4: Main product image (landingImage)
            if (preg_match('/id=["\']landingImage["\'][^>]*src=["\']([^"\']+)["\']/', $html, $matches)) {
                $imageUrl = $matches[1];
                // Upgrade to larger size
                $imageUrl = preg_replace('/\._[A-Z]{2}_[A-Z0-9_]+_\./', '._AC_SL1500_.', $imageUrl);
                Log::info('GeminiProductService: Found Amazon landing image via ScrapeOps', ['image_url' => $imageUrl]);
                return $imageUrl;
            }

            // Priority 5: og:image meta tag (may not be main product image)
            if (preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/', $html, $matches)) {
                $imageUrl = $matches[1];
                $imageUrl = preg_replace('/\._[A-Z]{2}_[A-Z0-9_]+_\./', '._AC_SL1500_.', $imageUrl);
                Log::info('GeminiProductService: Found Amazon og:image via ScrapeOps', ['image_url' => $imageUrl]);
                return $imageUrl;
            }

            Log::warning('GeminiProductService: No product image found on Amazon page via ScrapeOps', ['url' => $pageUrl]);
            return null;

        } catch (\Exception $e) {
            Log::error('GeminiProductService: Error extracting Amazon image via ScrapeOps', [
                'url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Download an image from URL and save to product images directory
     *
     * @param string $imageUrl URL to download image from
     * @param string|null $productName Optional product name for filename
     * @return string|null Relative path to saved image, or null if failed
     */
    public static function downloadProductImage(string $imageUrl, ?string $productName = null): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        try {
            // Validate URL
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                Log::warning('GeminiProductService: Invalid image URL', ['url' => $imageUrl]);
                return null;
            }

            // Download image with timeout
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; TCSWoodwork/1.0)',
                    'Accept' => 'image/*',
                ])
                ->get($imageUrl);

            if (!$response->successful()) {
                Log::warning('GeminiProductService: Failed to download image', [
                    'url' => $imageUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            // Get content type and validate it's an image
            $contentType = $response->header('Content-Type') ?? '';
            if (!str_starts_with($contentType, 'image/')) {
                Log::warning('GeminiProductService: URL is not an image', [
                    'url' => $imageUrl,
                    'content_type' => $contentType,
                ]);
                return null;
            }

            // Determine file extension
            $extension = match (true) {
                str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg') => 'jpg',
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            // Generate filename
            $slug = $productName ? preg_replace('/[^a-z0-9]+/', '-', strtolower($productName)) : 'product';
            $slug = substr($slug, 0, 50); // Limit length
            $filename = $slug . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $extension;

            // Ensure directory exists - use 'products/images/' to match FileUpload directory config
            $directory = storage_path('app/public/products/images');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save image
            $fullPath = $directory . '/' . $filename;
            file_put_contents($fullPath, $response->body());

            Log::info('GeminiProductService: Downloaded product image', [
                'url' => $imageUrl,
                'saved_to' => $filename,
            ]);

            // Return just the filename - FileUpload's directory() setting handles the path
            return $filename;

        } catch (\Exception $e) {
            Log::error('GeminiProductService: Error downloading image', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch and extract relevant content from a product page URL
     * Used to give Gemini the actual page content when user provides source_url
     * Uses ScrapeOps for Amazon URLs to bypass anti-bot protection
     *
     * @param string $url The product page URL
     * @return string|null Extracted text content, or null on failure
     */
    protected function fetchPageContent(string $url): ?string
    {
        try {
            Log::info('GeminiProductService: Fetching page content', ['url' => $url]);

            // Use ScrapeOps for Amazon and Richelieu URLs
            $apiKey = config('services.scrapeops.api_key');
            $useScrapeOps = !empty($apiKey) && (str_contains($url, 'amazon.com') || str_contains($url, 'richelieu.com'));

            if ($useScrapeOps) {
                $source = str_contains($url, 'amazon.com') ? 'Amazon' : 'Richelieu';
                Log::info("GeminiProductService: Using ScrapeOps for {$source} page", ['url' => $url]);
                $response = Http::timeout(60)
                    ->get('https://proxy.scrapeops.io/v1/', [
                        'api_key' => $apiKey,
                        'url' => $url,
                        'render_js' => 'false',
                    ]);
            } elseif (str_contains($url, 'amazon.com') && empty($apiKey)) {
                Log::warning('GeminiProductService: ScrapeOps API key not configured, skipping Amazon');
                return null;
            } else {
                // Direct fetch for other URLs
                $response = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.5',
                    ])
                    ->get($url);
            }

            if (!$response->successful()) {
                Log::warning('GeminiProductService: Failed to fetch page', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $html = $response->body();

            // Extract key product information from HTML

            // Get page title
            $title = '';
            if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
                $title = trim(html_entity_decode($matches[1]));
            }

            // Get meta description
            $metaDesc = '';
            if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/', $html, $matches) ||
                preg_match('/<meta[^>]*content=["\']([^"\']+)["\'][^>]*name=["\']description["\']/', $html, $matches)) {
                $metaDesc = trim(html_entity_decode($matches[1]));
            }

            // Get og:image for reference
            $ogImage = '';
            if (preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/', $html, $matches)) {
                $ogImage = $matches[1];
            }

            // Extract price information
            $prices = [];
            // Look for common price patterns
            if (preg_match_all('/\$[\d,]+\.?\d{0,2}/', $html, $priceMatches)) {
                $prices = array_unique($priceMatches[0]);
            }

            // For Richelieu, look for specific price elements and product data
            if (str_contains($url, 'richelieu.com')) {
                // Extract JSON-LD product data (contains price, name, sku, description)
                if (preg_match('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>([^<]+)<\/script>/i', $html, $jsonMatch)) {
                    $jsonData = json_decode($jsonMatch[1], true);
                    if ($jsonData) {
                        if (!empty($jsonData['name'])) {
                            $specs[] = 'Product Name: ' . $jsonData['name'];
                        }
                        if (!empty($jsonData['sku'])) {
                            $specs[] = 'Richelieu SKU: ' . $jsonData['sku'];
                        }
                        if (!empty($jsonData['description'])) {
                            $specs[] = 'Description: ' . substr(strip_tags($jsonData['description']), 0, 500);
                        }
                        if (!empty($jsonData['offers']['price'])) {
                            $prices[] = '$' . $jsonData['offers']['price'];
                        }
                        if (!empty($jsonData['brand']['name'])) {
                            $specs[] = 'Brand: ' . $jsonData['brand']['name'];
                        }
                    }
                }

                // Price from data attributes
                if (preg_match('/"price":([0-9.]+)/', $html, $matches)) {
                    $prices[] = '$' . $matches[1];
                }
                if (preg_match('/data-price=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $prices[] = '$' . $matches[1];
                }

                // Extract SKU from URL
                if (preg_match('/sku-([A-Z0-9]+)/i', $url, $skuMatch)) {
                    $specs[] = 'SKU: ' . strtoupper($skuMatch[1]);
                }

                // Related documents (PDFs, installation guides)
                $relatedDocs = [];
                if (preg_match_all('/href=["\']([^"\']*\.pdf)["\'][^>]*>([^<]*)/i', $html, $pdfMatches, PREG_SET_ORDER)) {
                    foreach ($pdfMatches as $pdf) {
                        $docName = trim(strip_tags($pdf[2])) ?: 'Document';
                        $relatedDocs[] = $docName . ': ' . $pdf[1];
                    }
                }
                if (!empty($relatedDocs)) {
                    $specs[] = 'Related Documents: ' . implode('; ', array_slice($relatedDocs, 0, 5));
                }

                // Catalog references
                if (preg_match('/catalog=([0-9]+)/', $html, $catMatch)) {
                    $specs[] = 'Richelieu Catalog ID: ' . $catMatch[1];
                }

                // Product specifications from the page (look for spec tables)
                if (preg_match_all('/<li[^>]*class=["\'][^"\']*spec[^"\']*["\'][^>]*>([^<]+)/i', $html, $specMatches)) {
                    foreach ($specMatches[1] as $spec) {
                        $spec = trim($spec);
                        if (strlen($spec) > 5 && strlen($spec) < 200) {
                            $specs[] = $spec;
                        }
                    }
                }
            }

            // For Amazon, look for specific price and product elements
            if (str_contains($url, 'amazon.com')) {
                // Amazon price selectors
                if (preg_match('/class=["\'].*?a-price-whole["\'][^>]*>([^<]+)/', $html, $matches)) {
                    $prices[] = '$' . trim($matches[1]);
                }
                if (preg_match('/id=["\']priceblock_ourprice["\'][^>]*>([^<]+)/', $html, $matches)) {
                    $prices[] = trim($matches[1]);
                }
                if (preg_match('/id=["\']corePriceDisplay_desktop_feature_div["\'].*?\$[\d,.]+/s', $html, $matches)) {
                    if (preg_match('/\$[\d,.]+/', $matches[0], $priceMatch)) {
                        $prices[] = $priceMatch[0];
                    }
                }

                // Amazon product features/bullet points
                if (preg_match_all('/<span class=["\']a-list-item["\'][^>]*>([^<]+)<\/span>/i', $html, $featureMatches)) {
                    foreach ($featureMatches[1] as $feature) {
                        $feature = trim($feature);
                        if (strlen($feature) > 10 && strlen($feature) < 500) {
                            $specs[] = $feature;
                        }
                    }
                }

                // Amazon product details table
                if (preg_match_all('/<th[^>]*class=["\'].*?prodDetSectionEntry["\'][^>]*>([^<]+)<\/th>\s*<td[^>]*>([^<]+)<\/td>/i', $html, $detailMatches, PREG_SET_ORDER)) {
                    foreach ($detailMatches as $match) {
                        $specs[] = trim($match[1]) . ': ' . trim($match[2]);
                    }
                }

                // Amazon ASIN
                if (preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $asinMatch)) {
                    $specs[] = 'ASIN: ' . $asinMatch[1];
                }
            }

            // Extract product specs - look for common patterns
            // Look for definition lists or spec tables
            if (preg_match_all('/<dt[^>]*>([^<]+)<\/dt>\s*<dd[^>]*>([^<]+)<\/dd>/i', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $specs[] = trim($match[1]) . ': ' . trim($match[2]);
                }
            }
            // Look for table rows with specs
            if (preg_match_all('/<tr[^>]*>\s*<t[dh][^>]*>([^<]+)<\/t[dh]>\s*<t[dh][^>]*>([^<]+)<\/t[dh]>/i', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $label = trim(strip_tags($match[1]));
                    $value = trim(strip_tags($match[2]));
                    if (strlen($label) > 2 && strlen($label) < 50 && strlen($value) > 0 && strlen($value) < 200) {
                        $specs[] = $label . ': ' . $value;
                    }
                }
            }

            // Build structured content
            $content = "Page Title: {$title}\n";
            if ($metaDesc) {
                $content .= "Description: {$metaDesc}\n";
            }
            if ($ogImage) {
                $content .= "Product Image URL: {$ogImage}\n";
            }
            if (!empty($prices)) {
                $content .= "Prices found: " . implode(', ', array_slice($prices, 0, 5)) . "\n";
            }
            if (!empty($specs)) {
                $content .= "Specifications:\n" . implode("\n", array_slice($specs, 0, 20)) . "\n";
            }

            // Limit content length for prompt
            if (strlen($content) > 4000) {
                $content = substr($content, 0, 4000) . '... [truncated]';
            }

            Log::info('GeminiProductService: Extracted page content', [
                'url' => $url,
                'content_length' => strlen($content),
            ]);

            return $content;

        } catch (\Exception $e) {
            Log::error('GeminiProductService: Error fetching page content', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
