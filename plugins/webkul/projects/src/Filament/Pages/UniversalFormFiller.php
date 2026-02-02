<?php

namespace Webkul\Project\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\FormFiller\UniversalFormFillerService;
use Webkul\Sale\Models\DocumentTemplate;

/**
 * Universal Form Filler Page
 *
 * Displays HTML templates with editable fields and AI auto-fill capabilities.
 * Works with BOL, proposals, invoices, and other document templates.
 */
class UniversalFormFiller extends Page
{
    protected static ?int $navigationSort = 50;
    protected static ?string $slug = 'form-filler';

    public static function getNavigationIcon(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationLabel(): string
    {
        return 'Form Filler';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Documents';
    }

    protected string $view = 'webkul-project::filament.pages.universal-form-filler';

    // State properties
    public ?int $selectedTemplateId = null;
    public ?int $selectedProjectId = null;
    public ?int $selectedOrderId = null;
    public string $aiPrompt = '';
    public array $editableFields = [];
    public string $renderedContent = '';
    public bool $isEditing = false;
    public bool $showAiPanel = false;
    public bool $isProcessing = false;

    // Service
    protected ?UniversalFormFillerService $formFillerService = null;

    public function mount(): void
    {
        // Check for query params to pre-select template/project
        $this->selectedTemplateId = request()->query('template_id') ? (int) request()->query('template_id') : null;
        $this->selectedProjectId = request()->query('project_id') ? (int) request()->query('project_id') : null;
        $this->selectedOrderId = request()->query('order_id') ? (int) request()->query('order_id') : null;

        // Restore from session
        if (!$this->selectedTemplateId) {
            $this->selectedTemplateId = session('form_filler.template_id');
        }

        // Load template if selected
        if ($this->selectedTemplateId) {
            $this->loadTemplate();

            // If project is also selected, auto-fill
            if ($this->selectedProjectId) {
                $this->autoFillFromProject();
            }
        }
    }

    public function boot(): void
    {
        $this->formFillerService = app(UniversalFormFillerService::class);
    }

    protected function formFillerService(): UniversalFormFillerService
    {
        return $this->formFillerService ??= app(UniversalFormFillerService::class);
    }

    public function getTemplateOptions(): array
    {
        return DocumentTemplate::query()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($template) => [
                $template->id => "[{$template->type}] {$template->name}"
            ])
            ->toArray();
    }

    public function getProjectOptions(): array
    {
        return Project::query()
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get()
            ->mapWithKeys(fn ($project) => [
                $project->id => $project->name
            ])
            ->toArray();
    }

    public function updatedSelectedTemplateId(): void
    {
        $this->loadTemplate();
    }

    public function updatedSelectedProjectId(): void
    {
        $this->autoFillFromProject();
    }

    public function loadTemplate(): void
    {
        if (!$this->selectedTemplateId) {
            $this->renderedContent = '';
            $this->editableFields = [];
            return;
        }

        $template = DocumentTemplate::find($this->selectedTemplateId);
        if (!$template) {
            Notification::make()
                ->title('Template not found')
                ->danger()
                ->send();
            return;
        }

        $content = $template->getContent();
        if (!$content) {
            $this->renderedContent = '<div class="p-8 text-center text-gray-500">Template has no content</div>';
            return;
        }

        // Parse editable fields from template
        $this->editableFields = $this->formFillerService()->parseEditableFields($content);

        // Render initial content
        $this->renderedContent = $content;

        // Save to session
        session()->put('form_filler.template_id', $this->selectedTemplateId);
    }

    public function autoFillFromProject(): void
    {
        if (!$this->selectedProjectId || !$this->selectedTemplateId) {
            return;
        }

        $project = Project::with(['partner', 'orders'])->find($this->selectedProjectId);
        if (!$project) {
            return;
        }

        // Auto-fill fields from project data
        $this->editableFields = $this->formFillerService()->fillFromProject(
            $this->editableFields,
            $project
        );

        $this->refreshContent();

        Notification::make()
            ->title('Filled from project')
            ->body("Data populated from: {$project->name}")
            ->success()
            ->send();
    }

    public function updateField(string $fieldName, string $value): void
    {
        $this->editableFields[$fieldName] = $value;
        $this->refreshContent();
    }

    public function refreshContent(): void
    {
        if (!$this->selectedTemplateId) {
            return;
        }

        $template = DocumentTemplate::find($this->selectedTemplateId);
        if (!$template) {
            return;
        }

        $content = $template->getContent();
        $this->renderedContent = $this->formFillerService()->applyFields($content, $this->editableFields);
    }

    public function toggleAiPanel(): void
    {
        $this->showAiPanel = !$this->showAiPanel;
    }

    public function runAiFill(): void
    {
        if (empty($this->aiPrompt)) {
            Notification::make()
                ->title('Please enter instructions')
                ->warning()
                ->send();
            return;
        }

        $this->isProcessing = true;

        try {
            // Call AI service to process the prompt
            $result = $this->formFillerService()->processAiPrompt(
                $this->aiPrompt,
                $this->editableFields,
                $this->selectedProjectId ? Project::find($this->selectedProjectId) : null
            );

            if ($result['success']) {
                $this->editableFields = array_merge($this->editableFields, $result['fields']);
                $this->refreshContent();

                Notification::make()
                    ->title('AI Fill Complete')
                    ->body($result['message'] ?? 'Fields updated successfully')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('AI Fill Failed')
                    ->body($result['error'] ?? 'Unknown error')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('AI Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isProcessing = false;
            $this->aiPrompt = '';
        }
    }

    public function exportPdf(): void
    {
        // Generate PDF from current content
        $this->dispatch('generate-pdf', content: $this->renderedContent);

        Notification::make()
            ->title('PDF Generation')
            ->body('Preparing PDF for download...')
            ->info()
            ->send();
    }

    public function exportHtml(): void
    {
        $filename = 'document-' . now()->format('Y-m-d-His') . '.html';

        Storage::disk('local')->put("exports/{$filename}", $this->renderedContent);

        Notification::make()
            ->title('HTML Exported')
            ->body("Saved to storage/app/exports/{$filename}")
            ->success()
            ->send();
    }

    public function printDocument(): void
    {
        $this->dispatch('print-document');
    }

    public function clearFields(): void
    {
        $template = DocumentTemplate::find($this->selectedTemplateId);
        if ($template) {
            $content = $template->getContent();
            $this->editableFields = $this->formFillerService()->parseEditableFields($content);
            $this->renderedContent = $content;
        }

        Notification::make()
            ->title('Fields Cleared')
            ->success()
            ->send();
    }

    #[On('field-updated')]
    public function handleFieldUpdate(string $field, string $value): void
    {
        $this->updateField($field, $value);
    }

    #[Computed]
    public function templateTypes(): array
    {
        return DocumentTemplate::getTypes();
    }

    #[Computed]
    public function currentTemplate(): ?DocumentTemplate
    {
        return $this->selectedTemplateId
            ? DocumentTemplate::find($this->selectedTemplateId)
            : null;
    }

    public static function canAccess(): bool
    {
        return true; // Add proper permission check
    }
}
