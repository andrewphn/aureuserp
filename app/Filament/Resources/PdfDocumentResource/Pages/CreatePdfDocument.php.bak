<?php

namespace App\Filament\Resources\PdfDocumentResource\Pages;

use App\Filament\Resources\PdfDocumentResource;
use App\Models\PdfDocument;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreatePdfDocument extends CreateRecord
{
    protected static string $resource = PdfDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set uploaded_by to current user
        $data['uploaded_by'] = Auth::id();

        // Get file size if file was uploaded
        if (isset($data['file_path']) && $data['file_path']) {
            $filePath = Storage::disk('public')->path($data['file_path']);
            if (file_exists($filePath)) {
                $data['file_size'] = filesize($filePath);
            }
        }

        // Set MIME type
        $data['mime_type'] = 'application/pdf';

        // Default page count to 0 (will be updated later if needed)
        $data['page_count'] = $data['page_count'] ?? 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
