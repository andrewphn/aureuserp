<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Project Media Relation Manager
 *
 * Manages all project assets including:
 * - Inspiration images (Pinterest, reference photos)
 * - CAD drawings and DWG files
 * - Documents (PDFs, contracts, permits)
 * - Site/progress photos
 * - Videos
 *
 * Uses Spatie Media Library with Filament Plugin
 */
class ProjectMediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Project Assets';

    protected static \BackedEnum|string|null $icon = 'heroicon-o-photo';

    /**
     * Define the form schema for uploading media
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('collection_name')
                        ->label('Asset Type')
                        ->options([
                            'inspiration' => 'Inspiration Images',
                            'drawings' => 'CAD Drawings / DWG',
                            'documents' => 'Documents (PDF, Word)',
                            'photos' => 'Site / Progress Photos',
                            'videos' => 'Videos',
                        ])
                        ->required()
                        ->native(false)
                        ->live()
                        ->columnSpan(1),

                    TextInput::make('name')
                        ->label('Display Name')
                        ->placeholder('Optional custom name')
                        ->columnSpan(1),
                ]),

                FileUpload::make('file')
                    ->label('Upload File(s)')
                    ->multiple()
                    ->maxFiles(10)
                    ->maxSize(102400) // 100MB
                    ->acceptedFileTypes(fn (callable $get) => match ($get('collection_name')) {
                        'inspiration' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'drawings' => ['application/pdf', 'image/jpeg', 'image/png', 'application/octet-stream', 'application/acad', 'image/vnd.dwg'],
                        'documents' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                        'photos' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic'],
                        'videos' => ['video/mp4', 'video/quicktime', 'video/webm'],
                        default => ['image/jpeg', 'image/png', 'application/pdf'],
                    })
                    ->preserveFilenames()
                    ->getUploadedFileNameForStorageUsing(
                        fn (TemporaryUploadedFile $file): string => $file->getClientOriginalName()
                    )
                    ->directory('project-media')
                    ->visibility('public')
                    ->columnSpanFull(),

                Textarea::make('custom_properties.notes')
                    ->label('Notes')
                    ->placeholder('Optional notes about this file')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Define the table schema for viewing media
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('preview')
                    ->label('')
                    ->circular(false)
                    ->width(60)
                    ->height(60)
                    ->defaultImageUrl(fn ($record) => $this->getMediaIcon($record))
                    ->getStateUsing(fn ($record) => $this->getPreviewUrl($record)),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('collection_name')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'inspiration' => 'pink',
                        'drawings' => 'blue',
                        'documents' => 'amber',
                        'photos' => 'green',
                        'videos' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'inspiration' => 'Inspiration',
                        'drawings' => 'Drawings',
                        'documents' => 'Documents',
                        'photos' => 'Photos',
                        'videos' => 'Videos',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('File')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Format')
                    ->formatStateUsing(fn ($state) => strtoupper(explode('/', $state)[1] ?? $state))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $this->formatFileSize($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collection_name')
                    ->label('Asset Type')
                    ->options([
                        'inspiration' => 'Inspiration Images',
                        'drawings' => 'CAD Drawings',
                        'documents' => 'Documents',
                        'photos' => 'Photos',
                        'videos' => 'Videos',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Upload Assets')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('Upload Project Assets')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Handle file uploads through Spatie Media Library
                        return $data;
                    })
                    ->using(function (array $data, RelationManager $livewire) {
                        $project = $livewire->getOwnerRecord();
                        $collection = $data['collection_name'] ?? 'documents';

                        // Handle multiple file uploads
                        $files = $data['file'] ?? [];
                        if (!is_array($files)) {
                            $files = [$files];
                        }

                        $lastMedia = null;
                        foreach ($files as $file) {
                            $media = $project
                                ->addMedia(storage_path('app/public/' . $file))
                                ->usingName($data['name'] ?? pathinfo($file, PATHINFO_FILENAME))
                                ->withCustomProperties([
                                    'notes' => $data['custom_properties']['notes'] ?? null,
                                ])
                                ->toMediaCollection($collection);
                            $lastMedia = $media;
                        }

                        return $lastMedia;
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => $record->getUrl())
                    ->openUrlInNewTab(),

                \Filament\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->getUrl())
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => str_starts_with($record->mime_type, 'image/') || $record->mime_type === 'application/pdf'),

                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Get preview URL for media item
     */
    protected function getPreviewUrl($record): ?string
    {
        if (str_starts_with($record->mime_type, 'image/')) {
            return $record->getUrl('thumb') ?? $record->getUrl();
        }

        return null;
    }

    /**
     * Get icon for non-image media types
     */
    protected function getMediaIcon($record): string
    {
        return match (true) {
            str_starts_with($record->mime_type, 'video/') => asset('images/icons/video-icon.svg'),
            $record->mime_type === 'application/pdf' => asset('images/icons/pdf-icon.svg'),
            str_contains($record->mime_type, 'dwg') || str_contains($record->file_name, '.dwg') => asset('images/icons/dwg-icon.svg'),
            str_contains($record->mime_type, 'word') || str_contains($record->mime_type, 'document') => asset('images/icons/doc-icon.svg'),
            str_contains($record->mime_type, 'excel') || str_contains($record->mime_type, 'spreadsheet') => asset('images/icons/xls-icon.svg'),
            default => asset('images/icons/file-icon.svg'),
        };
    }

    /**
     * Format file size for display
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 1) . ' ' . $units[$index];
    }
}
