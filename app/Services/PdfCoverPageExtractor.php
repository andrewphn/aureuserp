<?php

namespace App\Services;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Extracts structured data from PDF cover pages using AI vision
 *
 * Pipeline:
 * 1. Send PDF to Gemini with structured prompt
 * 2. Receive JSON matching our schema
 * 3. Validate and map to our data models
 */
class PdfCoverPageExtractor
{
    protected string $geminiKey;

    /**
     * The JSON schema we expect back from AI
     * This controls the output structure
     */
    public const OUTPUT_SCHEMA = [
        'project' => [
            'name' => 'string|null',           // "25 Friendship Lane" or "Kitchen Cabinetry"
            'address' => [
                'street' => 'string|null',     // "25 Friendship Lane"
                'city' => 'string|null',       // "Nantucket"
                'state' => 'string|null',      // "MA"
                'zip' => 'string|null',        // "02554"
            ],
            'description' => 'string|null',    // "Kitchen Cabinetry"
        ],
        'designer' => [
            'company_name' => 'string|null',   // "Trottier Fine Woodworking"
            'owner_name' => 'string|null',     // "Jeremy Trottier"
            'drawn_by' => 'string|null',       // "J. Garcia"
            'approved_by' => 'string|null',    // "J. Trottier"
            'phone' => 'string|null',
            'email' => 'string|null',
            'website' => 'string|null',
        ],
        'revision' => [
            'current' => 'string|null',        // "Revision 4" or "Rev 4"
            'date' => 'string|null',           // "9/27/25"
            'history' => [                     // Array of previous revisions
                ['date' => 'string', 'description' => 'string'],
            ],
        ],
        'scope_estimate' => [                  // Designer's line item estimates
            [
                'item_type' => 'string',       // "Tier 2 Cabinetry", "Floating Shelves"
                'quantity' => 'number',        // 11.5
                'unit' => 'string',            // "LF", "SF", "EA"
                'notes' => 'string|null',      // Any additional notes
            ],
        ],
        'rooms_mentioned' => ['string'],       // ["Kitchen", "Pantry"]
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key')
            ?? env('GOOGLE_API_KEY')
            ?? env('GEMINI_API_KEY');
    }

    /**
     * Extract cover page data from a PDF document
     */
    public function extract(PdfDocument $document): array
    {
        $pdfPath = $this->getPdfPath($document);

        if (!$pdfPath) {
            return ['error' => 'PDF file not found'];
        }

        $prompt = $this->buildPrompt();
        $response = $this->callGemini($pdfPath, $prompt);

        if (isset($response['error'])) {
            return $response;
        }

        // Validate the response matches our schema
        $validated = $this->validateResponse($response);

        return $validated;
    }

    /**
     * Build the AI prompt with exact output schema
     */
    protected function buildPrompt(): string
    {
        return <<<'PROMPT'
Analyze PAGE 1 ONLY (the cover page) of this architectural cabinet drawing PDF.

Extract ALL visible data into this EXACT JSON structure:

{
    "project": {
        "name": "Project name or main title shown",
        "address": {
            "street": "Street address",
            "city": "City name",
            "state": "State abbreviation (e.g., MA, NY)",
            "zip": "ZIP code"
        },
        "description": "Project description (e.g., Kitchen Cabinetry, Bathroom Vanities)"
    },
    "designer": {
        "company_name": "Design/woodworking company name",
        "owner_name": "Owner name if shown separately",
        "drawn_by": "Name of person who drew the plans",
        "approved_by": "Name of person who approved",
        "phone": "Phone number",
        "email": "Email address",
        "website": "Website URL"
    },
    "revision": {
        "current": "Current revision number or name (e.g., Rev 4, Revision 4)",
        "date": "Date of current revision",
        "history": [
            {"date": "9/1/25", "description": "Initial draft"},
            {"date": "9/3/25", "description": "Revision 2"}
        ]
    },
    "scope_estimate": [
        {
            "item_type": "Category name exactly as shown (e.g., Tier 2 Cabinetry, Floating Shelves, Millwork Countertops)",
            "quantity": 11.5,
            "unit": "LF or SF or EA or other unit shown",
            "notes": "Any notes for this line"
        }
    ],
    "rooms_mentioned": ["Kitchen", "Pantry", "any other rooms mentioned"]
}

IMPORTANT RULES:
1. Return ONLY valid JSON - no other text, no markdown, no explanations
2. Use null for any field not visible on the cover page
3. For scope_estimate, capture EVERY line item shown with quantities
4. Keep item_type exactly as written on the page (their terminology, not yours)
5. Parse numbers as numbers, not strings (11.5 not "11.5")
6. For revision history, include ALL revisions shown, oldest first
PROMPT;
    }

