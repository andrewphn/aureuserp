<?php

namespace App\Filament\Resources\PdfDocumentResource\Pages;

use App\Filament\Resources\PdfDocumentResource;
use App\Models\PdfDocument;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Create Pdf Document class
 *
 * @see \Filament\Resources\Resource
 */
class CreatePdfDocument extends CreateRecord
{
    protected static string $resource = PdfDocumentResource::class;

    /**
     * Store page data for creating PdfPage records after create
     */
    protected array $pageData = [];

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set uploaded_by to current user
        $data['uploaded_by'] = Auth::id();

        // Get file size if file was uploaded
        if (isset($data['file_path']) && $data['file_path']) {
            $filePath = Storage::disk('public')->path($data['file_path']);
            if (file_exists($filePath)) {
                $data['file_size'] = filesize($filePath);

                // Extract page count and dimensions from PDF
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($filePath);
                    $pages = $pdf->getPages();
                    $data['page_count'] = count($pages);

                    // Store page data for afterCreate hook
                    $this->pageData = [];
                    foreach ($pages as $index => $page) {
                        $details = $page->getDetails();
                        $this->pageData[] = [
                            'page_number' => $index + 1,
                            'width' => $details['MediaBox'][2] ?? null,
                            'height' => $details['MediaBox'][3] ?? null,
                            'rotation' => $details['Rotate'] ?? 0,
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning('Could not extract page count from PDF: ' . $e->getMessage());
                    $data['page_count'] = 0;
                }
            }
        }

        // Set MIME type
        $data['mime_type'] = 'application/pdf';

        return $data;
    }

    /**
     * After Create - Create PdfPage records
     */
    protected function afterCreate(): void
    {
        // Create PdfPage records for each page
        if (!empty($this->pageData)) {
            foreach ($this->pageData as $page) {
                \App\Models\PdfPage::create([
                    'document_id' => $this->record->id,
                    'page_number' => $page['page_number'],
                    'width' => $page['width'],
                    'height' => $page['height'],
                    'rotation' => $page['rotation'],
                ]);
            }
            \Log::info("Created " . count($this->pageData) . " PdfPage records for document {$this->record->id}");
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
