<?php

namespace App\Services;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered PDF parsing service
 * Supports Gemini (default, cheaper) and Claude (fallback)
 * Extracts structured data from architectural drawing PDFs
 *
 * Gemini supports native PDF vision - can analyze images directly without OCR
 */
class AiPdfParsingService
{
    protected ?string $geminiKey;
    protected ?string $anthropicKey;
    protected string $provider = 'gemini'; // default to cheaper option

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
        $this->anthropicKey = config('services.anthropic.api_key') ?? env('ANTHROPIC_API_KEY');

        // Auto-select provider based on available keys
        if (!empty($this->geminiKey)) {
            $this->provider = 'gemini';
        } elseif (!empty($this->anthropicKey)) {
            $this->provider = 'anthropic';
        }
    }

    /**
     * Set which AI provider to use
     */
    public function useProvider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Classify all pages in a PDF document using Gemini's native PDF vision
     * This uploads the entire PDF and classifies all pages at once
     */
    public function classifyDocumentPages(PdfDocument $document): array
    {
        $filePath = $document->file_path;

        // Try to get the full path
        if (Storage::disk('public')->exists($filePath)) {
            $fullPath = Storage::disk('public')->path($filePath);
        } elseif (file_exists(storage_path('app/public/' . $filePath))) {
            $fullPath = storage_path('app/public/' . $filePath);
        } elseif (file_exists($filePath)) {
            $fullPath = $filePath;
        } else {
            Log::error("PDF file not found: {$filePath}");
            return ['error' => "PDF file not found: {$filePath}"];
        }

        $pageCount = $document->pages()->count();

        $prompt = <<<PROMPT
You are analyzing an architectural cabinet drawing PDF with {$pageCount} pages.
For EACH page in this PDF, classify it and extract key information.

Return a JSON array with one entry per page:
[
    {
        "page_number": 1,
        "primary_purpose": "cover|floor_plan|elevations|countertops|reference|other",
        "page_label": "Descriptive name like 'Cover Page', 'Kitchen Floor Plan', 'Sink Wall', 'Island Elevations'",
        "confidence": 0.95,
        "rooms_mentioned": ["Kitchen", "Pantry"],
        "locations_mentioned": ["Sink Wall", "Island"],
        "has_hardware_schedule": false,
        "has_material_spec": false,
        "linear_feet": null,
        "pricing_tier": null,
        "brief_description": "One sentence about what this page shows"
    }
]

Classification guide:
- "cover" = Title page, project info, pricing summary, table of contents
- "floor_plan" = Bird's eye view showing room layout and cabinet positions
- "elevations" = Front view of cabinets/walls showing cabinet details (most common)
- "countertops" = Counter layout, cutouts, edge profiles
- "reference" = Photos, inspiration images, existing conditions
- "other" = Anything that doesn't fit above

For elevations pages, try to identify:
- The wall/location name (e.g., "Sink Wall", "Fridge Wall", "Island", "Pantry North Wall")
- Linear feet if shown
- Pricing tier/level if shown

Return ONLY the JSON array, no other text.
PROMPT;

        return $this->callGeminiWithPdf($fullPath, $prompt);
    }

    /**
     * Call Gemini API with a PDF file (native vision)
     * Includes retry logic with exponential backoff for rate limits
     */
    protected function callGeminiWithPdf(string $pdfPath, string $prompt): array
    {
        if (empty($this->geminiKey)) {
            Log::warning('AiPdfParsingService: No Gemini API key configured');
            return ['error' => 'Gemini API key not configured. Add GOOGLE_API_KEY to .env'];
        }

        if (!file_exists($pdfPath)) {
            return ['error' => "PDF file not found: {$pdfPath}"];
        }

        // Read and encode the PDF
        $pdfContent = file_get_contents($pdfPath);
        $base64Pdf = base64_encode($pdfContent);
        $fileSize = strlen($pdfContent);

        Log::info("Sending PDF to Gemini: " . basename($pdfPath) . " ({$fileSize} bytes)");

        // Models to try in order (gemini-1.5-flash has higher quotas)
        $models = ['gemini-1.5-flash', 'gemini-2.0-flash'];
        $maxRetries = 3;
        $lastError = null;

        foreach ($models as $model) {
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    Log::info("Attempt {$attempt}/{$maxRetries} with model {$model}");

                    $response = Http::timeout(180)->withHeaders([
                        'Content-Type' => 'application/json',
                    ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->geminiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'inline_data' => [
                                            'mime_type' => 'application/pdf',
                                            'data' => $base64Pdf,
                                        ]
                                    ],
                                    [
                                        'text' => $prompt
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.1,
                            'maxOutputTokens' => 8192,
                        ],
                    ]);

                    if ($response->successful()) {
                        $content = $response->json('candidates.0.content.parts.0.text');

                        Log::info("Gemini PDF response received from {$model}: " . substr($content ?? '', 0, 200) . "...");

                        if (empty($content)) {
                            Log::warning("Empty response from Gemini");
                            continue;
                        }

                        // Clean up markdown code blocks if present
                        $content = preg_replace('/^```json\s*/m', '', $content);
                        $content = preg_replace('/\s*```$/m', '', $content);
                        $content = trim($content);

                        // Try to parse as JSON
                        $decoded = json_decode($content, true);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            return $decoded;
                        }

                        Log::warning('Gemini PDF response not valid JSON: ' . json_last_error_msg());
                        return ['raw_response' => $content, 'parse_error' => json_last_error_msg()];
                    }

                    $statusCode = $response->status();
                    $errorBody = $response->json();
                    Log::warning("Gemini API error (attempt {$attempt}): status={$statusCode}");

                    // If rate limited (429), wait and retry
                    if ($statusCode === 429) {
                        $waitTime = pow(2, $attempt) * 2; // 4s, 8s, 16s
                        Log::info("Rate limited, waiting {$waitTime} seconds before retry...");
                        sleep($waitTime);
                        continue;
                    }

                    // For other errors, store and try next model
                    $lastError = [
                        'error' => 'Gemini API request failed',
                        'status' => $statusCode,
                        'model' => $model,
                        'body' => $errorBody
                    ];
                    break; // Try next model

                } catch (\Exception $e) {
                    Log::error("AiPdfParsingService Gemini PDF error (attempt {$attempt}): " . $e->getMessage());
                    $lastError = ['error' => $e->getMessage()];

                    if ($attempt < $maxRetries) {
                        sleep(pow(2, $attempt));
                    }
                }
            }
        }

        Log::error('All Gemini models failed for PDF classification');
        return $lastError ?? ['error' => 'All attempts failed'];
    }

    /**
     * Parse a cover page and extract structured project data
     * Falls back to PDF vision if no extracted text available
     */
    public function parseCoverPage(PdfPage $page): array
    {
        $text = $page->extracted_text;

        // If we have extracted text, use text-based parsing
        if (!empty($text)) {
            $prompt = <<<PROMPT
Analyze this architectural drawing cover page text and extract the actual data shown.

TEXT FROM COVER PAGE:
{$text}

Return a JSON object with these fields (use null if not found, extract ACTUAL values from the text):
- project_name: The actual project name shown
- client_name: The actual client/homeowner name
- designer: The actual designer or architect name
- revision: The actual revision number or date
- drawing_set_title: The actual title of the drawing package
- address: Object with street, city, state, zip - extract actual address values
- pricing_tiers: Array of objects with tier number, description, and linear_feet - extract actual tier info
- rooms_mentioned: Array of actual room names mentioned
- total_linear_feet: The actual total linear feet number
- scope_summary: Brief summary of what the project includes

Return ONLY valid JSON with the actual extracted values.
PROMPT;
            return $this->callAI($prompt);
        }

        // Fall back to PDF vision for the specific page
        $prompt = <<<PROMPT
You are looking at page {$page->page_number} of an architectural cabinet drawing PDF.
This appears to be a cover page. Extract all the ACTUAL information visible on this page.

Return a JSON object with these fields (use null if not visible, extract ACTUAL values):
- project_name: The actual project name shown
- client_name: The actual client/homeowner name
- designer: The actual designer or architect name
- revision: The actual revision number or date
- drawing_set_title: The actual title of the drawing package
- address: Object with street, city, state, zip - extract actual address values
- pricing_tiers: Array of objects with tier number, description, and linear_feet - extract actual tier info if shown
- rooms_mentioned: Array of actual room names mentioned
- total_linear_feet: The actual total linear feet number if shown
- scope_summary: Brief summary of what the project includes based on what you see

Return ONLY valid JSON with the actual extracted values from this PDF page.
PROMPT;

        return $this->parsePageWithVision($page, $prompt);
    }

    /**
     * Parse a specific page using Gemini's native PDF vision
     * Sends the entire PDF but instructs the AI to focus on a specific page
     */
    public function parsePageWithVision(PdfPage $page, string $prompt): array
    {
        // Use the correct relationship name 'pdfDocument' not 'document'
        $document = $page->pdfDocument;

        if (!$document) {
            // Fallback: try to load the document by ID
            $document = PdfDocument::find($page->document_id);

            if (!$document) {
                return ['error' => 'Page has no associated document'];
            }
        }

        $filePath = $document->file_path;

        // Try to get the full path
        if (Storage::disk('public')->exists($filePath)) {
            $fullPath = Storage::disk('public')->path($filePath);
        } elseif (file_exists(storage_path('app/public/' . $filePath))) {
            $fullPath = storage_path('app/public/' . $filePath);
        } elseif (file_exists($filePath)) {
            $fullPath = $filePath;
        } else {
            Log::error("PDF file not found for vision parsing: {$filePath}");
            return ['error' => "PDF file not found: {$filePath}"];
        }

        Log::info("Parsing page {$page->page_number} with PDF vision from: " . basename($fullPath));

        return $this->callGeminiWithPdf($fullPath, $prompt);
    }

    /**
     * Parse a floor plan page and extract room information
     * Falls back to PDF vision if no extracted text available
     */
    public function parseFloorPlan(PdfPage $page): array
    {
        $text = $page->extracted_text;

        if (!empty($text)) {
            $prompt = <<<PROMPT
Analyze this architectural floor plan text and extract the ACTUAL room and location information.

TEXT FROM FLOOR PLAN:
{$text}

Return a JSON object with these fields (extract ACTUAL values from the text):
- rooms: Array of room objects, each with "name" (actual room name) and "locations" (array of actual wall/area names like "Sink Wall", "Island", etc.)
- appliances_noted: Array of actual appliances mentioned
- dimensions_noted: Array of actual dimension strings shown
- notes: Any actual special notes from the plan

Return ONLY valid JSON with actual extracted values.
PROMPT;
            return $this->callAI($prompt);
        }

        // Fall back to PDF vision
        $prompt = <<<PROMPT
You are looking at page {$page->page_number} of an architectural cabinet drawing PDF.
This appears to be a floor plan. Extract all the ACTUAL room and location information visible.

Return a JSON object with these fields (extract ACTUAL values you see):
- rooms: Array of room objects, each with "name" (actual room name) and "locations" (array of actual wall/area names visible)
- appliances_noted: Array of actual appliances shown
- dimensions_noted: Array of actual dimension strings visible
- notes: Any actual special notes visible on this floor plan

Return ONLY valid JSON with actual extracted values from this PDF page.
PROMPT;

        return $this->parsePageWithVision($page, $prompt);
    }

    /**
     * Parse an elevation page and extract cabinet/location details
     * Falls back to PDF vision if no extracted text available
     */
    public function parseElevation(PdfPage $page): array
    {
        $text = $page->extracted_text;

        if (!empty($text)) {
            $prompt = <<<PROMPT
Analyze this cabinet elevation drawing text and extract the ACTUAL detailed information.

TEXT FROM ELEVATION:
{$text}

Return a JSON object with these fields (extract ACTUAL values from the text):
- location_name: The actual wall/location name (e.g., "Sink Wall", "Island", "Fridge Wall")
- room_name: The actual room this elevation is in
- linear_feet: The actual linear feet number if shown
- pricing_tier: The actual tier/level number if shown
- cabinets: Array of cabinet objects with type (base/upper/tall), width, and description - extract actual cabinets shown
- hardware: Object with drawer_slides, hinges, pulls - extract actual hardware specs
- materials: Object with face_frame, interior, finish - extract actual material specs
- appliances: Array of actual appliances in this elevation
- special_features: Array of actual special features mentioned

Return ONLY valid JSON with actual extracted values.
PROMPT;
            return $this->callAI($prompt);
        }

        // Fall back to PDF vision
        $prompt = <<<PROMPT
You are looking at page {$page->page_number} of an architectural cabinet drawing PDF.
This appears to be an elevation view showing cabinet details. Extract all the ACTUAL information visible.

Return a JSON object with these fields (extract ACTUAL values you see):
- location_name: The actual wall/location name shown
- room_name: The actual room this elevation is in
- linear_feet: The actual linear feet number if visible
- pricing_tier: The actual tier/level number if visible
- cabinets: Array of cabinet objects with type, width, description - extract actual cabinets visible
- hardware: Object with actual hardware specifications if shown
- materials: Object with actual material specifications if shown
- appliances: Array of actual appliances visible in this elevation
- special_features: Array of actual special features visible

Return ONLY valid JSON with actual extracted values from this PDF page.
PROMPT;

        return $this->parsePageWithVision($page, $prompt);
    }

    /**
     * Auto-classify a page based on its content (text-based, legacy)
     */
    public function classifyPage(PdfPage $page): array
    {
        $text = $page->extracted_text;

        if (empty($text)) {
            return ['error' => 'No extracted text available. Use classifyDocumentPages() for vision-based classification.'];
        }

        $prompt = <<<PROMPT
Analyze this architectural drawing page text and classify it.

TEXT FROM PAGE {$page->page_number}:
{$text}

Classify and return as JSON:
{
    "primary_purpose": "cover|floor_plan|elevations|countertops|reference|other",
    "confidence": 0.95,
    "page_label": "Descriptive name like 'Sink Wall' or 'Kitchen Floor Plan'",
    "has_hardware_schedule": true|false,
    "has_material_spec": true|false,
    "rooms_mentioned": ["Kitchen", "Pantry"],
    "locations_mentioned": ["Sink Wall", "Island"],
    "reasoning": "Brief explanation of why you classified it this way"
}

Return ONLY valid JSON.
PROMPT;

        return $this->callAI($prompt);
    }

    /**
     * Batch analyze all pages in a document (legacy text-based)
     */
    public function analyzeDocument(PdfDocument $document): array
    {
        $results = [];
        $pages = $document->pages()->orderBy('page_number')->get();

        foreach ($pages as $page) {
            $classification = $this->classifyPage($page);

            $results[$page->page_number] = [
                'page_id' => $page->id,
                'classification' => $classification,
            ];

            // Get detailed data based on classification
            $purpose = $classification['primary_purpose'] ?? null;

            if ($purpose === 'cover') {
                $results[$page->page_number]['details'] = $this->parseCoverPage($page);
            } elseif ($purpose === 'floor_plan') {
                $results[$page->page_number]['details'] = $this->parseFloorPlan($page);
            } elseif ($purpose === 'elevations') {
                $results[$page->page_number]['details'] = $this->parseElevation($page);
            }
        }

        return $results;
    }

    /**
     * Call the configured AI provider (text-only)
     */
    protected function callAI(string $prompt): array
    {
        if ($this->provider === 'gemini') {
            return $this->callGemini($prompt);
        }
        return $this->callClaude($prompt);
    }

    /**
     * Call Gemini API (text-only, cheaper option)
     */
    protected function callGemini(string $prompt): array
    {
        if (empty($this->geminiKey)) {
            Log::warning('AiPdfParsingService: No Gemini API key configured');
            return ['error' => 'Gemini API key not configured. Add GOOGLE_API_KEY to .env'];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->geminiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 2048,
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');

                // Clean up markdown code blocks if present
                $content = preg_replace('/^```json\s*/', '', $content);
                $content = preg_replace('/\s*```$/', '', $content);

                // Try to parse as JSON
                $decoded = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }

                return ['raw_response' => $content, 'parse_error' => json_last_error_msg()];
            }

            Log::error('Gemini API error: ' . $response->body());
            return ['error' => 'Gemini API request failed', 'status' => $response->status(), 'body' => $response->json()];

        } catch (\Exception $e) {
            Log::error('AiPdfParsingService Gemini error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Call Claude API (fallback)
     */
    protected function callClaude(string $prompt): array
    {
        if (empty($this->anthropicKey)) {
            Log::warning('AiPdfParsingService: No Anthropic API key configured');
            return ['error' => 'Anthropic API key not configured. Add ANTHROPIC_API_KEY to .env'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->anthropicKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 2048,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json('content.0.text');

                // Try to parse as JSON
                $decoded = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }

                return ['raw_response' => $content, 'parse_error' => json_last_error_msg()];
            }

            return ['error' => 'Claude API request failed', 'status' => $response->status()];

        } catch (\Exception $e) {
            Log::error('AiPdfParsingService Claude error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Apply AI classification results to a PdfPage
     *
     * Uses the model's classify() method to properly set classification status
     * and audit fields (classified_by, classified_at, processing_status).
     */
    public function applyClassification(PdfPage $page, array $classification): void
    {
        $primaryPurpose = $classification['primary_purpose'] ?? null;
        $pageLabel = $classification['page_label'] ?? null;

        // Use the model's classify method for proper status tracking
        if ($primaryPurpose) {
            $page->classify($primaryPurpose, $pageLabel);
        }

        // Build additional update data
        $updateData = [
            'page_metadata' => array_merge($page->page_metadata ?? [], [
                'ai_classification' => $classification,
                'ai_classified_at' => now()->toIso8601String(),
            ]),
        ];

        // Only update these if the columns exist and values are provided
        if (isset($classification['has_hardware_schedule'])) {
            $updateData['has_hardware_schedule'] = $classification['has_hardware_schedule'];
        }
        if (isset($classification['has_material_spec'])) {
            $updateData['has_material_spec'] = $classification['has_material_spec'];
        }

        $page->update($updateData);
    }

    /**
     * Apply bulk classification results from classifyDocumentPages to all pages
     */
    public function applyBulkClassification(PdfDocument $document, array $classifications): int
    {
        $updated = 0;
        $pages = $document->pages()->get()->keyBy('page_number');

        foreach ($classifications as $classification) {
            $pageNumber = $classification['page_number'] ?? null;
            if (!$pageNumber || !isset($pages[$pageNumber])) {
                continue;
            }

            $page = $pages[$pageNumber];
            $this->applyClassification($page, $classification);
            $updated++;
        }

        return $updated;
    }
}
