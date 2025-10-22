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
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;

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
                    ->sortable()
                    ->description(fn (PdfDocument $record) => $record->version_number > 1
                        ? "Version {$record->version_number}"
                        : null),

                TextColumn::make('version_number')
                    ->label('Version')
                    ->badge()
                    ->color(fn (PdfDocument $record) => $record->is_latest_version ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state, PdfDocument $record) => $record->is_latest_version
                        ? "v{$state} (Latest)"
                        : "v{$state}"),

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
                        // Create PdfPage records for each page in the PDF
                        $this->createPdfPages($record);

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

                Action::make('uploadNewVersion')
                    ->label('Upload New Version')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->visible(fn (PdfDocument $record) => $record->is_latest_version)
                    ->form([
                        FileUpload::make('new_version_file')
                            ->label('New PDF Version')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required()
                            ->disk('public')
                            ->directory('pdf-documents')
                            ->helperText('Upload a new version of this PDF. Annotations will be migrated.'),

                        Textarea::make('version_notes')
                            ->label('Version Notes')
                            ->rows(3)
                            ->placeholder('What changed in this version?')
                            ->helperText('Describe changes made in this version'),

                        Toggle::make('migrate_annotations')
                            ->label('Migrate Annotations')
                            ->default(true)
                            ->helperText('Automatically migrate annotations from previous version'),
                    ])
                    ->action(function (PdfDocument $record, array $data) {
                        // Mark current version as no longer latest
                        $record->update(['is_latest_version' => false]);

                        // Create new version
                        $newVersion = $record->replicate();
                        $newVersion->version_number = $record->version_number + 1;
                        $newVersion->previous_version_id = $record->id;
                        $newVersion->is_latest_version = true;
                        $newVersion->file_path = $data['new_version_file'];
                        $newVersion->uploaded_by = Auth::id();

                        // Extract page count from new PDF
                        try {
                            $fullPath = Storage::disk('public')->path($data['new_version_file']);
                            if (file_exists($fullPath)) {
                                $parser = new \Smalot\PdfParser\Parser();
                                $pdf = $parser->parseFile($fullPath);
                                $pages = $pdf->getPages();
                                $newVersion->page_count = count($pages);
                                $newVersion->file_size = Storage::disk('public')->size($data['new_version_file']);
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Could not extract page count from new version: ' . $e->getMessage());
                        }

                        // Store version metadata
                        $newVersion->version_metadata = [
                            'version_notes' => $data['version_notes'] ?? null,
                            'migrate_annotations' => $data['migrate_annotations'] ?? false,
                            'migration_date' => now()->toIso8601String(),
                            'migrated_by' => Auth::id(),
                        ];

                        $newVersion->save();

                        // Migrate annotations if requested
                        if ($data['migrate_annotations'] ?? false) {
                            $this->migrateAnnotations($record, $newVersion);
                        }

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('New Version Created')
                            ->body("Version {$newVersion->version_number} has been created successfully.")
                            ->send();

                        return redirect()->route('filament.admin.resources.project.projects.pdf-review', [
                            'record' => $this->getOwnerRecord()->id,
                            'pdf' => $newVersion->id,
                        ]);
                    }),

                Action::make('viewVersionHistory')
                    ->label('Version History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->visible(fn (PdfDocument $record) => $record->version_number > 1 || !$record->is_latest_version)
                    ->modalHeading(fn (PdfDocument $record) => 'Version History: ' . $record->file_name)
                    ->modalContent(fn (PdfDocument $record) => view('filament.modals.pdf-version-history', [
                        'versions' => $record->getAllVersions(),
                        'currentVersion' => $record,
                    ]))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

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

    /**
     * Create PdfPage records for each page in the uploaded PDF
     */
    protected function createPdfPages(PdfDocument $pdfDocument): void
    {
        try {
            $fullPath = Storage::disk('public')->path($pdfDocument->file_path);

            if (!file_exists($fullPath)) {
                \Log::warning("PDF file not found at: {$fullPath}");
                return;
            }

            // Parse PDF to get actual page count
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($fullPath);
            $pages = $pdf->getPages();
            $actualPageCount = count($pages);

            // Update page_count if it's incorrect
            if ($pdfDocument->page_count !== $actualPageCount) {
                $pdfDocument->update(['page_count' => $actualPageCount]);
            }

            // Create PdfPage record for each page
            for ($pageNumber = 1; $pageNumber <= $actualPageCount; $pageNumber++) {
                PdfPage::create([
                    'document_id' => $pdfDocument->id,
                    'page_number' => $pageNumber,
                    'page_type' => null, // Will be set later during review
                ]);
            }

            \Log::info("Created {$actualPageCount} PdfPage records for document {$pdfDocument->id}");

        } catch (\Exception $e) {
            \Log::error("Error creating PdfPage records: " . $e->getMessage());

            Notification::make()
                ->title('Warning: Page Processing Issue')
                ->body('The PDF was uploaded successfully, but there was an issue processing individual pages. Please contact support if you encounter problems.')
                ->warning()
                ->send();
        }
    }

    /**
     * Migrate annotations from old version to new version
     */
    protected function migrateAnnotations(PdfDocument $oldVersion, PdfDocument $newVersion): void
    {
        // Get all pages from old version
        $oldPages = $oldVersion->pages()->with('annotations')->get();

        foreach ($oldPages as $oldPage) {
            // Create corresponding page in new version
            $newPage = PdfPage::create([
                'pdf_document_id' => $newVersion->id,
                'page_number' => $oldPage->page_number,
            ]);

            // Copy annotations to new page
            foreach ($oldPage->annotations as $oldAnnotation) {
                $newAnnotation = $oldAnnotation->replicate();
                $newAnnotation->pdf_page_id = $newPage->id;
                $newAnnotation->save();

                // Log migration in metadata
                \Log::info("Migrated annotation {$oldAnnotation->id} from page {$oldPage->page_number} (v{$oldVersion->version_number}) to new page {$newPage->id} (v{$newVersion->version_number})");
            }
        }
    }
}
