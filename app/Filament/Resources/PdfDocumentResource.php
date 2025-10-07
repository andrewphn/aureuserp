<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PdfDocumentResource\Pages;
use App\Models\PdfDocument;
use App\Filament\Forms\Components\PdfViewerField;
use App\Services\PdfDataExtractor;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PdfDocumentResource extends Resource
{
    protected static ?string $model = PdfDocument::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationLabel(): string
    {
        return 'PDF Documents';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Documents';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter document title'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Enter document description'),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('PDF File')
                            ->disk('public')
                            ->directory('pdf-documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(51200) // 50MB in KB
                            ->required()
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->helperText('Maximum file size: 50MB. Accepted format: PDF only.'),

                        Forms\Components\Select::make('folder_id')
                            ->relationship('folder', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2),
                            ])
                            ->nullable(),

                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\ColorPicker::make('color')
                                    ->default('#3b82f6'),
                            ])
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Classification')
                    ->schema([
                        Forms\Components\Select::make('documentable_type')
                            ->label('Related To')
                            ->options([
                                'App\\Models\\Project' => 'Project',
                                'App\\Models\\Partner' => 'Partner',
                                'App\\Models\\Quote' => 'Quote',
                                'App\\Models\\WorkOrder' => 'Work Order',
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('documentable_id', null))
                            ->nullable(),

                        Forms\Components\Select::make('documentable_id')
                            ->label('Select Record')
                            ->options(function (callable $get) {
                                $type = $get('documentable_type');

                                if (!$type) {
                                    return [];
                                }

                                $model = app($type);

                                return $model->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => filled($get('documentable_type')))
                            ->nullable(),

                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('Add tags')
                            ->helperText('Press Enter after each tag'),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Document')
                            ->helperText('Allow all users to view this document')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('PDF Viewer')
                    ->schema([
                        PdfViewerField::make('id')
                            ->label('Document Preview')
                            ->documentId(fn ($record) => $record?->id)
                            ->fullEditor()
                            ->height('700px')
                            ->visible(fn ($record) => $record !== null && $record->file_path !== null),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('folder.name')
                    ->label('Folder')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? 'primary'),

                Tables\Columns\TextColumn::make('documentable_type')
                    ->label('Related To')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('success')
                    ->visible(fn () => Auth::user()->hasRole('admin')),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('processing_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\IconColumn::make('metadata_reviewed')
                    ->label('Reviewed')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('folder_id')
                    ->label('Folder')
                    ->relationship('folder', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('documentable_type')
                    ->label('Related To')
                    ->options([
                        'App\\Models\\Project' => 'Project',
                        'App\\Models\\Partner' => 'Partner',
                        'App\\Models\\Quote' => 'Quote',
                        'App\\Models\\WorkOrder' => 'Work Order',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public Documents')
                    ->placeholder('All documents')
                    ->trueLabel('Public only')
                    ->falseLabel('Private only'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Uploaded from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Uploaded until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                Tables\Actions\Action::make('review')
                    ->label('Review Data')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->url(fn (PdfDocument $record) => static::getUrl('review', ['record' => $record]))
                    ->visible(fn (PdfDocument $record) =>
                        $record->processing_status === 'completed' &&
                        !$record->metadata_reviewed &&
                        !empty($record->extracted_metadata)
                    ),
                Tables\Actions\Action::make('reextract')
                    ->label('Re-extract Metadata')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Re-extract Metadata')
                    ->modalDescription('This will re-run the extraction process using the latest extraction logic. Any previous metadata will be overwritten.')
                    ->modalSubmitActionLabel('Re-extract')
                    ->visible(fn (PdfDocument $record) =>
                        !empty($record->file_path) &&
                        $record->pages()->count() > 0
                    )
                    ->action(function (PdfDocument $record) {
                        try {
                            $extractor = app(PdfDataExtractor::class);

                            // Re-extract metadata
                            $metadata = $extractor->extractMetadata($record);

                            // Re-extract room data
                            $roomData = $extractor->extractRoomData($record);

                            // Update the record
                            $record->update([
                                'extracted_metadata' => array_merge($metadata, ['rooms' => $roomData]),
                                'processing_status' => 'completed',
                                'metadata_reviewed' => false, // Reset so they can review new data
                                'extracted_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Metadata Re-extracted Successfully')
                                ->body('The document has been re-processed with the latest extraction logic.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Re-extraction Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();

                            \Log::error('PDF re-extraction failed', [
                                'pdf_id' => $record->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }),
                \Filament\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (PdfDocument $record) => route('pdf.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('reextractBulk')
                        ->label('Re-extract Metadata')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Re-extract Metadata for Selected Documents')
                        ->modalDescription('This will re-run the extraction process for all selected documents using the latest extraction logic.')
                        ->modalSubmitActionLabel('Re-extract All')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $extractor = app(PdfDataExtractor::class);
                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($records as $record) {
                                try {
                                    // Re-extract metadata
                                    $metadata = $extractor->extractMetadata($record);

                                    // Re-extract room data
                                    $roomData = $extractor->extractRoomData($record);

                                    // Update the record
                                    $record->update([
                                        'extracted_metadata' => array_merge($metadata, ['rooms' => $roomData]),
                                        'processing_status' => 'completed',
                                        'metadata_reviewed' => false,
                                        'extracted_at' => now(),
                                    ]);

                                    $successCount++;

                                } catch (\Exception $e) {
                                    $errorCount++;
                                    \Log::error('PDF re-extraction failed', [
                                        'pdf_id' => $record->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Bulk Re-extraction Complete')
                                    ->body("Successfully re-extracted {$successCount} document(s)." . ($errorCount > 0 ? " {$errorCount} failed." : ''))
                                    ->success()
                                    ->send();
                            }

                            if ($errorCount > 0 && $successCount === 0) {
                                Notification::make()
                                    ->title('Bulk Re-extraction Failed')
                                    ->body("Failed to re-extract {$errorCount} document(s).")
                                    ->danger()
                                    ->send();
                            }
                        }),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPdfDocuments::route('/'),
            'create' => Pages\CreatePdfDocument::route('/create'),
            'edit' => Pages\EditPdfDocument::route('/{record}/edit'),
            'view' => Pages\ViewPdfDocument::route('/{record}'),
            'review' => Pages\ReviewExtractedData::route('/{record}/review'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        // Admin can see all documents
        if ($user->hasRole('admin')) {
            return $query;
        }

        // Regular users can see:
        // 1. Documents they uploaded
        // 2. Public documents
        // 3. Documents attached to records they have access to
        return $query->where(function (Builder $query) use ($user) {
            $query->where('uploaded_by', $user->id)
                  ->orWhere('is_public', true);
            // Add polymorphic relationship checks here based on your access control
        });
    }
}
