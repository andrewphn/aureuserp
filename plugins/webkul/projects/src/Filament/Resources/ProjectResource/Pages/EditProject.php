<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Filament\Resources\ProjectResource\Actions\CloneProjectAction;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Support\Models\ActivityPlan;

/**
 * Edit Project class
 *
 * @see \Filament\Resources\Resource
 */
class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    public static bool $formActionsAreSticky = true;

    /**
     * Mount
     *
     * @return void
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Set active context WITHOUT form data on edit pages
        // This prevents EntityStore from syncing data back and blocking save button
        $this->dispatch('set-active-context', [
            'entityType' => 'project',
            'entityId' => $this->record->id,
            // NO 'data' parameter - prevents EntityStore circular sync that blocks save button
        ]);
    }

    /**
     * Get formatted data for footer display
     */
    protected function getFooterData(): array
    {
        try {
            // Use getRawState() to avoid validation issues
            $formData = $this->form->getRawState();

            return [
                'id' => $this->record->id,
                'project_number' => $formData['project_number'] ?? $this->record->project_number,
                'partner_id' => $formData['partner_id'] ?? $this->record->partner_id,
                'company_id' => $formData['company_id'] ?? $this->record->company_id,
                'branch_id' => $formData['branch_id'] ?? $this->record->branch_id,
                'project_type' => $formData['project_type'] ?? $this->record->project_type,
                'estimated_linear_feet' => $formData['estimated_linear_feet'] ?? $this->record->estimated_linear_feet,
                'allocated_hours' => $formData['allocated_hours'] ?? $this->record->allocated_hours,
                'desired_completion_date' => $formData['desired_completion_date'] ?? $this->record->desired_completion_date,
                'project_address' => $formData['project_address'] ?? null,
            ];
        } catch (\Exception $e) {
            // Fallback to database values if form state unavailable
            return [
                'id' => $this->record->id,
                'project_number' => $this->record->project_number,
                'partner_id' => $this->record->partner_id,
                'company_id' => $this->record->company_id,
                'branch_id' => $this->record->branch_id,
                'project_type' => $this->record->project_type,
                'estimated_linear_feet' => $this->record->estimated_linear_feet,
                'allocated_hours' => $this->record->allocated_hours,
                'desired_completion_date' => $this->record->desired_completion_date,
                'project_address' => null,
            ];
        }
    }

    /**
     * Update footer with current form state
     * Called when fields are updated
     */
    /**
     * Update Footer
     *
     * @return void
     */
    public function updateFooter(): void
    {
        // TEMPORARILY DISABLED to debug save button issue
        // The form change detection might be interfering with footer updates
        return;

        try {
            $this->dispatch('entity-updated', [
                'entityType' => 'project',
                'entityId' => $this->record->id,
                'data' => $this->getFooterData(),
            ]);
        } catch (\Exception $e) {
            // Silently fail - footer update is not critical
            \Log::debug('Footer update failed: ' . $e->getMessage());
        }
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        // Disabled: Using global sticky footer instead to avoid double footer
        return null;
        // return view('filament.pages.project-sticky-footer', ['page' => $this]);
    }

    /**
     * Mutate Form Data Before Fill
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure branch_id is loaded from the database
        $data['branch_id'] = $this->record->branch_id;

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

    /**
     * Delete Pdf
     *
     * @param mixed $pdfId
     */
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

    /**
     * After Save
     *
     * @return void
     */
    protected function afterSave(): void
    {
        $data = $this->form->getState();

        // Clear entity store draft data (saved to database now)
        $this->dispatch('entity-saved', [
            'entityType' => 'project',
            'entityId' => $this->record->id
        ]);

        // Update active context with fresh data
        $this->dispatch('set-active-context', [
            'entityType' => 'project',
            'entityId' => $this->record->id,
            'data' => $data
        ]);

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

        // Clear tag cache when project is saved (tags may have changed)
        Cache::forget('project_tags_most_used');
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

                    // Extract page count and dimensions from PDF
                    $pageCount = null;
                    $pageData = [];
                    try {
                        $fullPath = Storage::disk('public')->path($data['file_path']);
                        if (file_exists($fullPath)) {
                            $parser = new \Smalot\PdfParser\Parser();
                            $pdf = $parser->parseFile($fullPath);
                            $pages = $pdf->getPages();
                            $pageCount = count($pages);

                            // Extract page dimensions for each page
                            foreach ($pages as $index => $page) {
                                $details = $page->getDetails();
                                $pageData[] = [
                                    'page_number' => $index + 1,
                                    'width' => $details['MediaBox'][2] ?? null,
                                    'height' => $details['MediaBox'][3] ?? null,
                                    'rotation' => $details['Rotate'] ?? 0,
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Could not extract page count from PDF: ' . $e->getMessage());
                    }

                    // Create PDF document
                    $pdfDocument = $this->record->pdfDocuments()->create([
                        'file_path' => $data['file_path'],
                        'file_name' => $data['file_name'] ?? basename($data['file_path']),
                        'file_size' => $data['file_size'],
                        'mime_type' => $data['mime_type'],
                        'document_type' => $data['document_type'],
                        'notes' => $data['notes'] ?? null,
                        'page_count' => $pageCount,
                        'uploaded_by' => Auth::id(),
                    ]);

                    // Create PDF page records for each page
                    if ($pageCount && $pageCount > 0) {
                        foreach ($pageData as $page) {
                            \App\Models\PdfPage::create([
                                'document_id' => $pdfDocument->id,
                                'page_number' => $page['page_number'],
                                'width' => $page['width'],
                                'height' => $page['height'],
                                'rotation' => $page['rotation'],
                            ]);
                        }
                        \Log::info("Created {$pageCount} PDF page records for document {$pdfDocument->id}");
                    }

                    Notification::make()
                        ->success()
                        ->title('PDF uploaded successfully')
                        ->body("Uploaded {$pageCount} pages")
                        ->send();
                }),
            CloneProjectAction::make(),
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
