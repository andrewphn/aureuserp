<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Support\Models\ActivityPlan;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    public static bool $formActionsAreSticky = true;

    public $uploadData = [];

    public function getUploadForm(): array
    {
        return [
            FileUpload::make('new_pdfs')
                ->label('Select PDF Files')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(51200)
                ->disk('public')
                ->directory('pdf-documents')
                ->multiple()
                ->reorderable()
                ->helperText('Maximum file size: 50MB per file. You can upload multiple PDFs.')
                ->required()
                ->statePath('uploadData.new_pdfs'),
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.pages.project-sticky-footer', ['page' => $this]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load project address from database if it exists
        if ($this->record->addresses()->count() > 0) {
            $address = $this->record->addresses()->where('is_primary', true)->first()
                       ?? $this->record->addresses()->first();

            $data['project_address'] = [
                'street1' => $address->street1,
                'street2' => $address->street2,
                'city' => $address->city,
                'zip' => $address->zip,
                'country_id' => $address->country_id,
                'state_id' => $address->state_id,
            ];

            // Set use_customer_address to false since we have a project-specific address
            $data['use_customer_address'] = false;
        }

        return $data;
    }

    public function deletePdf($pdfId)
    {
        $pdf = $this->record->pdfDocuments()->find($pdfId);

        if ($pdf) {
            // Delete the file from storage
            if (Storage::disk('public')->exists($pdf->file_path)) {
                Storage::disk('public')->delete($pdf->file_path);
            }

            // Delete the database record
            $pdf->delete();

            Notification::make()
                ->success()
                ->title('PDF deleted successfully')
                ->send();
        } else {
            Notification::make()
                ->danger()
                ->title('PDF not found')
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('projects::filament/resources/project/pages/edit-project.notification.title'))
            ->body(__('projects::filament/resources/project/pages/edit-project.notification.body'));
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        // Update or create project address
        if (!empty($data['project_address'])) {
            $addressData = $data['project_address'];

            if (!empty($addressData['street1']) || !empty($addressData['city'])) {
                // Update existing or create new address
                $address = $this->record->addresses()->where('is_primary', true)->first()
                           ?? $this->record->addresses()->first();

                if ($address) {
                    $address->update([
                        'street1' => $addressData['street1'] ?? null,
                        'street2' => $addressData['street2'] ?? null,
                        'city' => $addressData['city'] ?? null,
                        'zip' => $addressData['zip'] ?? null,
                        'country_id' => $addressData['country_id'] ?? null,
                        'state_id' => $addressData['state_id'] ?? null,
                    ]);
                } else {
                    $this->record->addresses()->create([
                        'type' => 'project',
                        'street1' => $addressData['street1'] ?? null,
                        'street2' => $addressData['street2'] ?? null,
                        'city' => $addressData['city'] ?? null,
                        'zip' => $addressData['zip'] ?? null,
                        'country_id' => $addressData['country_id'] ?? null,
                        'state_id' => $addressData['state_id'] ?? null,
                        'is_primary' => true,
                    ]);
                }
            }
        }

        // Save architectural PDFs if uploaded
        if (!empty($data['architectural_pdfs'])) {
            foreach ($data['architectural_pdfs'] as $pdfPath) {
                $filename = basename($pdfPath);
                $fileSize = Storage::disk('public')->size($pdfPath);

                $this->record->pdfDocuments()->create([
                    'file_path' => $pdfPath,
                    'file_name' => $filename,
                    'file_size' => $fileSize,
                    'mime_type' => 'application/pdf',
                    'document_type' => 'drawing', // Default to drawing/blueprint
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }
    }

    public function uploadPdfs()
    {
        if (!empty($this->uploadData['new_pdfs'])) {
            foreach ($this->uploadData['new_pdfs'] as $pdfPath) {
                $filename = basename($pdfPath);
                $fileSize = Storage::disk('public')->size($pdfPath);

                $this->record->pdfDocuments()->create([
                    'file_path' => $pdfPath,
                    'file_name' => $filename,
                    'file_size' => $fileSize,
                    'mime_type' => 'application/pdf',
                    'document_type' => 'drawing',
                    'uploaded_by' => Auth::id(),
                ]);
            }

            $this->uploadData = [];

            Notification::make()
                ->success()
                ->title('PDF(s) uploaded successfully')
                ->send();

            $this->dispatch('close-modal', id: 'upload-pdf-modal');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource)
                ->setActivityPlans($this->getActivityPlans()),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('projects::filament/resources/project/pages/edit-project.header-actions.delete.notification.title'))
                        ->body(__('projects::filament/resources/project/pages/edit-project.header-actions.delete.notification.body')),
                ),
        ];
    }

    private function getActivityPlans(): mixed
    {
        return ActivityPlan::where('plugin', 'projects')->pluck('name', 'id');
    }
}