    /**
     * Models to try in order (with retry on rate limit)
     */
    protected array $models = [
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
    ];

    /**
     * Call Gemini API with PDF and prompt (with retry logic)
     */
    protected function callGemini(string $pdfPath, string $prompt): array
    {
        if (empty($this->geminiKey)) {
            return ['error' => 'No Gemini API key configured'];
        }

        try {
            $pdfContent = file_get_contents($pdfPath);
            $base64Pdf = base64_encode($pdfContent);

            Log::info('PdfCoverPageExtractor: Sending PDF to Gemini', [
                'file' => basename($pdfPath),
                'size' => strlen($pdfContent),
            ]);

            // Try each model with retry logic
            $lastError = null;
            foreach ($this->models as $modelIndex => $model) {
                $maxRetries = 3;
                $retryDelay = 4; // seconds

                for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                    $response = Http::timeout(120)->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->geminiKey}",
                        [
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'inline_data' => [
                                                'mime_type' => 'application/pdf',
                                                'data' => $base64Pdf,
                                            ]
                                        ],
                                        ['text' => $prompt]
                                    ]
                                ]
                            ],
                            'generationConfig' => [
                                'temperature' => 0.1,  // Low temp for consistent output
                                'maxOutputTokens' => 4096,
                            ],
                        ]
                    );

                    // Success
                    if ($response->successful()) {
                        Log::info("PdfCoverPageExtractor: Success with {$model} on attempt {$attempt}");
                        return $this->parseGeminiResponse($response);
                    }

                    // Rate limited - retry with backoff
                    if ($response->status() === 429) {
                        $waitTime = $retryDelay * pow(2, $attempt - 1);
                        Log::warning("PdfCoverPageExtractor: Rate limited on {$model}, waiting {$waitTime}s (attempt {$attempt}/{$maxRetries})");

                        if ($attempt < $maxRetries) {
                            sleep($waitTime);
                            continue;
                        }

                        // Max retries for this model, try next model
                        $lastError = [
                            'error' => "Rate limited on {$model} after {$maxRetries} attempts",
                            'status' => 429,
                        ];
                        break;
                    }

                    // Other error - try next model
                    $lastError = [
                        'error' => "Gemini API error on {$model}",
                        'status' => $response->status(),
                        'details' => $response->json()['error']['message'] ?? 'Unknown error',
                    ];
                    Log::warning("PdfCoverPageExtractor: {$model} failed", $lastError);
                    break;
                }
            }

            // All models failed
            Log::error('PdfCoverPageExtractor: All models failed', $lastError ?? []);
            return $lastError ?? ['error' => 'All Gemini models failed'];

        } catch (\Exception $e) {
            Log::error('PdfCoverPageExtractor: Exception', [
                'message' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Parse the Gemini response into structured data
     */
    protected function parseGeminiResponse($response): array
    {
        $content = $response->json('candidates.0.content.parts.0.text');

        // Clean markdown code blocks
        $content = preg_replace('/^```json\s*/m', '', $content);
        $content = preg_replace('/^```\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        Log::info('PdfCoverPageExtractor: Raw response', [
            'length' => strlen($content),
            'preview' => substr($content, 0, 200),
        ]);

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('PdfCoverPageExtractor: JSON parse error', [
                'error' => json_last_error_msg(),
                'content' => $content,
            ]);
            return [
                'error' => 'Failed to parse AI response as JSON',
                'parse_error' => json_last_error_msg(),
                'raw_response' => $content,
            ];
        }

        return $decoded;
    }

    /**
     * Validate response matches expected schema
     */
    protected function validateResponse(array $response): array
    {
        // Check for required top-level keys
        $requiredKeys = ['project', 'designer', 'revision', 'scope_estimate'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                $response[$key] = null;
            }
        }

        // Ensure scope_estimate is an array
        if (!is_array($response['scope_estimate'])) {
            $response['scope_estimate'] = [];
        }

        // Ensure rooms_mentioned is an array
        if (!isset($response['rooms_mentioned']) || !is_array($response['rooms_mentioned'])) {
            $response['rooms_mentioned'] = [];
        }

        return $response;
    }

    /**
     * Get the full path to the PDF file
     */
    protected function getPdfPath(PdfDocument $document): ?string
    {
        $filePath = $document->file_path;

        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->path($filePath);
        }

        if (file_exists(storage_path('app/public/' . $filePath))) {
            return storage_path('app/public/' . $filePath);
        }

        if (file_exists($filePath)) {
            return $filePath;
        }

        return null;
    }

    /**
     * Map extracted data to Project model fields
     * Returns array ready for Project::update()
     */
    public function mapToProject(array $extracted): array
    {
        $mapped = [];

        // Project name/description
        if (!empty($extracted['project']['name'])) {
            $mapped['name'] = $extracted['project']['name'];
        }
        if (!empty($extracted['project']['description'])) {
            $mapped['description'] = $extracted['project']['description'];
        }

        // Revision info
        if (!empty($extracted['revision']['current'])) {
            $mapped['design_revision_number'] = $extracted['revision']['current'];
        }

        // Store designer info and scope in design_notes as structured data
        $notes = [];

        if (!empty($extracted['designer']['drawn_by'])) {
            $notes[] = "Drawn by: {$extracted['designer']['drawn_by']}";
        }
        if (!empty($extracted['designer']['approved_by'])) {
            $notes[] = "Approved by: {$extracted['designer']['approved_by']}";
        }
        if (!empty($extracted['revision']['date'])) {
            $notes[] = "Revision date: {$extracted['revision']['date']}";
        }

        if (!empty($notes)) {
            $mapped['design_notes'] = implode("\n", $notes);
        }

        return $mapped;
    }

    /**
     * Map extracted data to Partner model fields
     * Returns array ready for Partner::update() or Partner::create()
     */
    public function mapToPartner(array $extracted): array
    {
        $mapped = [];

        if (!empty($extracted['designer']['company_name'])) {
            $mapped['name'] = $extracted['designer']['company_name'];
        }
        if (!empty($extracted['designer']['phone'])) {
            $mapped['phone'] = $extracted['designer']['phone'];
        }
        if (!empty($extracted['designer']['email'])) {
            $mapped['email'] = $extracted['designer']['email'];
        }
        if (!empty($extracted['designer']['website'])) {
            $mapped['website'] = $extracted['designer']['website'];
        }

        return $mapped;
    }

    /**
     * Map extracted address to project address fields
     */
    public function mapToAddress(array $extracted): array
    {
        $address = $extracted['project']['address'] ?? [];

        return [
            'street1' => $address['street'] ?? null,
            'city' => $address['city'] ?? null,
            'state_code' => $address['state'] ?? null,
            'zip' => $address['zip'] ?? null,
        ];
    }

    /**
     * Get scope estimate line items
     * These are the designer's estimates, stored for reference
     */
    public function getScopeEstimate(array $extracted): array
    {
        return $extracted['scope_estimate'] ?? [];
    }
}
