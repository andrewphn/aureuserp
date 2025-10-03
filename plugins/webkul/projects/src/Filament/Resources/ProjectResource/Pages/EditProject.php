<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Support\Models\ActivityPlan;

class EditProject extends EditRecord implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProjectResource::class;

    public static bool $formActionsAreSticky = true;

    public function uploadForm(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('new_pdfs')
                    ->label('Select PDF Files')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(51200)
                    ->disk('public')
                    ->directory('pdf-documents')
                    ->multiple()
                    ->reorderable()
                    ->helperText('Maximum file size: 50MB per file. You can upload multiple PDFs.')
                    ->required(),
            ])
            ->statePath('uploadData');
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.pages.project-sticky-footer', ['page' => $this]);
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

    public function uploadPdfs()
    {
        $data = $this->uploadForm->getState();

        if (!empty($data['new_pdfs'])) {
            foreach ($data['new_pdfs'] as $pdfPath) {
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

            $this->uploadForm->fill();

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
