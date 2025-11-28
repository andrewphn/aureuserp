<?php

namespace App\Filament\Resources\PdfDocumentResource\Pages;

use App\Filament\Resources\PdfDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

/**
 * Edit Pdf Document class
 *
 * @see \Filament\Resources\Resource
 */
class EditPdfDocument extends EditRecord
{
    protected static string $resource = PdfDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Mutate Form Data Before Save
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update file size if file was changed
        if (isset($data['file_path']) && $data['file_path'] !== $this->record->file_path) {
            $filePath = Storage::disk('public')->path($data['file_path']);
            if (file_exists($filePath)) {
                $data['file_size'] = filesize($filePath);
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
