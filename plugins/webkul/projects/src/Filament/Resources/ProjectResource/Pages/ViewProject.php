<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Filament\Resources\ProjectResource\Actions\CloneProjectAction;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;
use Webkul\Project\Services\MaterialBomService;
use Webkul\Project\Services\ProjectReportService;
use Webkul\Support\Models\ActivityPlan;

/**
 * View Project class
 *
 * @see \Filament\Resources\Resource
 */
class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * Mount
     *
     * @return void
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Set active context to notify the footer which project is being viewed
        // NO form data is passed to prevent EntityStore sync
        $this->dispatch('set-active-context', [
            'entityType' => 'project',
            'entityId' => $this->record->id,
        ]);

        // Auto-sync with Google Drive if enabled and needs sync
        $this->autoSyncGoogleDrive();
    }

    /**
     * Auto-sync with Google Drive when viewing project
     */
    protected function autoSyncGoogleDrive(): void
    {
        // Only sync if project has Google Drive enabled and configured
        if (!$this->record->google_drive_enabled || !$this->record->google_drive_root_folder_id) {
            return;
        }

        try {
            $driveService = app(GoogleDriveService::class);

            // Only sync if it's been more than 5 minutes since last sync
            if ($driveService->projectNeedsSync($this->record, 5)) {
                $result = $driveService->syncProject($this->record);

                // Show notification if changes were found
                if ($result['success'] && !empty($result['changes'])) {
                    $added = count($result['changes']['added'] ?? []);
                    $deleted = count($result['changes']['deleted'] ?? []);
                    $modified = count($result['changes']['modified'] ?? []);

                    if ($added > 0 || $deleted > 0 || $modified > 0) {
                        $changes = [];
                        if ($added > 0) $changes[] = "{$added} added";
                        if ($deleted > 0) $changes[] = "{$deleted} deleted";
                        if ($modified > 0) $changes[] = "{$modified} modified";

                        Notification::make()
                            ->info()
                            ->title('Google Drive Synced')
                            ->body('Changes detected: ' . implode(', ', $changes))
                            ->send();
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Auto-sync failed for project', [
                'project_id' => $this->record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editInWizard')
                ->label('Edit in Wizard')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn () => ProjectResource::getUrl('wizard-edit', ['record' => $this->record->id]))
                ->tooltip('Edit this project using the step-by-step wizard'),
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

                    Checkbox::make('is_primary_reference')
                        ->label('Set as Primary Reference')
                        ->helperText('Mark this document as the primary reference for the project (will be displayed in project overview)'),
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

                    // If this document is being set as primary reference, unmark any existing primary references
                    if (!empty($data['is_primary_reference'])) {
                        $this->record->pdfDocuments()
                            ->where('is_primary_reference', true)
                            ->update(['is_primary_reference' => false]);
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
                        'is_primary_reference' => $data['is_primary_reference'] ?? false,
                        'uploaded_by' => Auth::id(),
                    ]);

                    // Create PdfPage records for each page
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
                        \Log::info("Created {$pageCount} PdfPage records for document {$pdfDocument->id}");
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
                        ->title(__('webkul-project::filament/resources/project/pages/view-project.header-actions.delete.notification.title'))
                        ->body(__('webkul-project::filament/resources/project/pages/view-project.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProjectResource\Widgets\ProjectStageWidget::class,
            ProjectResource\Widgets\ProjectFinancialsWidget::class,
            ProjectResource\Widgets\ProjectTimelineWidget::class,
            ProjectResource\Widgets\ProjectCncStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 2,
            'lg' => 4,
        ];
    }

    /**
     * Mutate Form Data Before Fill
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load relationships for hierarchical display
        $this->record->load([
            'rooms.locations.cabinetRuns.cabinets',
            'pdfDocuments.pages',
            'tags'
        ]);

        return $data;
    }

    /**
     * Define the infolist schema
     *
     * Compact Header Bar Layout:
     * - Full-width project info bar with inline project details and actions
     * - Primary Reference in collapsible section below
     * - Project Data Cards full width at bottom
     *
     * @param Schema $schema
     * @return Schema
     */
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Compact Project Info Bar (Full Width)
                Section::make('')
                    ->schema([
                        // Row 1: Project info inline (name, status, customer, tags)
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('')
                                    ->icon('heroicon-o-folder')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('status')
                                    ->label('')
                                    ->badge()
                                    ->color(fn ($state): string => match ($state) {
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'on_hold' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('partner.name')
                                    ->label('')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('No customer'),
                                TextEntry::make('tags.name')
                                    ->label('')
                                    ->badge()
                                    ->state(function ($record): array {
                                        return $record->tags->map(fn ($tag) => [
                                            'label' => $tag->name,
                                            'color' => $tag->color ?? '#808080',
                                        ])->toArray();
                                    })
                                    ->formatStateUsing(fn ($state) => $state['label'] ?? '')
                                    ->color(fn ($state) => \Filament\Support\Colors\Color::generateV3Palette($state['color'] ?? '#808080'))
                                    ->separator(' ')
                                    ->placeholder('No tags'),
                            ]),

                        // Row 2: All actions in a single horizontal row
                        Actions::make([
                            // Primary export actions
                            Action::make('exportBom')
                                ->label('BOM')
                                ->icon('heroicon-o-document-arrow-down')
                                ->color('primary')
                                ->size('sm')
                                ->action(fn () => $this->exportBom()),
                            Action::make('generateSummary')
                                ->label('Summary')
                                ->icon('heroicon-o-document-text')
                                ->color('success')
                                ->size('sm')
                                ->form([
                                    Section::make('Report Sections')
                                        ->description('Select which sections to include in your report')
                                        ->schema([
                                            \Filament\Forms\Components\CheckboxList::make('sections')
                                                ->label('')
                                                ->options([
                                                    'project_info' => 'Project Information (name, customer, status, tags)',
                                                    'project_totals' => 'Project Totals (rooms, cabinets, LF, value)',
                                                    'room_breakdown' => 'Room Breakdown (summary by room)',
                                                    'cabinet_detail' => 'Cabinet Detail (all cabinets with specs)',
                                                    'hardware_summary' => 'Hardware Summary (hinges, slides, etc.)',
                                                    'materials_bom' => 'Materials BOM (wood, sheet goods)',
                                                    'production_status' => 'Production Status (stage gates, timestamps)',
                                                    'tasks_milestones' => 'Tasks & Milestones',
                                                    'addresses' => 'Project Addresses',
                                                    'pdf_documents' => 'PDF Documents List',
                                                ])
                                                ->default(['project_info', 'project_totals', 'room_breakdown', 'cabinet_detail'])
                                                ->columns(2)
                                                ->bulkToggleable(),
                                        ]),
                                    Section::make('Options')
                                        ->schema([
                                            \Filament\Forms\Components\Toggle::make('include_pricing')
                                                ->label('Include Pricing Details')
                                                ->default(true)
                                                ->helperText('Show unit prices and totals'),
                                            \Filament\Forms\Components\Toggle::make('include_dimensions')
                                                ->label('Include Dimensions')
                                                ->default(true)
                                                ->helperText('Show W x D x H measurements'),
                                        ])
                                        ->columns(2),
                                ])
                                ->action(fn (array $data) => $this->generateSummary($data)),
                            Action::make('purchaseRequisition')
                                ->label('Purchase')
                                ->icon('heroicon-o-shopping-cart')
                                ->color('warning')
                                ->size('sm')
                                ->action(fn () => $this->generatePurchaseRequisition()),
                            // Google Drive actions
                            Action::make('syncGoogleDrive')
                                ->label('Sync')
                                ->icon('heroicon-o-arrow-path')
                                ->color('gray')
                                ->size('sm')
                                ->visible(fn () => $this->record->google_drive_enabled && $this->record->google_drive_root_folder_id)
                                ->action(function () {
                                    $driveService = app(GoogleDriveService::class);
                                    $result = $driveService->syncProject($this->record);

                                    if ($result['success']) {
                                        $added = count($result['changes']['added'] ?? []);
                                        $deleted = count($result['changes']['deleted'] ?? []);
                                        $modified = count($result['changes']['modified'] ?? []);

                                        if ($added > 0 || $deleted > 0 || $modified > 0) {
                                            $changes = [];
                                            if ($added > 0) $changes[] = "{$added} added";
                                            if ($deleted > 0) $changes[] = "{$deleted} deleted";
                                            if ($modified > 0) $changes[] = "{$modified} modified";

                                            Notification::make()
                                                ->success()
                                                ->title('Google Drive Synced')
                                                ->body('Changes: ' . implode(', ', $changes) . '. Check chatter for details.')
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->success()
                                                ->title('Google Drive Synced')
                                                ->body('No changes detected. ' . $result['total_files'] . ' files in Drive.')
                                                ->send();
                                        }
                                    } else {
                                        Notification::make()
                                            ->danger()
                                            ->title('Sync Failed')
                                            ->body($result['message'] ?? 'Unknown error')
                                            ->send();
                                    }
                                }),
                            Action::make('openGoogleDrive')
                                ->label('Drive')
                                ->icon('heroicon-o-folder-open')
                                ->color('info')
                                ->size('sm')
                                ->visible(fn () => $this->record->google_drive_folder_url)
                                ->url(fn () => $this->record->google_drive_folder_url)
                                ->openUrlInNewTab(),
                        ]),
                    ])
                    ->columnSpanFull(),

                // Primary Reference Gallery (collapsible, full width)
                Section::make('Primary Reference')
                    ->schema([
                        ViewEntry::make('primary_reference_gallery')
                            ->label('')
                            ->view('filament.infolists.components.primary-reference-gallery')
                            ->state(fn ($record) => $record->pdfDocuments()->where('is_primary_reference', true)->first()),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->visible(fn ($record) => $record->pdfDocuments()->where('is_primary_reference', true)->exists())
                    ->columnSpanFull(),

                // Project Breakdown Cards (full width)
                Section::make('')
                    ->schema([
                        ViewEntry::make('project_data_cards')
                            ->label('')
                            ->view('webkul-project::filament.infolists.components.project-data-cards-wrapper'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function getActivityPlans(): mixed
    {
        return ActivityPlan::where('plugin', 'projects')->pluck('name', 'id');
    }

    /**
     * Export Bill of Materials (BOM) for the project as HTML
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function exportBom(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $record = $this->record;
        $reportService = app(ProjectReportService::class);

        $html = $reportService->generateBomHtml($record);

        // Sanitize project name for filename
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $record->name);
        $filename = "bom-{$safeName}-" . now()->format('Y-m-d') . '.html';

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $filename, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    /**
     * Generate and display a configurable project summary as HTML
     *
     * @param array $config Configuration options from the form
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function generateSummary(array $config = []): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $record = $this->record;
        $reportService = app(ProjectReportService::class);

        $html = $reportService->generateSummaryHtml($record, $config);

        // Sanitize project name for filename
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $record->name);
        $filename = "summary-{$safeName}-" . now()->format('Y-m-d') . '.html';

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $filename, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    /**
     * Generate a Purchase Requisition for the project as HTML
     * This report shows what needs to be ordered from suppliers
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function generatePurchaseRequisition(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $record = $this->record;
        $reportService = app(ProjectReportService::class);

        $html = $reportService->generatePurchaseRequisitionHtml($record);

        // Sanitize project name for filename
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $record->name);
        $filename = "purchase-requisition-{$safeName}-" . now()->format('Y-m-d') . '.html';

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $filename, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }
}
