<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Generates PDF Files for Testing
 *
 * Provides helper methods to create fake PDF files for testing
 * without requiring actual PDF libraries.
 */
trait GeneratesPdfFiles
{
    /**
     * Create a fake PDF file for testing
     *
     * @param string $filename
     * @param int $sizeInKB
     * @return \Illuminate\Http\UploadedFile
     */
    protected function createFakePdf(string $filename = 'test.pdf', int $sizeInKB = 1024): UploadedFile
    {
        return UploadedFile::fake()->create($filename, $sizeInKB, 'application/pdf');
    }

    /**
     * Create a large fake PDF file
     *
     * @param int $sizeInMB
     * @return \Illuminate\Http\UploadedFile
     */
    protected function createLargePdf(int $sizeInMB = 50): UploadedFile
    {
        return UploadedFile::fake()->create('large-document.pdf', $sizeInMB * 1024, 'application/pdf');
    }

    /**
     * Create multiple PDF files
     *
     * @param int $count
     * @param int $sizeInKB
     * @return array
     */
    protected function createMultiplePdfs(int $count = 5, int $sizeInKB = 1024): array
    {
        $files = [];

        for ($i = 1; $i <= $count; $i++) {
            $files[] = $this->createFakePdf("document-{$i}.pdf", $sizeInKB);
        }

        return $files;
    }

    /**
     * Create a PDF with specific content
     *
     * @param string $content
     * @return string Path to the created PDF
     */
    protected function createPdfWithContent(string $content): string
    {
        $pdfContent = "%PDF-1.4\n{$content}";
        $path = 'test-pdfs/' . uniqid() . '.pdf';

        Storage::disk('local')->put($path, $pdfContent);

        return Storage::disk('local')->path($path);
    }

    /**
     * Create a corrupted PDF file
     *
     * @return \Illuminate\Http\UploadedFile
     */
    protected function createCorruptedPdf(): UploadedFile
    {
        $file = UploadedFile::fake()->create('corrupted.pdf', 100, 'application/pdf');

        // Overwrite with invalid content
        file_put_contents($file->getPathname(), 'This is not a valid PDF');

        return $file;
    }

    /**
     * Seed sample PDF files for testing
     *
     * @return array Array of file paths
     */
    protected function seedSamplePdfs(): array
    {
        $paths = [];

        // Create sample PDF files in storage
        for ($i = 1; $i <= 3; $i++) {
            $path = "test-pdfs/sample-{$i}.pdf";
            Storage::disk('public')->put($path, $this->generateMinimalPdfContent());
            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * Generate minimal valid PDF content
     *
     * @return string
     */
    protected function generateMinimalPdfContent(): string
    {
        return <<<PDF
%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/Resources <<
/Font <<
/F1 <<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
>>
>>
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<< /Length 44 >>
stream
BT
/F1 12 Tf
100 700 Td
(Test PDF) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f
0000000009 00000 n
0000000058 00000 n
0000000115 00000 n
0000000310 00000 n
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
408
%%EOF
PDF;
    }

    /**
     * Clean up test PDF files
     */
    protected function cleanupTestPdfs(): void
    {
        Storage::disk('public')->deleteDirectory('test-pdfs');
    }
}
