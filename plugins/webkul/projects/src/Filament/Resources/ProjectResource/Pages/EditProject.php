<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

        // PDF uploads are now handled exclusively through the Upload PDFs modal action
        // This ensures all PDFs are uploaded with proper metadata (document_type, notes)
    }


    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource)
                ->setActivityPlans($this->getActivityPlans()),
            Action::make('uploadPdf')
                ->label('Upload PDFs')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('file_path')
                        ->label('PDF Document')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(51200)
                        ->disk('public')
                        ->directory('pdf-documents')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $filename = is_string($state) ? basename($state) : (is_object($state) ? $state->getClientOriginalName() : null);
                                if ($filename) {
                                    $set('file_name', $filename);
                                }
                            }
                        }),

                    TextInput::make('file_name')
                        ->label('File Name')
                        ->placeholder('Auto-filled from uploaded file')
                        ->disabled()
                        ->dehydrated(),

                    Select::make('document_type')
                        ->label('Document Type')
                        ->options([
                            'drawing' => 'Architectural Drawing',
                            'blueprint' => 'Blueprint',
                            'specification' => 'Specification',
                            'contract' => 'Contract',
                            'permit' => 'Permit',
                            'photo' => 'Photo/Image',
                            'other' => 'Other',
                        ])
                        ->default('drawing')
                        ->required()
                        ->helperText('Select the type of document you are uploading'),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->helperText('Optional notes about this document'),
                ])
                ->action(function (array $data) {
                    // Get file size
                    if (empty($data['file_size']) && !empty($data['file_path'])) {
                        $data['file_size'] = Storage::disk('public')->size($data['file_path']);
                    }

                    // Set mime type
                    if (empty($data['mime_type'])) {
                        $data['mime_type'] = 'application/pdf';
                    }

                    // Create PDF document
                    $this->record->pdfDocuments()->create([
                        'file_path' => $data['file_path'],
                        'file_name' => $data['file_name'] ?? basename($data['file_path']),
                        'file_size' => $data['file_size'],
                        'mime_type' => $data['mime_type'],
                        'document_type' => $data['document_type'],
                        'notes' => $data['notes'] ?? null,
                        'uploaded_by' => Auth::id(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('PDF uploaded successfully')
                        ->send();
                }),
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
