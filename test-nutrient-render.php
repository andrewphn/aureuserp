<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "=== Nutrient PDF Rendering Test ===\n\n";

$pdf = \App\Models\PdfDocument::find(1);

echo "PDF Info:\n";
echo "  ID: " . $pdf->id . "\n";
echo "  File Path: " . $pdf->file_path . "\n\n";

$fullPath = Storage::disk('public')->path($pdf->file_path);
echo "Full Path: $fullPath\n";
echo "File Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
echo "File Size: " . filesize($fullPath) . " bytes\n\n";

$apiKey = config('nutrient.cloud_api_key');
echo "API Key: " . substr($apiKey, 0, 15) . "...\n";
echo "API Key Length: " . strlen($apiKey) . "\n\n";

echo "Testing Nutrient API call...\n";

$instructions = [
    'parts' => [
        [
            'file' => 'document'
        ]
    ],
    'output' => [
        'type' => 'image',
        'format' => 'png',
        'dpi' => 150,
        'pages' => [
            'start' => 0,  // First page (0-indexed)
            'end' => 0
        ]
    ]
];

try {
    $response = Http::timeout(30)
        ->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])
        ->attach('document', file_get_contents($fullPath), basename($pdf->file_path))
        ->post("https://api.nutrient.io/build", [
            'instructions' => json_encode($instructions)
        ]);

    echo "Response Status: " . $response->status() . "\n";
    echo "Response Headers:\n";
    foreach ($response->headers() as $header => $values) {
        echo "  $header: " . implode(', ', $values) . "\n";
    }
    echo "\n";

    if ($response->successful()) {
        echo "SUCCESS!\n";
        echo "Response body length: " . strlen($response->body()) . " bytes\n";

        // Save the image
        $cachePath = "pdf-previews/test-page-1.png";
        Storage::disk('public')->put($cachePath, $response->body());
        echo "Image saved to: " . Storage::disk('public')->url($cachePath) . "\n";
    } else {
        echo "FAILED!\n";
        echo "Response body:\n" . $response->body() . "\n";
    }

} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
