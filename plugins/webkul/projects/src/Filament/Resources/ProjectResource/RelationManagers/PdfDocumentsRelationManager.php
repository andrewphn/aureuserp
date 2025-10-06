<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\PdfDocument;
use App\Jobs\ProcessPdfJob;
use App\Services\PdfDataExtractor;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
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

    /**
     * Extract value from field with confidence scoring
     */
    protected function getFieldValue($field): mixed
    {
        if (is_array($field) && isset($field['value'])) {
            return $field['value'];
        }
        return $field;
    }

    /**
     * Get confidence badge HTML
     */
    protected function getConfidenceBadge($field): ?string
    {
        if (!is_array($field) || !isset($field['confidence'])) {
            return null;
        }

        return match($field['confidence']) {
            'high' => '🟢 High Confidence',
            'medium' => '🟡 Medium Confidence',
            'low' => '🔴 Low Confidence',
            default => null,
        };
    }

    /**
     * Get helper text with confidence level
     */
    protected function getConfidenceHelper($field): ?string
    {
        $badge = $this->getConfidenceBadge($field);
        return $badge ? "Confidence: {$badge}" : null;
    }

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
                Actions\CreateAction::make()
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
                Actions\Action::make('view')
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

                Actions\Action::make('reprocess')
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

                Actions\Action::make('extractData')
                    ->label('Extract Data')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->visible(fn (PdfDocument $record): bool =>
                        $record->processing_status === 'completed' && $record->page_count > 0
                    )
                    ->form(function (PdfDocument $record) {
                        $extractor = app(PdfDataExtractor::class);
                        $extractedData = $record->extracted_metadata ?? $extractor->extractMetadata($record);

                        return [
                            Section::make('Project Information')
                                ->schema([
                                    Forms\Components\TextInput::make('metadata.project.address')
                                        ->label('Project Address')
                                        ->default($extractedData['project']['address'] ?? null),
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('metadata.project.city')
                                                ->label('City')
                                                ->default($extractedData['project']['city'] ?? null),
                                            Forms\Components\TextInput::make('metadata.project.state')
                                                ->label('State')
                                                ->maxLength(2)
                                                ->default($extractedData['project']['state'] ?? null),
                                            Forms\Components\TextInput::make('metadata.project.zip')
                                                ->label('ZIP')
                                                ->maxLength(5)
                                                ->default($extractedData['project']['zip'] ?? null),
                                        ]),
                                    Forms\Components\TextInput::make('metadata.project.type')
                                        ->label('Project Type')
                                        ->default($extractedData['project']['type'] ?? null),
                                ])
                                ->collapsed(empty($extractedData['project'] ?? null)),

                            Section::make('Client Information')
                                ->schema([
                                    Forms\Components\TextInput::make('metadata.client.name')
                                        ->label('Owner Name')
                                        ->default($this->getFieldValue($extractedData['client']['name'] ?? null))
                                        ->helperText($this->getConfidenceHelper($extractedData['client']['name'] ?? null)),
                                    Forms\Components\TextInput::make('metadata.client.company')
                                        ->label('Company')
                                        ->default($this->getFieldValue($extractedData['client']['company'] ?? null))
                                        ->helperText($this->getConfidenceHelper($extractedData['client']['company'] ?? null)),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('metadata.client.email')
                                                ->label('Email')
                                                ->email()
                                                ->default($this->getFieldValue($extractedData['client']['email'] ?? null))
                                                ->helperText($this->getConfidenceHelper($extractedData['client']['email'] ?? null)),
                                            Forms\Components\TextInput::make('metadata.client.phone')
                                                ->label('Phone')
                                                ->tel()
                                                ->default($this->getFieldValue($extractedData['client']['phone'] ?? null))
                                                ->helperText($this->getConfidenceHelper($extractedData['client']['phone'] ?? null)),
                                        ]),
                                    Forms\Components\TextInput::make('metadata.client.website')
                                        ->label('Website')
                                        ->url()
                                        ->default($this->getFieldValue($extractedData['client']['website'] ?? null))
                                        ->helperText($this->getConfidenceHelper($extractedData['client']['website'] ?? null)),
                                ])
                                ->collapsed(empty($extractedData['client'] ?? null)),

                            Section::make('Document Details')
                                ->schema([
                                    Forms\Components\TextInput::make('metadata.document.drawing_file')
                                        ->label('Drawing File')
                                        ->default($extractedData['document']['drawing_file'] ?? null),
                                    Forms\Components\TextInput::make('metadata.document.drawn_by')
                                        ->label('Drawn By')
                                        ->default($extractedData['document']['drawn_by'] ?? null),
                                    Forms\Components\TextInput::make('metadata.document.approved_date')
                                        ->label('Approved Date')
                                        ->default($extractedData['document']['approved_date'] ?? null),
                                    Forms\Components\Repeater::make('metadata.document.revisions')
                                        ->label('Revision History')
                                        ->schema([
                                            Forms\Components\TextInput::make('number')
                                                ->label('Revision #')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('date')
                                                ->label('Date'),
                                        ])
                                        ->default($extractedData['document']['revisions'] ?? [])
                                        ->collapsed()
                                        ->itemLabel(fn (array $state): ?string =>
                                            isset($state['number']) ? "Revision {$state['number']}" : null
                                        ),
                                ])
                                ->collapsed(empty($extractedData['document'] ?? null)),

                            Section::make('Measurements & Linear Feet')
                                ->description('🟢 High confidence fields - extracted from labeled measurements')
                                ->schema([
                                    Forms\Components\Repeater::make('metadata.measurements.tiers')
                                        ->label('Tier Cabinetry')
                                        ->schema([
                                            Forms\Components\TextInput::make('tier')
                                                ->label('Tier #')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('linear_feet')
                                                ->label('Linear Feet')
                                                ->numeric()
                                                ->suffix('LF'),
                                        ])
                                        ->default(collect($extractedData['measurements']['tiers'] ?? [])->map(function($tier) {
                                            return [
                                                'tier' => $this->getFieldValue($tier['tier'] ?? null),
                                                'linear_feet' => $this->getFieldValue($tier['linear_feet'] ?? null),
                                            ];
                                        })->toArray())
                                        ->collapsed()
                                        ->itemLabel(fn (array $state): ?string =>
                                            isset($state['tier']) ? "Tier {$state['tier']}" : null
                                        ),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('metadata.measurements.floating_shelves_lf')
                                                ->label('Floating Shelves')
                                                ->numeric()
                                                ->suffix('LF')
                                                ->default($this->getFieldValue($extractedData['measurements']['floating_shelves_lf'] ?? null))
                                                ->helperText($this->getConfidenceHelper($extractedData['measurements']['floating_shelves_lf'] ?? null)),
                                            Forms\Components\TextInput::make('metadata.measurements.countertops_sf')
                                                ->label('Countertops')
                                                ->numeric()
                                                ->suffix('SF')
                                                ->default($this->getFieldValue($extractedData['measurements']['countertops_sf'] ?? null))
                                                ->helperText($this->getConfidenceHelper($extractedData['measurements']['countertops_sf'] ?? null)),
                                        ]),
                                ])
                                ->collapsed(empty($extractedData['measurements'] ?? null)),

                            Section::make('Equipment & Appliances')
                                ->schema([
                                    Forms\Components\Repeater::make('metadata.equipment')
                                        ->label('Equipment List')
                                        ->schema([
                                            Forms\Components\TextInput::make('brand')
                                                ->label('Brand'),
                                            Forms\Components\TextInput::make('model')
                                                ->label('Model Number'),
                                        ])
                                        ->default($extractedData['equipment'] ?? [])
                                        ->collapsed()
                                        ->itemLabel(fn (array $state): ?string =>
                                            isset($state['brand']) ? "{$state['brand']}" : null
                                        ),
                                ])
                                ->collapsed(empty($extractedData['equipment'] ?? null)),

                            Section::make('Materials')
                                ->schema([
                                    Forms\Components\TagsInput::make('metadata.materials.wood_types')
                                        ->label('Wood Types')
                                        ->default($extractedData['materials']['wood_types'] ?? []),
                                    Forms\Components\TagsInput::make('metadata.materials.finishes')
                                        ->label('Finishes')
                                        ->default($extractedData['materials']['finishes'] ?? []),
                                    Forms\Components\TextInput::make('metadata.materials.hardware')
                                        ->label('Hardware')
                                        ->default($extractedData['materials']['hardware'] ?? null),
                                ])
                                ->collapsed(empty($extractedData['materials'] ?? null)),
                        ];
                    })
                    ->modalHeading('Review & Edit Extracted Data')
                    ->modalDescription('Review the automatically extracted data and make any necessary corrections.')
                    ->modalSubmitActionLabel('Save Metadata')
                    ->modalWidth('5xl')
                    ->action(function (PdfDocument $record, array $data) {
                        $record->update([
                            'extracted_metadata' => $data['metadata'],
                            'metadata_reviewed' => true,
                            'extracted_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Metadata Saved')
                            ->body('Extracted data has been saved and marked as reviewed.')
                            ->success()
                            ->send();
                    }),

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('reprocessFailed')
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

                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
