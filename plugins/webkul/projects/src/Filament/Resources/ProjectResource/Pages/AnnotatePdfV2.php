<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Webkul\Project\Filament\Resources\ProjectResource;
use App\Models\PdfDocument;
use App\Models\PdfPage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

/**
 * Annotate Pdf V2 class
 *
 * @see \Filament\Resources\Resource
 */
class AnnotatePdfV2 extends Page implements HasForms
{
    use InteractsWithRecord;
    use InteractsWithForms;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'webkul-project::filament.pages.annotate-pdf-v2';

    protected static bool $shouldRegisterNavigation = false;

    public ?PdfPage $pdfPage = null;
    public string $pdfUrl = '';
    public int $pageNumber = 1;
    public int $projectId;
    public $pdfDocument;
    public int $totalPages = 1;
    public ?string $pageType = null;
    public array $pageMap = [];

    #[Url]
    public $pdf;

    /**
     * Mount
     *
     * @param int|string $record The model record
     * @param ?int $page Page number
     * @return void
     */
    public function mount(int|string $record, ?int $page = null): void
    {
        $this->record = $this->resolveRecord($record);
        $this->projectId = $this->record->id;

        // Get page number from route parameter
        // If not set, default to 1
        $this->pageNumber = $page ?? 1;
        if ($this->pageNumber < 1) {
            $this->pageNumber = 1;
        }

        // Check if PDF ID is provided via URL parameter
        if (!$this->pdf) {
            Notification::make()
                ->title('PDF Not Specified')
                ->body('Please select a PDF document to annotate.')
                ->danger()
                ->send();
            $this->redirect(ProjectResource::getUrl('view', ['record' => $this->record]));
            return;
        }

        // Load the PDF document
        $this->pdfDocument = PdfDocument::findOrFail($this->pdf);

        // Check if PDF file actually exists
        if (!Storage::disk('public')->exists($this->pdfDocument->file_path)) {
            Notification::make()
                ->title('PDF File Not Found')
                ->body('The PDF file "' . $this->pdfDocument->file_name . '" is missing from storage.')
                ->danger()
                ->send();
            $this->redirect(ProjectResource::getUrl('view', ['record' => $this->record]));
            return;
        }

        // Get the PDF page record if it exists
        $this->pdfPage = PdfPage::where('document_id', $this->pdfDocument->id)
            ->where('page_number', $this->pageNumber)
            ->first();

        // Get total pages count
        $this->totalPages = PdfPage::where('document_id', $this->pdfDocument->id)->count();

        // Get page type if page exists
        $this->pageType = $this->pdfPage?->page_type;

        // Build a map of page_number => pdfPageId for all pages
        $this->pageMap = PdfPage::where('document_id', $this->pdfDocument->id)
            ->orderBy('page_number')
            ->pluck('id', 'page_number')
            ->toArray();

        // Get the PDF URL - force HTTPS if the request is secure to prevent mixed content errors
        $this->pdfUrl = Storage::disk('public')->url($this->pdfDocument->file_path);

        // Force HTTPS protocol if the page is accessed via HTTPS (prevents mixed content blocking)
        if (request()->secure() && str_starts_with($this->pdfUrl, 'http://')) {
            $this->pdfUrl = str_replace('http://', 'https://', $this->pdfUrl);
        }
    }

    public function getTitle(): string | Htmlable
    {
        return "Annotate Page {$this->pageNumber} (V3 Overlay System)";
    }

    public function getHeading(): string | Htmlable
    {
        return ''; // Hide the large heading, breadcrumb only
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null; // Hide the subheading
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Project')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => ProjectResource::getUrl('pdf-review', ['record' => $this->record->id])),
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'loadAnnotationScripts' => true,
        ]);
    }

    /**
     * Define a minimal form schema to trigger Filament asset loading
     */
    /**
     * Define the form schema
     *
     * @param Schema $form
     * @return Schema
     */
    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }
}
