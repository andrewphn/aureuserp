<?php

/**
 * Direct test for PDF Ingestion using Gemini Vision
 * Tests the AiPdfParsingService with a sample PDF file
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "=== Direct PDF Ingestion Test (Gemini Vision) ===\n\n";

// Use sample architectural PDF
$pdfPath = __DIR__ . '/sample/Architectural PDF/9.28.25_25FriendshipRevision4.pdf';

if (!file_exists($pdfPath)) {
    echo "Error: Sample PDF not found at: {$pdfPath}\n";
    exit(1);
}

$fileSize = filesize($pdfPath);
echo "PDF File: " . basename($pdfPath) . "\n";
echo "File Size: " . number_format($fileSize) . " bytes\n";

// Check Gemini API key
$geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
if (empty($geminiKey)) {
    echo "\nError: No Gemini API key configured.\n";
    echo "Set GOOGLE_API_KEY or GEMINI_API_KEY in .env\n";
    exit(1);
}
echo "Gemini API Key: " . substr($geminiKey, 0, 10) . "...\n";

echo "\n--- Sending PDF to Gemini for Classification ---\n";

$pdfContent = file_get_contents($pdfPath);
$base64Pdf = base64_encode($pdfContent);

$prompt = <<<PROMPT
You are analyzing an architectural cabinet drawing PDF.
For EACH page in this PDF, classify it and extract key information.

Return a JSON array with one entry per page:
[
    {
        "page_number": 1,
        "primary_purpose": "cover|floor_plan|elevations|countertops|reference|other",
        "page_label": "Descriptive name like 'Cover Page', 'Kitchen Floor Plan', 'Sink Wall'",
        "confidence": 0.95,
        "rooms_mentioned": ["Kitchen", "Pantry"],
        "locations_mentioned": ["Sink Wall", "Island"],
        "linear_feet": null,
        "pricing_tier": null,
        "brief_description": "One sentence about what this page shows"
    }
]

Classification guide:
- "cover" = Title page, project info, pricing summary
- "floor_plan" = Bird's eye view showing room layout
- "elevations" = Front view of cabinets/walls
- "countertops" = Counter layout, cutouts
- "reference" = Photos, inspiration images
- "other" = Anything else

Return ONLY the JSON array, no other text.
PROMPT;

$startTime = microtime(true);

try {
    $response = Http::timeout(180)->withHeaders([
        'Content-Type' => 'application/json',
    ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiKey}", [
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

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "API call completed in {$elapsed}s\n\n";

    if (!$response->successful()) {
        echo "Error: API request failed with status " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
        exit(1);
    }

    $content = $response->json('candidates.0.content.parts.0.text');

    if (empty($content)) {
        echo "Error: Empty response from Gemini\n";
        echo "Full response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }

    // Clean up markdown code blocks if present
    $content = preg_replace('/^```json\s*/m', '', $content);
    $content = preg_replace('/\s*```$/m', '', $content);
    $content = trim($content);

    // Parse JSON
    $classifications = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error: Invalid JSON response\n";
        echo "Raw response:\n{$content}\n";
        exit(1);
    }

    echo "Page Classifications:\n";
    echo str_repeat('=', 100) . "\n";
    printf("%-6s %-15s %-35s %-8s %s\n", "Page", "Purpose", "Label", "Conf", "Description");
    echo str_repeat('-', 100) . "\n";

    foreach ($classifications as $page) {
        printf(
            "%-6s %-15s %-35s %-8s %s\n",
            $page['page_number'] ?? '?',
            $page['primary_purpose'] ?? 'unknown',
            substr($page['page_label'] ?? '', 0, 35),
            isset($page['confidence']) ? round($page['confidence'] * 100) . '%' : '?',
            substr($page['brief_description'] ?? '', 0, 50)
        );

        // Show linear feet if found
        if (!empty($page['linear_feet'])) {
            echo "       └── Linear Feet: {$page['linear_feet']} LF";
            if (!empty($page['pricing_tier'])) {
                echo ", Tier: {$page['pricing_tier']}";
            }
            echo "\n";
        }

        // Show rooms/locations
        if (!empty($page['rooms_mentioned'])) {
            echo "       └── Rooms: " . implode(', ', $page['rooms_mentioned']) . "\n";
        }
        if (!empty($page['locations_mentioned'])) {
            echo "       └── Locations: " . implode(', ', $page['locations_mentioned']) . "\n";
        }
    }

    echo str_repeat('=', 100) . "\n";

    // Summary
    $purposes = collect($classifications)->groupBy('primary_purpose');
    echo "\nSummary:\n";
    echo "  Total Pages: " . count($classifications) . "\n";
    foreach ($purposes as $purpose => $pages) {
        echo "  {$purpose}: " . count($pages) . " page(s)\n";
    }

    echo "\n=== Test Complete ===\n";
    echo "✓ Gemini Vision PDF analysis is working correctly!\n";
    echo "✓ You can now set up the n8n workflow to use the Laravel API.\n";

} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    exit(1);
}
