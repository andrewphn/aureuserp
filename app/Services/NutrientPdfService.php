<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NutrientPdfService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.nutrient.io';

    public function __construct()
    {
        $this->apiKey = config('nutrient.cloud_api_key');
    }

    /**
     * Render a specific page of a PDF as an image
     *
     * @param string $pdfPath Path to PDF in storage
     * @param int $pageNumber Page number (1-indexed)
     * @param int $width Desired width in pixels (default: 800)
     * @return string|null Base64 encoded image or null on failure
     */
    public function renderPageAsImage(string $pdfPath, int $pageNumber, int $width = 800): ?string
    {
        try {
            $fullPath = Storage::disk('public')->path($pdfPath);

            if (!file_exists($fullPath)) {
                Log::error("PDF file not found for rendering: {$pdfPath}");
                return null;
            }

            // Build the instructions to export specific page as PNG
            $instructions = [
                'parts' => [
                    ['file' => 'document']
                ],
                'actions' => [
                    [
                        'type' => 'exportPages',
                        'pages' => [$pageNumber - 1], // Nutrient uses 0-indexed pages
                        'format' => 'png',
                        'width' => $width,
                    ]
                ]
            ];

            // Make request to Nutrient API
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])
            ->attach('document', file_get_contents($fullPath), basename($pdfPath))
            ->post("{$this->baseUrl}/build", [
                'instructions' => json_encode($instructions)
            ]);

            if ($response->successful()) {
                // Return base64 encoded image
                return base64_encode($response->body());
            }

            Log::error("Nutrient API error: " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("Error rendering PDF page: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Render a page and save it to storage
     *
     * @param string $pdfPath Path to PDF in storage
     * @param int $pageNumber Page number (1-indexed)
     * @param int $width Desired width in pixels
     * @return string|null Path to saved image or null on failure
     */
    public function renderAndSavePage(string $pdfPath, int $pageNumber, int $width = 800): ?string
    {
        $imageData = $this->renderPageAsImage($pdfPath, $pageNumber, $width);

        if (!$imageData) {
            return null;
        }

        // Generate cache path
        $pdfHash = md5($pdfPath);
        $cachePath = "pdf-previews/{$pdfHash}/page-{$pageNumber}-{$width}.png";

        // Save to storage
        Storage::disk('public')->put($cachePath, base64_decode($imageData));

        return $cachePath;
    }

    /**
     * Get cached page image or render if not cached
     *
     * @param string $pdfPath Path to PDF in storage
     * @param int $pageNumber Page number (1-indexed)
     * @param int $width Desired width in pixels
     * @return string|null URL to image or null on failure
     */
    public function getCachedOrRenderPage(string $pdfPath, int $pageNumber, int $width = 800): ?string
    {
        $pdfHash = md5($pdfPath);
        $cachePath = "pdf-previews/{$pdfHash}/page-{$pageNumber}-{$width}.png";

        // Check if cached version exists
        if (Storage::disk('public')->exists($cachePath)) {
            return Storage::disk('public')->url($cachePath);
        }

        // Render and save
        $savedPath = $this->renderAndSavePage($pdfPath, $pageNumber, $width);

        if ($savedPath) {
            return Storage::disk('public')->url($savedPath);
        }

        return null;
    }
}
