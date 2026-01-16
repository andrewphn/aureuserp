<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extracts and classifies all notes, callouts, and annotations from construction drawings.
 * This is the THIRD step in the drawing analysis pipeline.
 *
 * Purpose: Capture all textual information WITHOUT interpreting or applying it.
 * Notes will be applied to geometry in a later stage.
 */
class DrawingNotesExtractorService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Note scope levels (from broadest to most specific)
    public const NOTE_SCOPES = [
        'global' => 'Applies to entire project/all drawings',
        'drawing' => 'Applies to this drawing sheet only',
        'room' => 'Applies to a specific room',
        'location' => 'Applies to a specific location/wall within room',
        'cabinet' => 'Applies to a specific cabinet',
        'section' => 'Applies to a section of cabinets (e.g., upper run)',
        'component' => 'Applies to a specific component (door, drawer, shelf)',
        'detail' => 'Applies to a detail callout area',
    ];

    // Note types
    public const NOTE_TYPES = [
        'instruction' => 'Construction or assembly instruction',
        'constraint' => 'Dimensional or positional constraint',
        'material_spec' => 'Material specification (species, grade, thickness)',
        'finish_spec' => 'Finish specification (stain, paint, clear coat)',
        'hardware_spec' => 'Hardware specification (hinges, slides, pulls)',
        'warning' => 'Warning or caution note',
        'clarification' => 'Clarifying note for ambiguous detail',
        'reference' => 'Reference to another drawing or standard',
        'revision' => 'Revision or change note',
        'general' => 'General note not fitting other categories',
    ];

    // Actionability categories
    public const ACTIONABILITY = [
        'production_required' => 'Must be addressed during production',
        'production_optional' => 'May affect production decisions',
        'verification_only' => 'For verification/QC purposes only',
        'informational' => 'Informational only, no action needed',
        'unclear' => 'Actionability cannot be determined',
    ];

    // Location descriptors for note placement
    public const LOCATION_DESCRIPTORS = [
        'title_block' => 'In drawing title block',
        'general_notes' => 'In general notes section',
        'leader_callout' => 'Connected by leader line to geometry',
        'inline' => 'Inline with dimension or geometry',
        'floating' => 'Floating text near relevant geometry',
        'detail_view' => 'Within a detail view callout',
        'section_label' => 'Section cut label',
        'symbol_legend' => 'In symbol legend/key',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Extract all notes and annotations from a drawing.
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param string $mimeType Image MIME type
     * @param array|null $drawingContext Optional context from prior analysis
     * @return array Extraction result with classified notes
     */
    public function extractNotes(
        string $imageBase64,
        string $mimeType,
        ?array $drawingContext = null
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        $systemPrompt = $this->buildNotesExtractionPrompt($drawingContext);

        try {
            $response = $this->callGeminiApi($systemPrompt, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            return $this->parseNotesResponse($response);

        } catch (\Exception $e) {
            Log::error('DrawingNotesExtractor error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Build the system prompt for notes extraction.
     */
    protected function buildNotesExtractionPrompt(?array $drawingContext): string
    {
        $contextInfo = '';
        if ($drawingContext) {
            $viewType = $drawingContext['view_type']['primary'] ?? 'unknown';
            $intent = $drawingContext['drawing_intent']['type'] ?? 'unknown';

            $contextInfo = <<<CONTEXT

## DRAWING CONTEXT (from prior analysis)
- View Type: {$viewType}
- Drawing Intent: {$intent}

Use this context to help classify note scope and relevance.
CONTEXT;
        }

        return <<<PROMPT
You are extracting ALL notes, callouts, and annotations from a cabinet construction drawing.

CRITICAL INSTRUCTIONS:
- Extract the EXACT text as written - do NOT paraphrase or interpret
- Classify each note but do NOT apply it to geometry yet
- Do NOT skip any text, even if it seems redundant
- Include title block information, general notes, callouts, and inline annotations
{$contextInfo}

## FOR EACH NOTE, IDENTIFY:

### 1. EXACT TEXT
- Transcribe exactly as written
- Preserve line breaks if multi-line
- Note if text is partially obscured or unclear
- Include any symbols (⌀, ±, #, etc.)

### 2. LOCATION IN DRAWING
Where is this note physically located?
- **title_block**: In the title block area
- **general_notes**: In a general notes section/list
- **leader_callout**: Connected by a leader line to specific geometry
- **inline**: Inline with a dimension string
- **floating**: Floating text near relevant geometry
- **detail_view**: Inside a detail view bubble
- **section_label**: A section cut identifier
- **symbol_legend**: In a legend or key

### 3. SCOPE
What does this note apply to?
- **global**: Entire project (e.g., "All cabinets face frame construction")
- **drawing**: This drawing only (e.g., "See Sheet A2 for sections")
- **room**: A specific room (e.g., "Master Bath vanity")
- **location**: A wall or area (e.g., "North wall uppers")
- **cabinet**: A specific cabinet (e.g., "Sink base")
- **section**: A run of cabinets (e.g., "Upper cabinets")
- **component**: A specific part (e.g., "Drawer fronts")
- **detail**: A detail area (e.g., "Crown detail")

### 4. TYPE
What kind of note is this?
- **instruction**: How to build/install (e.g., "Scribe to wall")
- **constraint**: Limits or requirements (e.g., "Max 36\" wide")
- **material_spec**: Material callout (e.g., "3/4\" Maple plywood")
- **finish_spec**: Finish callout (e.g., "Sherwin Williams SW7006")
- **hardware_spec**: Hardware callout (e.g., "Blum Tandem 563H")
- **warning**: Caution note (e.g., "Verify field dimensions")
- **clarification**: Explains ambiguity (e.g., "Dimension to face frame")
- **reference**: Points elsewhere (e.g., "See detail A")
- **revision**: Change note (e.g., "Rev 2: Changed depth")
- **general**: Doesn't fit other categories

### 5. ACTIONABILITY
Is this note actionable for production?
- **production_required**: Must be addressed in shop (e.g., "Notch for plumbing")
- **production_optional**: May affect production (e.g., "Field verify")
- **verification_only**: For QC only (e.g., "Check against approved sample")
- **informational**: No action needed (e.g., "Designer: J. Smith")
- **unclear**: Cannot determine actionability

### 6. ASSOCIATED GEOMETRY (if visible)
If a leader line or proximity suggests association:
- Describe what geometry it points to
- Do NOT assume - only report what is visibly connected

## RESPONSE FORMAT

Respond ONLY with valid JSON (no markdown, no explanation):

{
  "notes": [
    {
      "id": "NOTE-001",
      "text": {
        "exact": "ALL FACE FRAMES 1-1/2\" STILES & RAILS\\nUNLESS NOTED OTHERWISE",
        "is_complete": true,
        "is_clear": true,
        "has_symbols": false
      },
      "location": {
        "placement": "general_notes",
        "position_description": "Top right, general notes block, item 1"
      },
      "scope": "global",
      "type": "material_spec",
      "actionability": "production_required",
      "associated_geometry": null,
      "confidence": 0.95
    },
    {
      "id": "NOTE-002",
      "text": {
        "exact": "VERIFY FIELD DIMENSIONS",
        "is_complete": true,
        "is_clear": true,
        "has_symbols": false
      },
      "location": {
        "placement": "floating",
        "position_description": "Bottom center of elevation"
      },
      "scope": "drawing",
      "type": "warning",
      "actionability": "production_required",
      "associated_geometry": null,
      "confidence": 0.9
    },
    {
      "id": "NOTE-003",
      "text": {
        "exact": "3/4\" MAPLE PLY",
        "is_complete": true,
        "is_clear": true,
        "has_symbols": false
      },
      "location": {
        "placement": "leader_callout",
        "position_description": "Leader pointing to cabinet side panel"
      },
      "scope": "component",
      "type": "material_spec",
      "actionability": "production_required",
      "associated_geometry": {
        "type": "panel",
        "description": "Cabinet side panel, left side of elevation"
      },
      "confidence": 0.9
    },
    {
      "id": "NOTE-004",
      "text": {
        "exact": "TYP.",
        "is_complete": true,
        "is_clear": true,
        "has_symbols": false
      },
      "location": {
        "placement": "inline",
        "position_description": "After dimension 1-1/2\""
      },
      "scope": "drawing",
      "type": "clarification",
      "actionability": "production_required",
      "associated_geometry": {
        "type": "dimension",
        "description": "Face frame stile width dimension"
      },
      "confidence": 0.85
    }
  ],
  "title_block": {
    "project_name": "Smith Residence - Kitchen",
    "drawing_title": "Base Cabinet Elevations",
    "drawing_number": "A-101",
    "revision": "2",
    "date": "01/15/2026",
    "scale": "1/2\" = 1'-0\"",
    "designer": "J. Smith",
    "other_fields": []
  },
  "general_notes_section": {
    "exists": true,
    "location": "Top right corner",
    "note_count": 5,
    "note_ids": ["NOTE-001", "NOTE-005", "NOTE-006", "NOTE-007", "NOTE-008"]
  },
  "summary": {
    "total_notes": 8,
    "by_scope": {
      "global": 2,
      "drawing": 1,
      "cabinet": 3,
      "component": 2
    },
    "by_type": {
      "material_spec": 3,
      "instruction": 2,
      "warning": 1,
      "clarification": 2
    },
    "production_required_count": 6,
    "unclear_notes": 0
  },
  "extraction_notes": "All notes successfully extracted. General notes section contains standard TCS specifications."
}
PROMPT;
    }

    /**
     * Call the Gemini API for notes extraction.
     */
    protected function callGeminiApi(string $systemPrompt, array $image): array
    {
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Extract ALL notes, callouts, and annotations from this drawing. Transcribe exact text and classify each note. Do not interpret meaning or apply to geometry.'],
                    ['inlineData' => $image],
                ],
            ],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.1, // Very low for accurate transcription
                'topP' => 0.8,
                'maxOutputTokens' => 8192, // Large for many notes
            ],
        ];

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(120)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->geminiKey}",
                    $payload
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $candidate = $data['candidates'][0] ?? null;

                    if (!$candidate) {
                        return $this->errorResponse('No response generated');
                    }

                    $content = $candidate['content'] ?? [];
                    $parts = $content['parts'] ?? [];
                    $textResponse = '';

                    foreach ($parts as $part) {
                        if (isset($part['text'])) {
                            $textResponse .= $part['text'];
                        }
                    }

                    return ['text' => $textResponse, 'success' => true];
                }

                $statusCode = $response->status();
                if ($statusCode === 429 && $attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                    continue;
                }

                return $this->errorResponse('API error: ' . ($response->json('error.message') ?? 'Unknown error'));

            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                    continue;
                }
                throw $e;
            }
        }

        return $this->errorResponse('All API attempts failed');
    }

    /**
     * Parse and validate the notes extraction response.
     */
    protected function parseNotesResponse(array $response): array
    {
        $text = $response['text'] ?? '';

        // Clean up response
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Failed to parse JSON response: ' . json_last_error_msg(),
                'raw_response' => $text,
            ];
        }

        // Validate the response
        $validation = $this->validateNotesExtraction($decoded);

        // Categorize notes for easy access
        $categorized = $this->categorizeNotes($decoded);

        return [
            'success' => true,
            'extraction' => $decoded,
            'validation' => $validation,
            'categorized' => $categorized,
        ];
    }

    /**
     * Validate the notes extraction against expected structure.
     */
    protected function validateNotesExtraction(array $extraction): array
    {
        $errors = [];
        $warnings = [];

        // Check for notes array
        if (!isset($extraction['notes']) || !is_array($extraction['notes'])) {
            $errors[] = "Missing or invalid 'notes' array";
            return ['is_valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Validate each note
        foreach ($extraction['notes'] as $index => $note) {
            $noteId = $note['id'] ?? "NOTE-{$index}";

            // Check required fields
            if (!isset($note['text']['exact'])) {
                $warnings[] = "{$noteId}: Missing exact text";
            }

            if (!isset($note['scope'])) {
                $warnings[] = "{$noteId}: Missing scope classification";
            } elseif (!array_key_exists($note['scope'], self::NOTE_SCOPES)) {
                $warnings[] = "{$noteId}: Unknown scope '{$note['scope']}'";
            }

            if (!isset($note['type'])) {
                $warnings[] = "{$noteId}: Missing type classification";
            } elseif (!array_key_exists($note['type'], self::NOTE_TYPES)) {
                $warnings[] = "{$noteId}: Unknown type '{$note['type']}'";
            }

            if (!isset($note['actionability'])) {
                $warnings[] = "{$noteId}: Missing actionability classification";
            } elseif (!array_key_exists($note['actionability'], self::ACTIONABILITY)) {
                $warnings[] = "{$noteId}: Unknown actionability '{$note['actionability']}'";
            }

            // Check for unclear text
            if (isset($note['text']['is_clear']) && !$note['text']['is_clear']) {
                $warnings[] = "{$noteId}: Text is marked as unclear - manual review needed";
            }

            if (isset($note['text']['is_complete']) && !$note['text']['is_complete']) {
                $warnings[] = "{$noteId}: Text is incomplete/obscured - manual review needed";
            }

            // Check confidence
            $confidence = $note['confidence'] ?? 0;
            if ($confidence < 0.7) {
                $warnings[] = "{$noteId}: Low confidence ({$confidence}) in classification";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Categorize notes for easy filtering and access.
     */
    protected function categorizeNotes(array $extraction): array
    {
        $notes = $extraction['notes'] ?? [];

        $byScope = [];
        $byType = [];
        $byActionability = [];
        $productionRequired = [];
        $warnings = [];
        $materialSpecs = [];
        $hardwareSpecs = [];
        $finishSpecs = [];

        foreach ($notes as $note) {
            $noteId = $note['id'] ?? 'unknown';

            // By scope
            $scope = $note['scope'] ?? 'unknown';
            $byScope[$scope][] = $note;

            // By type
            $type = $note['type'] ?? 'unknown';
            $byType[$type][] = $note;

            // By actionability
            $action = $note['actionability'] ?? 'unclear';
            $byActionability[$action][] = $note;

            // Production required
            if ($action === 'production_required') {
                $productionRequired[] = $note;
            }

            // Warnings
            if ($type === 'warning') {
                $warnings[] = $note;
            }

            // Specifications
            if ($type === 'material_spec') {
                $materialSpecs[] = $note;
            }
            if ($type === 'hardware_spec') {
                $hardwareSpecs[] = $note;
            }
            if ($type === 'finish_spec') {
                $finishSpecs[] = $note;
            }
        }

        return [
            'by_scope' => $byScope,
            'by_type' => $byType,
            'by_actionability' => $byActionability,
            'production_required' => $productionRequired,
            'warnings' => $warnings,
            'specifications' => [
                'material' => $materialSpecs,
                'hardware' => $hardwareSpecs,
                'finish' => $finishSpecs,
            ],
        ];
    }

    /**
     * Get notes that apply to a specific scope level.
     */
    public function getNotesForScope(array $extractionResult, string $scope): array
    {
        if (!$extractionResult['success']) {
            return [];
        }

        return $extractionResult['categorized']['by_scope'][$scope] ?? [];
    }

    /**
     * Get all production-required notes.
     */
    public function getProductionNotes(array $extractionResult): array
    {
        if (!$extractionResult['success']) {
            return [];
        }

        return $extractionResult['categorized']['production_required'] ?? [];
    }

    /**
     * Get all specification notes (material, hardware, finish).
     */
    public function getSpecificationNotes(array $extractionResult): array
    {
        if (!$extractionResult['success']) {
            return [];
        }

        return $extractionResult['categorized']['specifications'] ?? [];
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeNotes(array $extractionResult): string
    {
        if (!$extractionResult['success']) {
            return "Extraction failed: " . ($extractionResult['error'] ?? 'Unknown error');
        }

        $ext = $extractionResult['extraction'];
        $lines = [];

        $lines[] = "Drawing Notes Extraction";
        $lines[] = str_repeat('-', 40);

        // Title block info
        if (isset($ext['title_block'])) {
            $tb = $ext['title_block'];
            if (!empty($tb['project_name'])) {
                $lines[] = "Project: {$tb['project_name']}";
            }
            if (!empty($tb['drawing_number'])) {
                $rev = !empty($tb['revision']) ? " Rev {$tb['revision']}" : '';
                $lines[] = "Drawing: {$tb['drawing_number']}{$rev}";
            }
        }

        // Summary stats
        $summary = $ext['summary'] ?? [];
        $total = $summary['total_notes'] ?? 0;
        $lines[] = "";
        $lines[] = "Total notes extracted: {$total}";

        // By scope
        if (!empty($summary['by_scope'])) {
            $lines[] = "";
            $lines[] = "By Scope:";
            foreach ($summary['by_scope'] as $scope => $count) {
                $lines[] = "  {$scope}: {$count}";
            }
        }

        // By type
        if (!empty($summary['by_type'])) {
            $lines[] = "";
            $lines[] = "By Type:";
            foreach ($summary['by_type'] as $type => $count) {
                $lines[] = "  {$type}: {$count}";
            }
        }

        // Production required
        $prodCount = $summary['production_required_count'] ?? 0;
        $lines[] = "";
        $lines[] = "Production-required notes: {$prodCount}";

        // Warnings
        $warnings = $extractionResult['categorized']['warnings'] ?? [];
        if (!empty($warnings)) {
            $lines[] = "";
            $lines[] = "⚠ Warning Notes:";
            foreach ($warnings as $w) {
                $text = $w['text']['exact'] ?? 'Unknown';
                $lines[] = "  • {$text}";
            }
        }

        // Validation warnings
        if (!empty($extractionResult['validation']['warnings'])) {
            $lines[] = "";
            $lines[] = "Extraction Warnings:";
            foreach ($extractionResult['validation']['warnings'] as $warning) {
                $lines[] = "  • {$warning}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Create error response.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'extraction' => null,
            'validation' => ['is_valid' => false, 'errors' => [$message], 'warnings' => []],
            'categorized' => [],
        ];
    }

    /**
     * Check if service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->geminiKey);
    }

    /**
     * Get valid values for a given category.
     */
    public static function getValidValues(string $category): array
    {
        return match ($category) {
            'note_scopes' => self::NOTE_SCOPES,
            'note_types' => self::NOTE_TYPES,
            'actionability' => self::ACTIONABILITY,
            'location_descriptors' => self::LOCATION_DESCRIPTORS,
            default => [],
        };
    }
}
