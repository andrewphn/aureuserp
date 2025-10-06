<?php

namespace App\Services;

use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\RawDocument;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Google Cloud Document AI Service
 *
 * Handles OCR text extraction using Google Cloud Document AI API
 * Optimized for PDF documents with better accuracy than Vision API
 *
 * Features:
 * - Enterprise Document OCR processor
 * - Automatic rotation correction
 * - Image quality scoring
 * - Table and form detection
 * - Multi-language support
 */
class GoogleDocumentAiService
{
    protected string $projectId;
    protected string $location;
    protected string $processorId;

    /**
     * Initialize Google Document AI client
     *
     * Uses Application Default Credentials (ADC) for authentication
     * Credentials are loaded from: ~/.config/gcloud/application_default_credentials.json
     */
    public function __construct()
    {
        $this->projectId = config('services.google.project_id');
        $this->location = config('services.google.location', 'us'); // 'us' or 'eu'

        // Document AI requires a processor ID
        $this->processorId = config('services.google.document_ai_processor_id');

        if (empty($this->projectId)) {
            throw new \Exception('Google Cloud project ID not configured. Set GOOGLE_PROJECT_ID in .env');
        }

        if (empty($this->processorId)) {
            throw new \Exception('Document AI processor ID not configured. Set GOOGLE_DOCUMENT_AI_PROCESSOR_ID in .env');
        }

        // Set Google Application Credentials path for ADC
        // This allows PHP SDK to automatically find and use credentials
        $adcPath = getenv('HOME') . '/.config/gcloud/application_default_credentials.json';
        if (file_exists($adcPath)) {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $adcPath);
        }
    }

    /**
     * Extract text from an image/PDF using Document AI OCR
     *
     * @param string $imagePath Path to image file (thumbnail or PDF page image)
     * @param string $mimeType MIME type of the document (e.g., 'image/png', 'application/pdf')
     * @return array ['text' => string, 'time_ms' => int, 'confidence' => float]
     */
    public function extractText(string $imagePath, string $mimeType = 'image/png'): array
    {
        $startTime = microtime(true);

        try {
            // Load image from storage
            if (!Storage::disk('public')->exists($imagePath)) {
                throw new \Exception("Image file not found: {$imagePath}");
            }

            $imageContent = Storage::disk('public')->get($imagePath);

            // Initialize client with location-specific endpoint
            // Uses Application Default Credentials (ADC) automatically
            $endpoint = $this->location === 'eu'
                ? 'eu-documentai.googleapis.com'
                : 'us-documentai.googleapis.com';

            $client = new DocumentProcessorServiceClient([
                'apiEndpoint' => $endpoint,
                // ADC is used automatically when 'credentials' is not specified
                // and GOOGLE_APPLICATION_CREDENTIALS environment variable is set
            ]);

            // Build processor name
            $processorName = $client->processorName(
                $this->projectId,
                $this->location,
                $this->processorId
            );

            // Create raw document
            $rawDocument = new RawDocument();
            $rawDocument->setContent($imageContent);
            $rawDocument->setMimeType($mimeType);

            // Create process request
            $request = new ProcessRequest();
            $request->setName($processorName);
            $request->setRawDocument($rawDocument);

            // Process document
            $response = $client->processDocument($request);

            // Extract text and confidence
            $document = $response->getDocument();
            $extractedText = $document->getText();

            // Calculate average confidence from pages
            $confidence = 0.0;
            $pageCount = 0;

            foreach ($document->getPages() as $page) {
                if ($page->hasConfidence()) {
                    $confidence += $page->getConfidence();
                    $pageCount++;
                }
            }

            $confidence = $pageCount > 0 ? ($confidence / $pageCount) : 0.0;

            // Calculate processing time
            $endTime = microtime(true);
            $processingTimeMs = intval(($endTime - $startTime) * 1000);

            Log::info('Google Document AI OCR completed', [
                'image_path' => $imagePath,
                'text_length' => strlen($extractedText),
                'time_ms' => $processingTimeMs,
                'confidence' => round($confidence * 100, 2) . '%',
                'processor' => 'Enterprise Document OCR',
            ]);

            $client->close();

            return [
                'text' => $extractedText,
                'time_ms' => $processingTimeMs,
                'confidence' => $confidence,
            ];

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $processingTimeMs = intval(($endTime - $startTime) * 1000);

            Log::error('Google Document AI OCR failed', [
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
     * Extract text from PDF directly (without converting to images)
     *
     * @param string $pdfPath Path to PDF file in storage
     * @return array ['text' => string, 'time_ms' => int, 'confidence' => float, 'pages' => array]
     */
    public function extractFromPdf(string $pdfPath): array
    {
        $startTime = microtime(true);

        try {
            if (!Storage::disk('public')->exists($pdfPath)) {
                throw new \Exception("PDF file not found: {$pdfPath}");
            }

            $pdfContent = Storage::disk('public')->get($pdfPath);

            $endpoint = $this->location === 'eu'
                ? 'eu-documentai.googleapis.com'
                : 'us-documentai.googleapis.com';

            $client = new DocumentProcessorServiceClient([
                'apiEndpoint' => $endpoint,
                // ADC is used automatically
            ]);

            $processorName = $client->processorName(
                $this->projectId,
                $this->location,
                $this->processorId
            );

            // Create raw PDF document
            $rawDocument = new RawDocument();
            $rawDocument->setContent($pdfContent);
            $rawDocument->setMimeType('application/pdf');

            $request = new ProcessRequest();
            $request->setName($processorName);
            $request->setRawDocument($rawDocument);

            // Process entire PDF
            $response = $client->processDocument($request);
            $document = $response->getDocument();

            // Extract text per page
            $pages = [];
            $totalConfidence = 0;
            $pageCount = count($document->getPages());

            foreach ($document->getPages() as $index => $page) {
                $pageText = '';

                // Extract text from page
                foreach ($page->getParagraphs() as $paragraph) {
                    $layout = $paragraph->getLayout();
                    if ($layout && $layout->getTextAnchor()) {
                        foreach ($layout->getTextAnchor()->getTextSegments() as $segment) {
                            $startIndex = $segment->getStartIndex();
                            $endIndex = $segment->getEndIndex();
                            $pageText .= substr($document->getText(), $startIndex, $endIndex - $startIndex);
                        }
                    }
                }

                $pageConfidence = $page->hasConfidence() ? $page->getConfidence() : 0.0;
                $totalConfidence += $pageConfidence;

                $pages[] = [
                    'page_number' => $index + 1,
                    'text' => $pageText,
                    'confidence' => $pageConfidence,
                ];
            }

            $avgConfidence = $pageCount > 0 ? ($totalConfidence / $pageCount) : 0.0;
            $endTime = microtime(true);
            $processingTimeMs = intval(($endTime - $startTime) * 1000);

            Log::info('Google Document AI PDF extraction completed', [
                'pdf_path' => $pdfPath,
                'page_count' => $pageCount,
                'time_ms' => $processingTimeMs,
                'avg_confidence' => round($avgConfidence * 100, 2) . '%',
            ]);

            $client->close();

            return [
                'text' => $document->getText(),
                'time_ms' => $processingTimeMs,
                'confidence' => $avgConfidence,
                'pages' => $pages,
            ];

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $processingTimeMs = intval(($endTime - $startTime) * 1000);

            Log::error('Google Document AI PDF extraction failed', [
                'pdf_path' => $pdfPath,
                'error' => $e->getMessage(),
                'time_ms' => $processingTimeMs,
            ]);

            return [
                'text' => '',
                'time_ms' => $processingTimeMs,
                'confidence' => 0.0,
                'pages' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get OCR usage statistics
     *
     * @return array Usage info and pricing estimate
     */
    public function getUsageInfo(): array
    {
        return [
            'provider' => 'Google Cloud Document AI',
            'processor_type' => 'Enterprise Document OCR',
            'pricing' => [
                'free_tier' => 'First 1,000 pages per month free',
                'paid_rate' => '$1.50 per 1,000 pages',
            ],
            'features' => [
                'enterprise_document_ocr',
                'automatic_rotation_correction',
                'image_quality_scoring',
                'table_detection',
                'form_detection',
                'multi_language_support',
                'handwriting_recognition',
            ],
            'advantages_over_vision_api' => [
                'Better accuracy for documents',
                'PDF-specific optimizations',
                'Layout preservation',
                'Advanced preprocessing',
            ],
        ];
    }
}
