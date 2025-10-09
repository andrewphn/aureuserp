<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\PdfDocument;
use App\Services\PdfParsingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                FileUpload::make('file_path')
                    ->label('PDF Document')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(51200) // 50MB
                    ->disk('public')
                    ->directory('pdf-documents')
                    ->required()
                    ->live()
                    ->extraAttributes(['wire:ignore' => true])
                    ->afterStateUpdated(function ($state, $set, $livewire) {
                        if ($state) {
                            // Get original filename
                            $filename = is_string($state) ? basename($state) : (is_object($state) ? $state->getClientOriginalName() : null);

                            if ($filename) {
                                // Get project and calculate next revision
                                $project = $livewire->getOwnerRecord();
                                $existingPdfCount = $project->pdfDocuments()->count();
                                $nextRevision = $existingPdfCount + 1;

                                // Clean up original filename
                                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                                $originalName = pathinfo($filename, PATHINFO_FILENAME);
                                $cleanOriginalName = preg_replace('/^[0-9A-Z]{26}_/', '', $originalName);

                                // Generate proper filename with project number and revision
                                $newFilename = sprintf(
                                    '%s-Rev%d-%s.%s',
                                    $project->project_number,
                                    $nextRevision,
                                    $cleanOriginalName ?: 'Drawing',
                                    $extension
                                );

                                $set('file_name', $newFilename);
                            }
                        }
                    }),

                TextInput::make('file_name')
                    ->label('File Name')
                    ->placeholder('Auto-filled with Project#-Rev#-Name.pdf')
                    ->helperText('Will be auto-generated as: ProjectNumber-Rev#-OriginalName.pdf')
                    ->disabled()
                    ->dehydrated(),

                \Filament\Forms\Components\Select::make('document_type')
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

                \Filament\Forms\Components\Select::make('document_tags')
                    ->label('Tags')
                    ->multiple()
                    ->relationship('documentTags', 'name')
                    ->preload()
                    ->createOptionForm([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\Select::make('type')
                            ->options([
                                'status' => 'Status',
                                'category' => 'Category',
                                'priority' => 'Priority',
                                'custom' => 'Custom',
                            ])
                            ->default('custom'),
                        \Filament\Forms\Components\TextInput::make('color')
                            ->label('Color (hex)')
                            ->placeholder('#3B82F6')
                            ->prefix('#')
                            ->maxLength(7),
                        \Filament\Forms\Components\Textarea::make('description')
                            ->rows(2),
                    ])
                    ->helperText('Select or create tags to organize this document'),

                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->helperText('Optional notes about these documents'),

                Hidden::make('file_size'),
                Hidden::make('mime_type'),
                Hidden::make('uploaded_by')
                    ->default(Auth::id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('formatted_file_size')
                    ->label('Size'),

                TextColumn::make('page_count')
                    ->label('Pages')
                    ->default('—'),

                TextColumn::make('documentTags.name')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->searchable(),

                TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record) {
                        // Redirect to Review & Price wizard after upload
                        return redirect()->route('filament.admin.resources.project.projects.pdf-review', [
                            'record' => $this->getOwnerRecord()->id,
                            'pdf' => $record->id,
                        ]);
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $project = $this->getOwnerRecord();
                        $originalPath = $data['file_path'];

                        // If file_name was auto-generated with proper format, rename the actual file
                        if (!empty($data['file_name']) && !empty($originalPath)) {
                            // Build new path with proper filename
                            $directory = dirname($originalPath);
                            $newPath = $directory . '/' . $data['file_name'];

                            // Rename the actual file in storage
                            Storage::disk('public')->move($originalPath, $newPath);

                            // Update path in data
                            $data['file_path'] = $newPath;

                            // Store metadata about original filename
                            $existingPdfCount = $project->pdfDocuments()->count();
                            $data['metadata'] = json_encode([
                                'revision' => $existingPdfCount + 1,
                                'original_filename' => basename($originalPath),
                            ]);
                        }

                        // Get file size if not set
                        if (empty($data['file_size']) && !empty($data['file_path'])) {
                            $data['file_size'] = Storage::disk('public')->size($data['file_path']);
                        }

                        // Set mime type if not set
                        if (empty($data['mime_type'])) {
                            $data['mime_type'] = 'application/pdf';
                        }

                        // Extract page count from PDF using proper parser
                        if (empty($data['page_count']) && !empty($data['file_path'])) {
                            try {
                                $fullPath = Storage::disk('public')->path($data['file_path']);
                                if (file_exists($fullPath)) {
                                    $parser = new \Smalot\PdfParser\Parser();
                                    $pdf = $parser->parseFile($fullPath);
                                    $pages = $pdf->getPages();
                                    $data['page_count'] = count($pages);
                                }
                            } catch (\Exception $e) {
                                \Log::warning('Could not extract page count from PDF: ' . $e->getMessage());
                            }
                        }

                        $data['module_type'] = get_class($project);
                        $data['module_id'] = $project->id;
                        $data['uploaded_by'] = Auth::id();
                        return $data;
                    }),
            ])
            ->actions([
                Action::make('reviewAndPrice')
                    ->label('Review & Price')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('primary')
                    ->visible(fn (PdfDocument $record) => $record->document_type === 'drawing')
                    ->url(fn (PdfDocument $record) => route('filament.admin.resources.project.projects.pdf-review', [
                        'record' => $this->getOwnerRecord()->id,
                        'pdf' => $record->id,
                    ])),
                Action::make('view')
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
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
