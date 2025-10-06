<?php

namespace App\Services;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Google Vision OCR Service
 *
 * Handles OCR text extraction using Google Cloud Vision API
 * Provides timing metrics for performance comparison
 */
class GoogleVisionOcrService
{
    protected ImageAnnotatorClient $client;

    /**
     * Initialize Google Vision API client
     */
    public function __construct()
    {
        $apiKey = config('services.google.vision_api_key');

        if (empty($apiKey)) {
            throw new \Exception('Google Vision API key not configured. Set GOOGLE_VISION_API_KEY in .env');
        }

        // Initialize with API key authentication
        $this->client = new ImageAnnotatorClient([
            'credentials' => [
                'key' => $apiKey,
            ],
        ]);
    }

    /**
     * Extract text from an image using Google Vision OCR
     *
     * @param string $imagePath Path to image file (thumbnail or PDF page image)
     * @return array ['text' => string, 'time_ms' => int, 'confidence' => float]
     */
    public function extractText(string $imagePath): array
    {
        $startTime = microtime(true);

        try {
            // Load image from storage
            if (!Storage::disk('public')->exists($imagePath)) {
                throw new \Exception("Image file not found: {$imagePath}");
            }

            $imageContent = Storage::disk('public')->get($imagePath);

            // Create image object
            $image = new Image();
            $image->setContent($imageContent);

            // Configure feature for text detection (DOCUMENT_TEXT_DETECTION is best for documents)
            $feature = new Feature();
            $feature->setType(Type::DOCUMENT_TEXT_DETECTION);

            // Perform OCR
            $response = $this->client->annotateImage($image, [$feature]);

            // Extract text
            $extractedText = '';
            $confidence = 0.0;

            if ($response->hasFullTextAnnotation()) {
                $annotation = $response->getFullTextAnnotation();
                $extractedText = $annotation->getText();

                // Calculate average confidence from all pages
                $pages = $annotation->getPages();
                $totalConfidence = 0;
                $blockCount = 0;

                foreach ($pages as $page) {
                    foreach ($page->getBlocks() as $block) {
                        $totalConfidence += $block->getConfidence();
                        $blockCount++;
                    }
                }

                $confidence = $blockCount > 0 ? ($totalConfidence / $blockCount) : 0.0;
            }

            // Calculate processing time
            $endTime = microtime(true);
            $processingTimeMs = intval(($endTime - $startTime) * 1000);

            Log::info('Google Vision OCR completed', [
                'image_path' => $imagePath,
                'text_length' => strlen($extractedText),
                'time_ms' => $processingTimeMs,
                'confidence' => round($confidence * 100, 2) . '%',
            ]);

            return [
                'text' => $extractedText,
                'time_ms' => $processingTimeMs,
                'confidence' => $confidence,
            ];

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $processingTimeMs = intval(($endTime - $startTime) * 1000);

            Log::error('Google Vision OCR failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
                'time_ms' => $processingTimeMs,
            ]);

            return [
                'text' => '',
                'time_ms' => $processingTimeMs,
                'confidence' => 0.0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract text from PDF page (batch processing)
     *
     * @param array $imagePaths Array of image paths
     * @return array Array of results with text, time_ms, and confidence
     */
    public function extractTextBatch(array $imagePaths): array
    {
        $results = [];

        foreach ($imagePaths as $imagePath) {
            $results[] = $this->extractText($imagePath);
        }

        return $results;
    }

    /**
     * Get OCR usage statistics
     *
     * @return array Usage info and pricing estimate
     */
    public function getUsageInfo(): array
    {
        return [
            'provider' => 'Google Cloud Vision API',
            'pricing' => [
                'free_tier' => '1,000 pages per month',
                'paid_rate' => '$1.50 per 1,000 pages',
            ],
            'features' => [
                'document_text_detection',
                'handwriting_recognition',
                'multi_language_support',
                'confidence_scoring',
            ],
        ];
    }

    /**
     * Clean up resources
     */
    public function __destruct()
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }
}
