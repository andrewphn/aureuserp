<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\CabinetConfiguratorService;
use App\Services\StretcherCalculator;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetSection;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

/**
 * Cabinet Configurator - Visual Builder
 *
 * Provides a visual interface for configuring cabinet structure:
 * - Face frame vs frameless construction
 * - Section layout within cabinet
 * - Template application for common configurations
 * - Bi-directional face frame ↔ opening calculations
 * - Stretcher generation
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 */
class CabinetConfigurator extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    // Cabinet being configured
    public ?int $cabinetId = null;
    public ?Cabinet $cabinet = null;

    // Cabinet dimensions
    public float $cabinetWidth = 36;
    public float $cabinetHeight = 30;
    public float $cabinetDepth = 24;

    // Construction type
    public string $constructionType = 'face_frame';

    // Section layout
    public string $sectionLayoutType = 'horizontal';

    // Face frame settings
    public float $stileWidth = 1.5;
    public float $railWidth = 1.5;
    public int $midStileCount = 0;

    // Calculated dimensions
    public array $frameDimensions = [];
    public array $openingSizes = [];

    // Sections list
    public array $sections = [];

    // Validation
    public bool $isValid = true;
    public array $validationErrors = [];
    public array $validationWarnings = [];

    // Add section modal
    public bool $showAddSectionModal = false;
    public string $addSectionType = 'door';
    public float $addSectionRatio = 0.5;

    // Edit section modal
    public bool $showEditSectionModal = false;
    public ?int $editSectionId = null;
    public string $editSectionType = 'door';
    public string $editSectionName = '';
    public float $editSectionWidth = 0;
    public float $editSectionHeight = 0;
    public float $editSectionRatio = 0.5;

    // Template modal
    public bool $showTemplateModal = false;
    public string $selectedTemplate = '';

    // Stretcher info
    public int $requiredStretcherCount = 0;
    public int $existingStretcherCount = 0;
    public bool $needsStretchers = false;

    // Services
    protected CabinetConfiguratorService $configurator;
    protected StretcherCalculator $stretcherCalculator;

    public function boot(
        CabinetConfiguratorService $configurator,
        StretcherCalculator $stretcherCalculator
    ): void {
        $this->configurator = $configurator;
        $this->stretcherCalculator = $stretcherCalculator;
    }

    public function mount(?int $cabinetId = null): void
    {
        if ($cabinetId) {
            $this->loadCabinet($cabinetId);
        }
    }

    /**
     * Load a cabinet for configuration
     */
    #[On('load-cabinet')]
    public function loadCabinet(int $cabinetId): void
    {
        $this->cabinetId = $cabinetId;
        $this->cabinet = Cabinet::with(['sections.drawers', 'sections.doors', 'sections.shelves', 'sections.pullouts', 'sections.falseFronts', 'stretchers'])
            ->findOrFail($cabinetId);

        // Load cabinet dimensions
        $this->cabinetWidth = $this->cabinet->length_inches ?? 36;
        $this->cabinetHeight = $this->cabinet->height_inches ?? 30;
        $this->cabinetDepth = $this->cabinet->depth_inches ?? 24;

        // Load construction settings
        $this->constructionType = $this->cabinet->construction_type ?? 'face_frame';
        $this->sectionLayoutType = $this->cabinet->section_layout_type ?? 'horizontal';

        // Load face frame settings
        $this->stileWidth = $this->cabinet->face_frame_stile_width_inches ?? CabinetConfiguratorService::DEFAULT_STILE_WIDTH_INCHES;
        $this->railWidth = $this->cabinet->face_frame_rail_width_inches ?? CabinetConfiguratorService::DEFAULT_RAIL_WIDTH_INCHES;
        $this->midStileCount = $this->cabinet->face_frame_mid_stile_count ?? 0;

        // Calculate frame dimensions
        $this->recalculateFrame();

        // Load sections
        $this->refreshSections();

        // Load stretcher info
        $this->refreshStretcherInfo();

        // Validate
        $this->validateLayout();
    }

    /**
     * Recalculate face frame dimensions
     */
    public function recalculateFrame(): void
    {
        if (!$this->cabinet) {
            return;
        }

        // Update cabinet with current values
        $this->cabinet->face_frame_stile_width_inches = $this->stileWidth;
        $this->cabinet->face_frame_rail_width_inches = $this->railWidth;
        $this->cabinet->face_frame_mid_stile_count = $this->midStileCount;

        $this->frameDimensions = $this->configurator->calculateFaceFrameDimensions($this->cabinet);

        // Calculate opening sizes based on current sections
        $sectionRatios = collect($this->sections)->pluck('ratio')->toArray();
        if (!empty($sectionRatios)) {
            $this->openingSizes = $this->configurator->calculateOpeningSizes($this->cabinet, $sectionRatios);
        } else {
            $this->openingSizes = $this->configurator->calculateOpeningSizes($this->cabinet, [1.0]);
        }
    }

    /**
     * Update face frame settings and recalculate
     */
    public function updateFaceFrame(): void
    {
        if (!$this->cabinet) {
            return;
        }

        // Validate inputs
        $this->stileWidth = max(CabinetConfiguratorService::MIN_FRAME_MEMBER_WIDTH_INCHES, min(CabinetConfiguratorService::MAX_FRAME_MEMBER_WIDTH_INCHES, $this->stileWidth));
        $this->railWidth = max(CabinetConfiguratorService::MIN_FRAME_MEMBER_WIDTH_INCHES, min(CabinetConfiguratorService::MAX_FRAME_MEMBER_WIDTH_INCHES, $this->railWidth));
        $this->midStileCount = max(0, $this->midStileCount);

        // Save to cabinet
        $this->cabinet->face_frame_stile_width_inches = $this->stileWidth;
        $this->cabinet->face_frame_rail_width_inches = $this->railWidth;
        $this->cabinet->face_frame_mid_stile_count = $this->midStileCount;
        $this->cabinet->save();

        // Recalculate
        $this->recalculateFrame();

        // Recalculate section positions
        $this->configurator->recalculateSectionPositions($this->cabinet);
        $this->refreshSections();
        $this->validateLayout();

        Notification::make()
            ->title('Face Frame Updated')
            ->success()
            ->send();
    }

    /**
     * Update construction type
     */
    public function updateConstructionType(): void
    {
        if (!$this->cabinet) {
            return;
        }

        $this->cabinet->construction_type = $this->constructionType;
        $this->cabinet->save();

        $this->recalculateFrame();
        $this->refreshStretcherInfo();
        $this->validateLayout();

        Notification::make()
            ->title('Construction Type Updated')
            ->body("Changed to " . ($this->constructionType === 'face_frame' ? 'Face Frame' : 'Frameless'))
            ->success()
            ->send();
    }

    /**
     * Refresh sections list from database
     */
    public function refreshSections(): void
    {
        if (!$this->cabinet) {
            return;
        }

        $this->cabinet->refresh();
        $this->cabinet->load(['sections.drawers', 'sections.doors', 'sections.shelves', 'sections.pullouts', 'sections.falseFronts']);

        $this->sections = $this->cabinet->sections->sortBy('sort_order')->map(function ($section) {
            return [
                'id' => $section->id,
                'type' => $section->section_type ?? 'door',
                'name' => $section->name ?? 'Section ' . $section->section_number,
                'width' => $section->opening_width_inches ?? 0,
                'height' => $section->opening_height_inches ?? 0,
                'position_left' => $section->position_from_left_inches ?? 0,
                'position_bottom' => $section->position_from_bottom_inches ?? 0,
                'ratio' => $section->section_width_ratio ?? 0.5,
                'sort_order' => $section->sort_order ?? 0,
                'component_count' => $section->total_components,
                'drawer_count' => $section->drawers->count(),
                'door_count' => $section->doors->count(),
                'shelf_count' => $section->shelves->count(),
            ];
        })->values()->toArray();

        // Update mid-stile count based on sections
        $this->midStileCount = max(0, count($this->sections) - 1);
    }

    /**
     * Refresh stretcher information
     */
    public function refreshStretcherInfo(): void
    {
        if (!$this->cabinet) {
            return;
        }

        $this->needsStretchers = $this->stretcherCalculator->cabinetNeedsStretchers($this->cabinet);
        $this->requiredStretcherCount = $this->stretcherCalculator->getRequiredStretcherCount($this->cabinet);
        $this->existingStretcherCount = $this->cabinet->stretchers()->count();
    }

    /**
     * Validate the current layout
     */
    public function validateLayout(): void
    {
        if (!$this->cabinet) {
            return;
        }

        $result = $this->configurator->validateLayout($this->cabinet);

        $this->isValid = $result['valid'];
        $this->validationErrors = $result['errors'];
        $this->validationWarnings = $result['warnings'];
    }

    /**
     * Open template modal
     */
    public function openTemplateModal(): void
    {
        $this->selectedTemplate = '';
        $this->showTemplateModal = true;
    }

    /**
     * Apply a template
     */
    public function applyTemplate(): void
    {
        if (!$this->cabinet || empty($this->selectedTemplate)) {
            return;
        }

        $result = $this->configurator->applyTemplate($this->cabinet, $this->selectedTemplate);

        if ($result['success']) {
            $this->showTemplateModal = false;
            $this->refreshSections();
            $this->recalculateFrame();
            $this->validateLayout();

            Notification::make()
                ->title('Template Applied')
                ->body("Applied '{$this->selectedTemplate}' template with " . count($result['sections']) . " sections")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Template Error')
                ->body($result['error'] ?? 'Failed to apply template')
                ->danger()
                ->send();
        }
    }

    /**
     * Open add section modal
     */
    public function openAddSectionModal(): void
    {
        $this->addSectionType = 'door';
        $this->addSectionRatio = 1.0 / max(1, count($this->sections) + 1);
        $this->showAddSectionModal = true;
    }

    /**
     * Add a new section
     */
    public function addSection(): void
    {
        if (!$this->cabinet) {
            return;
        }

        // Calculate opening dimensions from frame
        $this->recalculateFrame();

        $data = [
            'section_type' => $this->addSectionType,
            'sort_order' => count($this->sections) + 1,
            'opening_width_inches' => $this->frameDimensions['total_opening_width'] * $this->addSectionRatio,
            'opening_height_inches' => $this->frameDimensions['total_opening_height'],
            'section_width_ratio' => $this->addSectionRatio,
        ];

        $section = $this->configurator->createSection($this->cabinet, $data);

        $this->showAddSectionModal = false;

        // Recalculate all section positions
        $this->configurator->recalculateSectionPositions($this->cabinet);
        $this->refreshSections();
        $this->recalculateFrame();
        $this->validateLayout();

        Notification::make()
            ->title('Section Added')
            ->body("Added {$this->addSectionType} section")
            ->success()
            ->send();
    }

    /**
     * Open edit section modal
     */
    public function openEditSectionModal(int $sectionId): void
    {
        $section = CabinetSection::find($sectionId);
        if (!$section) {
            return;
        }

        $this->editSectionId = $sectionId;
        $this->editSectionType = $section->section_type ?? 'door';
        $this->editSectionName = $section->name ?? '';
        $this->editSectionWidth = $section->opening_width_inches ?? 0;
        $this->editSectionHeight = $section->opening_height_inches ?? 0;
        $this->editSectionRatio = $section->section_width_ratio ?? 0.5;
        $this->showEditSectionModal = true;
    }

    /**
     * Update a section
     */
    public function updateSection(): void
    {
        if (!$this->editSectionId) {
            return;
        }

        $section = CabinetSection::find($this->editSectionId);
        if (!$section) {
            return;
        }

        $this->configurator->updateSection($section, [
            'section_type' => $this->editSectionType,
            'name' => $this->editSectionName,
            'opening_width_inches' => $this->editSectionWidth,
            'opening_height_inches' => $this->editSectionHeight,
            'section_width_ratio' => $this->editSectionRatio,
        ]);

        $this->showEditSectionModal = false;

        // Recalculate positions
        $this->configurator->recalculateSectionPositions($this->cabinet);
        $this->refreshSections();
        $this->recalculateFrame();
        $this->validateLayout();

        Notification::make()
            ->title('Section Updated')
            ->success()
            ->send();
    }

    /**
     * Delete a section
     */
    public function deleteSection(int $sectionId): void
    {
        $section = CabinetSection::find($sectionId);
        if (!$section) {
            return;
        }

        $this->configurator->deleteSection($section);

        // Recalculate positions
        $this->configurator->recalculateSectionPositions($this->cabinet);
        $this->refreshSections();
        $this->recalculateFrame();
        $this->validateLayout();

        Notification::make()
            ->title('Section Deleted')
            ->success()
            ->send();
    }

    /**
     * Reorder sections (move left)
     */
    public function moveSectionLeft(int $sectionId): void
    {
        $currentIndex = collect($this->sections)->search(fn($s) => $s['id'] === $sectionId);
        if ($currentIndex === false || $currentIndex === 0) {
            return;
        }

        // Get IDs in new order
        $sectionIds = collect($this->sections)->pluck('id')->toArray();
        $temp = $sectionIds[$currentIndex];
        $sectionIds[$currentIndex] = $sectionIds[$currentIndex - 1];
        $sectionIds[$currentIndex - 1] = $temp;

        $this->configurator->reorderSections($this->cabinet, $sectionIds);
        $this->refreshSections();
        $this->validateLayout();
    }

    /**
     * Reorder sections (move right)
     */
    public function moveSectionRight(int $sectionId): void
    {
        $currentIndex = collect($this->sections)->search(fn($s) => $s['id'] === $sectionId);
        if ($currentIndex === false || $currentIndex >= count($this->sections) - 1) {
            return;
        }

        // Get IDs in new order
        $sectionIds = collect($this->sections)->pluck('id')->toArray();
        $temp = $sectionIds[$currentIndex];
        $sectionIds[$currentIndex] = $sectionIds[$currentIndex + 1];
        $sectionIds[$currentIndex + 1] = $temp;

        $this->configurator->reorderSections($this->cabinet, $sectionIds);
        $this->refreshSections();
        $this->validateLayout();
    }

    /**
     * Generate stretchers for the cabinet
     */
    public function generateStretchers(): void
    {
        if (!$this->cabinet) {
            return;
        }

        $stretchers = $this->stretcherCalculator->createStretchersForCabinet($this->cabinet, true);

        $this->refreshStretcherInfo();

        Notification::make()
            ->title('Stretchers Generated')
            ->body("Created {$stretchers->count()} stretcher(s)")
            ->success()
            ->send();
    }

    /**
     * Open section in Opening Configurator
     */
    public function openSectionConfigurator(int $sectionId): void
    {
        $this->dispatch('load-section', sectionId: $sectionId);
    }

    /**
     * Get available templates
     */
    public function getTemplates(): array
    {
        return CabinetConfiguratorService::getTemplates();
    }

    /**
     * Get section types
     */
    public function getSectionTypes(): array
    {
        return CabinetSection::SECTION_TYPES;
    }

    /**
     * Get section type color
     */
    public function getSectionColor(string $type): string
    {
        return match ($type) {
            'door' => '#8B5CF6',        // purple
            'drawer_bank' => '#3B82F6', // blue
            'open_shelf' => '#10B981',  // green
            'pullout' => '#F59E0B',     // amber
            'false_front' => '#78716C', // stone/brown
            'appliance' => '#EF4444',   // red
            'mixed' => '#6366F1',       // indigo
            default => '#6B7280',       // gray
        };
    }

    /**
     * Get section type icon
     */
    public function getSectionIcon(string $type): string
    {
        return match ($type) {
            'door' => 'heroicon-o-rectangle-group',
            'drawer_bank' => 'heroicon-o-inbox-stack',
            'open_shelf' => 'heroicon-o-bars-3',
            'pullout' => 'heroicon-o-arrow-right-on-rectangle',
            'false_front' => 'heroicon-o-stop',
            'appliance' => 'heroicon-o-cube',
            'mixed' => 'heroicon-o-squares-2x2',
            default => 'heroicon-o-square-3-stack-3d',
        };
    }

    /**
     * Get configuration summary
     */
    public function getConfigurationSummary(): array
    {
        if (!$this->cabinet) {
            return [];
        }

        return $this->configurator->getConfigurationSummary($this->cabinet);
    }

    public function render()
    {
        return view('webkul-project::livewire.cabinet-configurator');
    }
}
