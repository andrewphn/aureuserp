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
     * Extract text from a page
     *
     * Basic implementation - can be enhanced with pdftotext or OCR
     *
     * @param string $pdfPath
     * @param int $pageNumber
     * @return string|null
     */
    public function extractText(string $pdfPath, int $pageNumber): ?string
    {
        try {
            // Method 1: Try using pdftotext command (if available)
            if ($this->isPdfToTextAvailable()) {
                return $this->extractTextWithPdfToText($pdfPath, $pageNumber);
            }

            // Method 2: Fallback to Imagick OCR (basic, not accurate)
            return $this->extractTextWithImagick($pdfPath, $pageNumber);

        } catch (\Exception $e) {
            Log::warning("Text extraction failed", [
                'page' => $pageNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if pdftotext command is available
     *
     * @return bool
     */
    protected function isPdfToTextAvailable(): bool
    {
        exec('which pdftotext', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Extract text using pdftotext command
     *
     * @param string $pdfPath
     * @param int $pageNumber
     * @return string|null
     */
    protected function extractTextWithPdfToText(string $pdfPath, int $pageNumber): ?string
    {
        try {
            $tempFile = storage_path('app/temp/text-' . uniqid() . '.txt');

            // Create temp directory if it doesn't exist
            @mkdir(dirname($tempFile), 0755, true);

            // Extract text for specific page
            $command = sprintf(
                'pdftotext -f %d -l %d %s %s 2>&1',
                $pageNumber,
                $pageNumber,
                escapeshellarg($pdfPath),
                escapeshellarg($tempFile)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($tempFile)) {
                $text = file_get_contents($tempFile);
                @unlink($tempFile); // Clean up temp file
                return trim($text) ?: null;
            }

            return null;

        } catch (\Exception $e) {
            Log::warning("pdftotext extraction failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract text using Imagick (basic, not very accurate)
     *
     * @param string $pdfPath
     * @param int $pageNumber
     * @return string|null
     */
    protected function extractTextWithImagick(string $pdfPath, int $pageNumber): ?string
    {
        try {
            // Note: Imagick doesn't have built-in OCR
            // This is a placeholder - real OCR would require Tesseract or similar
            // For now, return null and log that OCR is not available
            Log::info("Basic Imagick text extraction not implemented (requires Tesseract OCR)", [
                'page' => $pageNumber,
            ]);

            return null;

        } catch (\Exception $e) {
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
