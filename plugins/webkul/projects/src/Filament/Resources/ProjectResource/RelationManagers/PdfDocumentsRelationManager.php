<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\PdfDocument;
use App\Jobs\ProcessPdfJob;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PdfDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'pdfDocuments';

    protected static ?string $recordTitleAttribute = 'file_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('PDF Document')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(51200) // 50MB
                    ->disk('public')
                    ->directory('pdf-documents')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            // Auto-fill file_name from uploaded file
                            $filename = is_string($state) ? basename($state) : (is_object($state) ? $state->getClientOriginalName() : null);
                            if ($filename) {
                                $set('file_name', $filename);
                            }
                        }
                    }),

                Forms\Components\TextInput::make('file_name')
                    ->label('File Name (auto-filled)')
                    ->placeholder('Will be auto-filled from uploaded file'),

                Forms\Components\Select::make('document_type')
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
                    ->helperText('Select the type of document(s) you are uploading'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->helperText('Optional notes about these documents'),

                Forms\Components\Hidden::make('file_size'),
                Forms\Components\Hidden::make('mime_type'),
                Forms\Components\Hidden::make('uploaded_by')
                    ->default(Auth::id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('processing_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'processing' => 'heroicon-o-arrow-path',
                        'completed' => 'heroicon-o-check-circle',
                        'failed' => 'heroicon-o-exclamation-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->tooltip(fn (PdfDocument $record): ?string =>
                        $record->processing_status === 'failed' && $record->processing_error
                            ? "Error: {$record->processing_error}"
                            : null
                    ),

                Tables\Columns\TextColumn::make('page_count')
                    ->label('Pages')
                    ->default('—')
                    ->formatStateUsing(fn (?int $state): string =>
                        $state ? (string) $state : '—'
                    )
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('formatted_file_size')
                    ->label('Size')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not processed'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('processing_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ])
                    ->multiple(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Extract file name from uploaded file path if not provided
                        if (empty($data['file_name']) && !empty($data['file_path'])) {
                            $data['file_name'] = basename($data['file_path']);
                        }

                        // Get file size if not set
                        if (empty($data['file_size']) && !empty($data['file_path'])) {
                            $data['file_size'] = Storage::disk('public')->size($data['file_path']);
                        }

                        // Set mime type if not set
                        if (empty($data['mime_type'])) {
                            $data['mime_type'] = 'application/pdf';
                        }

                        $data['module_type'] = get_class($this->getOwnerRecord());
                        $data['module_id'] = $this->getOwnerRecord()->id;
                        $data['uploaded_by'] = Auth::id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (PdfDocument $record) => 'View PDF: ' . $record->file_name)
                    ->modalContent(fn (PdfDocument $record) => view('filament.modals.pdf-viewer', [
                        'documentId' => $record->id,
                        'documentUrl' => Storage::disk('public')->url($record->file_path),
                    ]))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('reprocess')
                    ->label('Reprocess')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (PdfDocument $record): bool =>
                        in_array($record->processing_status, ['failed', 'pending'])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Reprocess PDF Document')
                    ->modalDescription('This will queue the PDF for reprocessing. Page extraction and thumbnails will be regenerated.')
                    ->action(function (PdfDocument $record) {
                        // Mark as pending and dispatch job
                        $record->update([
                            'processing_status' => 'pending',
                            'processing_error' => null,
                        ]);

                        ProcessPdfJob::dispatch($record);

                        Notification::make()
                            ->title('PDF Queued for Processing')
                            ->body("'{$record->file_name}' has been queued for reprocessing.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('reprocessFailed')
                        ->label('Reprocess Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reprocess Selected PDFs')
                        ->modalDescription('This will queue all selected PDFs for reprocessing.')
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (in_array($record->processing_status, ['failed', 'pending'])) {
                                    $record->update([
                                        'processing_status' => 'pending',
                                        'processing_error' => null,
                                    ]);
                                    ProcessPdfJob::dispatch($record);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title('PDFs Queued for Processing')
                                ->body("{$count} document(s) have been queued for reprocessing.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
