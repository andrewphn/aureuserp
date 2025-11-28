<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Inspiration Images Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class InspirationImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'inspirationImages';

    protected static ?string $title = 'Inspiration Gallery';

    protected static ?string $recordTitleAttribute = 'file_name';

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('Image')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1'])
                    ->directory('projects/inspiration')
                    ->visibility('private')
                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])
                    ->maxSize(10240)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('file_name')
                    ->label('File Name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TagsInput::make('tags')
                    ->label('Tags')
                    ->placeholder('Add tags')
                    ->columnSpanFull(),

                Forms\Components\KeyValue::make('metadata')
                    ->label('Additional Metadata')
                    ->keyLabel('Property')
                    ->valueLabel('Value')
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_name')
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Preview')
                    ->disk('private')
                    ->height(100)
                    ->square(),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Filename copied')
                    ->description(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('dimensions')
                    ->label('Dimensions')
                    ->sortable(['width', 'height'])
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('formatted_file_size')
                    ->label('Size')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('uploaded_by')
                    ->label('Uploaded By')
                    ->relationship('uploader', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Get file info
                        $filePath = $data['file_path'];
                        $fullPath = Storage::disk('private')->path($filePath);

                        $data['file_size'] = file_exists($fullPath) ? filesize($fullPath) : 0;
                        $data['mime_type'] = file_exists($fullPath) ? mime_content_type($fullPath) : 'image/jpeg';

                        // Get image dimensions
                        $dimensions = file_exists($fullPath) ? @getimagesize($fullPath) : null;
                        $data['width'] = $dimensions ? $dimensions[0] : null;
                        $data['height'] = $dimensions ? $dimensions[1] : null;

                        // Auto-fill file_name if empty
                        if (empty($data['file_name'])) {
                            $data['file_name'] = basename($filePath);
                        }

                        $data['uploaded_by'] = Auth::id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => $record->file_name)
                    ->modalContent(fn ($record) => view('filament.resources.project-resource.inspiration-image-viewer', [
                        'record' => $record,
                        'imageUrl' => Storage::disk('private')->url($record->file_path),
                    ]))
                    ->modalWidth('7xl')
                    ->slideOver(),

                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        // Only update file metadata if file changed
                        if ($data['file_path'] !== $record->file_path) {
                            $filePath = $data['file_path'];
                            $fullPath = Storage::disk('private')->path($filePath);

                            $data['file_size'] = file_exists($fullPath) ? filesize($fullPath) : 0;
                            $data['mime_type'] = file_exists($fullPath) ? mime_content_type($fullPath) : 'image/jpeg';

                            $dimensions = file_exists($fullPath) ? @getimagesize($fullPath) : null;
                            $data['width'] = $dimensions ? $dimensions[0] : null;
                            $data['height'] = $dimensions ? $dimensions[1] : null;
                        }

                        return $data;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Delete the actual file
                        if (Storage::disk('private')->exists($record->file_path)) {
                            Storage::disk('private')->delete($record->file_path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Delete the actual files
                            foreach ($records as $record) {
                                if (Storage::disk('private')->exists($record->file_path)) {
                                    Storage::disk('private')->delete($record->file_path);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->paginated([12, 24, 48, 'all']);
    }
}
