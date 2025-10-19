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

class AnnotatePdfV2 extends Page implements HasForms
{
    use InteractsWithRecord;
    use InteractsWithForms;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'webkul-project::filament.resources.project-resource.pages.annotate-pdf-v2';

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

    public function mount(int|string $record, int $page = 1): void
    {
        $this->record = $this->resolveRecord($record);
        $this->projectId = $this->record->id;
        $this->pageNumber = $page;

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

        // Get the PDF URL
        $this->pdfUrl = Storage::disk('public')->url($this->pdfDocument->file_path);
    }

    public function getTitle(): string | Htmlable
    {
        return "Annotate Page {$this->pageNumber} (V3 Overlay System)";
    }

    public function getHeading(): string | Htmlable
    {
        return "Annotate Page {$this->pageNumber}";
    }

    public function getSubheading(): string | Htmlable | null
    {
        return $this->record->name ?? null;
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
    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }
}
