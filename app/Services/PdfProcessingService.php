<?php

namespace App\Services;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickException;

/**
 * PDF Processing Service
 *
 * Handles all PDF processing operations including:
 * - Page extraction and counting
 * - Thumbnail generation
 * - Text extraction
 * - Metadata parsing
 */
class PdfProcessingService
{
    protected int $thumbnailWidth = 300;
    protected int $thumbnailHeight = 400;
    protected int $thumbnailQuality = 80;

    /**
     * Process a PDF document completely
     *
     * @param PdfDocument $document
     * @return bool
     * @throws \Exception
     */
    public function processPdf(PdfDocument $document): bool
    {
        try {
            Log::info("Starting PDF processing", [
                'document_id' => $document->id,
                'file_name' => $document->file_name,
            ]);

            // Get full path to PDF
            $pdfPath = Storage::disk('public')->path($document->file_path);

            if (!file_exists($pdfPath)) {
                throw new \Exception("PDF file not found: {$pdfPath}");
            }

            // Extract page count
            $pageCount = $this->getPageCount($pdfPath);
            $document->page_count = $pageCount;
            $document->save();

            Log::info("PDF has {$pageCount} pages", ['document_id' => $document->id]);

            // Process each page
            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $this->processPage($document, $pdfPath, $pageNumber);
            }

            Log::info("PDF processing completed successfully", [
                'document_id' => $document->id,
                'pages_processed' => $pageCount,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("PDF processing failed", [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single page
     *
     * @param PdfDocument $document
     * @param string $pdfPath
     * @param int $pageNumber
     * @return PdfPage
     */
    protected function processPage(PdfDocument $document, string $pdfPath, int $pageNumber): PdfPage
    {
        Log::info("Processing page {$pageNumber}", ['document_id' => $document->id]);

        // Get page dimensions
        $dimensions = $this->getPageDimensions($pdfPath, $pageNumber);

        // Generate thumbnail
        $thumbnailPath = $this->generateThumbnail($pdfPath, $pageNumber, $document->id);

        // Extract text (basic - can be enhanced with OCR later)
        $extractedText = $this->extractText($pdfPath, $pageNumber);

        // Create or update page record
        $page = PdfPage::updateOrCreate(
            [
                'document_id' => $document->id,
                'page_number' => $pageNumber,
            ],
            [
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
                'thumbnail_path' => $thumbnailPath,
                'extracted_text' => $extractedText,
                'page_metadata' => [
                    'rotation' => $dimensions['rotation'] ?? 0,
                    'resolution' => $dimensions['resolution'] ?? null,
                ],
            ]
        );

        Log::info("Page {$pageNumber} processed successfully", [
            'document_id' => $document->id,
            'page_id' => $page->id,
            'thumbnail' => $thumbnailPath,
            'text_length' => strlen($extractedText ?? ''),
        ]);

        return $page;
    }

    /**
     * Get total page count from PDF
     *
     * @param string $pdfPath
     * @return int
     * @throws ImagickException
     */
    public function getPageCount(string $pdfPath): int
    {
        $imagick = new Imagick();
        $imagick->readImage($pdfPath);
        $pageCount = $imagick->getNumberImages();
        $imagick->clear();
        $imagick->destroy();

        return $pageCount;
    }

    /**
     * Generate thumbnail for a specific page
     *
     * @param string $pdfPath
     * @param int $pageNumber
     * @param int $documentId
     * @return string|null Relative path to thumbnail
     */
    public function generateThumbnail(string $pdfPath, int $pageNumber, int $documentId): ?string
    {
        try {
            $imagick = new Imagick();

            // Read specific page (Imagick uses 0-based index)
            $imagick->readImage($pdfPath . '[' . ($pageNumber - 1) . ']');

            // Set format to JPEG
            $imagick->setImageFormat('jpg');
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality($this->thumbnailQuality);

            // Resize to thumbnail size (maintain aspect ratio)
            $imagick->thumbnailImage($this->thumbnailWidth, $this->thumbnailHeight, true);

            // Generate filename and path
            $thumbnailDirectory = "pdf-thumbnails/{$documentId}";
            $thumbnailFilename = "page-{$pageNumber}.jpg";
            $thumbnailRelativePath = "{$thumbnailDirectory}/{$thumbnailFilename}";

            // Ensure directory exists
            Storage::disk('public')->makeDirectory($thumbnailDirectory);

            // Save thumbnail
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailRelativePath);
            $imagick->writeImage($thumbnailFullPath);

            // Clean up
            $imagick->clear();
            $imagick->destroy();

            Log::info("Thumbnail generated", [
                'page' => $pageNumber,
                'path' => $thumbnailRelativePath,
            ]);

            return $thumbnailRelativePath;

        } catch (ImagickException $e) {
            Log::error("Failed to generate thumbnail", [
                'page' => $pageNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get page dimensions
     *
     * @param string $pdfPath
     * @param int $pageNumber
     * @return array
     */
    public function getPageDimensions(string $pdfPath, int $pageNumber): array
    {
        try {
            $imagick = new Imagick();
            $imagick->readImage($pdfPath . '[' . ($pageNumber - 1) . ']');

            $geometry = $imagick->getImageGeometry();
            $resolution = $imagick->getImageResolution();

            $dimensions = [
                'width' => $geometry['width'],
                'height' => $geometry['height'],
                'resolution' => $resolution['x'] ?? null,
                'rotation' => 0, // Could be detected if needed
            ];

            $imagick->clear();
            $imagick->destroy();

            return $dimensions;

        } catch (ImagickException $e) {
            Log::error("Failed to get page dimensions", [
                'page' => $pageNumber,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract text from a page using smalot/pdfparser
     *
     * @param string $pdfPath
     * @param int $pageNumber
     * @return string|null
     */
    public function extractText(string $pdfPath, int $pageNumber): ?string
    {
        try {
            // Use smalot/pdfparser for pure PHP text extraction
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);

            // Get all pages
            $pages = $pdf->getPages();

            // Check if requested page exists (1-based index)
            if (!isset($pages[$pageNumber - 1])) {
                return null;
            }

            // Extract text from specific page
            $page = $pages[$pageNumber - 1];
            $text = $page->getText();

            // Clean up whitespace
            $text = trim(preg_replace('/\s+/', ' ', $text));

            return $text ?: null;

        } catch (\Exception $e) {
            Log::warning("Text extraction failed", [
                'page' => $pageNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Set thumbnail dimensions
     *
     * @param int $width
     * @param int $height
     * @return self
     */
    public function setThumbnailDimensions(int $width, int $height): self
    {
        $this->thumbnailWidth = $width;
        $this->thumbnailHeight = $height;
        return $this;
    }

    /**
     * Set thumbnail quality
     *
     * @param int $quality (0-100)
     * @return self
     */
    public function setThumbnailQuality(int $quality): self
    {
        $this->thumbnailQuality = max(0, min(100, $quality));
        return $this;
    }
}
