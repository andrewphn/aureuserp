<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Support\Models\ActivityPlan;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

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
                ->columnSpanFull(),

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
                ->helperText('Select the type of document(s) you are uploading')
                ->columnSpanFull(),

            Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->helperText('Optional notes about these documents')
                ->columnSpanFull(),
        ];
    }

    public function uploadPdfs()
    {
        if (!empty($this->data['new_pdfs'])) {
            $documentType = $this->data['document_type'] ?? 'drawing';
            $notes = $this->data['notes'] ?? null;

            foreach ($this->data['new_pdfs'] as $pdfPath) {
                $filename = basename($pdfPath);
                $fileSize = Storage::disk('public')->size($pdfPath);

                $this->record->pdfDocuments()->create([
                    'file_path' => $pdfPath,
                    'file_name' => $filename,
                    'file_size' => $fileSize,
                    'mime_type' => 'application/pdf',
                    'document_type' => $documentType,
                    'notes' => $notes,
                    'uploaded_by' => Auth::id(),
                ]);
            }

            Notification::make()
                ->success()
                ->title('PDF(s) uploaded successfully')
                ->send();

            // Refresh the page to show the newly uploaded PDFs
            return redirect()->to($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource)
                ->setActivityPlans($this->getActivityPlans()),
            Action::make('uploadPdfsModal')
                ->label('Upload PDFs')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Upload PDF Documents')
                ->modalDescription('Upload architectural plans, blueprints, or technical drawings with metadata.')
                ->modalSubmitActionLabel('Upload')
                ->form($this->getUploadForm())
                ->action(fn () => $this->uploadPdfs()),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('projects::filament/resources/project/pages/view-project.header-actions.delete.notification.title'))
                        ->body(__('projects::filament/resources/project/pages/view-project.header-actions.delete.notification.body')),
                ),
        ];
    }

    private function getActivityPlans(): mixed
    {
        return ActivityPlan::where('plugin', 'projects')->pluck('name', 'id');
    }
}
