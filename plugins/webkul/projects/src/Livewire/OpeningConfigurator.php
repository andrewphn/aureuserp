<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\OpeningConfiguratorService;
use App\Services\OpeningLayoutEngine;
use App\Services\OpeningValidator;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Pullout;
use Webkul\Project\Models\FalseFront;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

/**
 * Opening Configurator - Visual Builder
 *
 * Provides a visual interface for configuring components within
 * a cabinet section opening. Features:
 * - Visual representation of opening with components
 * - Drag-and-drop positioning (future)
 * - Real-time validation feedback
 * - Auto-arrange strategies
 * - Space usage indicator
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 */
class OpeningConfigurator extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    // Section being configured
    public ?int $sectionId = null;
    public ?CabinetSection $section = null;

    // Layout options
    public string $layoutStrategy = 'stack_from_bottom';
    public string $layoutDirection = 'vertical';

    // Gap settings (in inches)
    public float $topReveal = 0.125;
    public float $bottomReveal = 0.125;
    public float $componentGap = 0.125;

    // Space tracking
    public float $openingHeight = 0;
    public float $openingWidth = 0;
    public float $consumedHeight = 0;
    public float $remainingHeight = 0;
    public float $usagePercentage = 0;

    // Components list
    public array $components = [];

    // Validation state
    public bool $isValid = true;
    public array $validationErrors = [];
    public array $validationWarnings = [];

    // Add component modal
    public bool $showAddModal = false;
    public string $addComponentType = 'drawer';
    public float $addComponentHeight = 6.0;

    // Services
    protected OpeningConfiguratorService $configurator;
    protected OpeningLayoutEngine $layoutEngine;
    protected OpeningValidator $validator;

    public function boot(
        OpeningConfiguratorService $configurator,
        OpeningLayoutEngine $layoutEngine,
        OpeningValidator $validator
    ): void {
        $this->configurator = $configurator;
        $this->layoutEngine = $layoutEngine;
        $this->validator = $validator;
    }

    public function mount(?int $sectionId = null): void
    {
        if ($sectionId) {
            $this->loadSection($sectionId);
        }
    }

    /**
     * Load a section for configuration
     */
    #[On('load-section')]
    public function loadSection(int $sectionId): void
    {
        $this->sectionId = $sectionId;
        $this->section = CabinetSection::with(['drawers', 'shelves', 'doors', 'pullouts', 'falseFronts'])
            ->findOrFail($sectionId);

        // Load dimensions
        $this->openingHeight = $this->section->opening_height_inches ?? 0;
        $this->openingWidth = $this->section->opening_width_inches ?? 0;
        $this->layoutDirection = $this->section->layout_direction ?? 'vertical';

        // Load gap settings
        $this->topReveal = $this->section->top_reveal_inches ?? OpeningConfiguratorService::GAP_TOP_REVEAL_INCHES;
        $this->bottomReveal = $this->section->bottom_reveal_inches ?? OpeningConfiguratorService::GAP_BOTTOM_REVEAL_INCHES;
        $this->componentGap = $this->section->component_gap_inches ?? OpeningConfiguratorService::GAP_BETWEEN_COMPONENTS_INCHES;

        $this->refreshComponents();
        $this->validateOpening();
    }

    /**
     * Refresh the components list from database
     */
    public function refreshComponents(): void
    {
        if (!$this->section) {
            return;
        }

        $this->section->refresh();
        $this->section->load(['drawers', 'shelves', 'doors', 'pullouts', 'falseFronts']);

        $this->components = [];

        foreach ($this->section->drawers as $drawer) {
            $this->components[] = [
                'id' => $drawer->id,
                'type' => 'drawer',
                'name' => $drawer->drawer_name ?? 'Drawer ' . $drawer->drawer_number,
                'height' => $drawer->front_height_inches ?? 0,
                'width' => $drawer->front_width_inches ?? $this->openingWidth,
                'position' => $drawer->position_in_opening_inches,
                'consumed_height' => $drawer->consumed_height_inches,
                'sort_order' => $drawer->sort_order ?? 0,
            ];
        }

        foreach ($this->section->shelves as $shelf) {
            $this->components[] = [
                'id' => $shelf->id,
                'type' => 'shelf',
                'name' => $shelf->shelf_name ?? 'Shelf ' . $shelf->shelf_number,
                'height' => OpeningConfiguratorService::MIN_SHELF_OPENING_HEIGHT_INCHES,
                'width' => $shelf->width_inches ?? $this->openingWidth,
                'position' => $shelf->position_in_opening_inches,
                'consumed_height' => $shelf->consumed_height_inches,
                'sort_order' => $shelf->sort_order ?? 0,
            ];
        }

        foreach ($this->section->doors as $door) {
            $this->components[] = [
                'id' => $door->id,
                'type' => 'door',
                'name' => $door->door_name ?? 'Door ' . $door->door_number,
                'height' => $door->height_inches ?? 0,
                'width' => $door->width_inches ?? $this->openingWidth,
                'position' => $door->position_in_opening_inches,
                'consumed_height' => $door->consumed_height_inches,
                'sort_order' => $door->sort_order ?? 0,
            ];
        }

        foreach ($this->section->pullouts as $pullout) {
            $this->components[] = [
                'id' => $pullout->id,
                'type' => 'pullout',
                'name' => $pullout->pullout_name ?? 'Pullout ' . $pullout->pullout_number,
                'height' => $pullout->height_inches ?? 0,
                'width' => $pullout->width_inches ?? $this->openingWidth,
                'position' => $pullout->position_in_opening_inches,
                'consumed_height' => $pullout->consumed_height_inches,
                'sort_order' => $pullout->sort_order ?? 0,
            ];
        }

        foreach ($this->section->falseFronts as $falseFront) {
            $this->components[] = [
                'id' => $falseFront->id,
                'type' => 'false_front',
                'name' => $falseFront->false_front_name ?? 'False Front ' . $falseFront->false_front_number,
                'height' => $falseFront->height_inches ?? 0,
                'width' => $falseFront->width_inches ?? $this->openingWidth,
                'position' => $falseFront->position_in_opening_inches,
                'consumed_height' => $falseFront->consumed_height_inches,
                'sort_order' => $falseFront->sort_order ?? 0,
            ];
        }

        // Sort by sort_order
        usort($this->components, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        // Update space tracking
        $this->consumedHeight = $this->section->total_consumed_height_inches ?? 0;
        $this->remainingHeight = $this->section->remaining_height_inches ?? $this->openingHeight;
        $this->usagePercentage = $this->openingHeight > 0
            ? ($this->consumedHeight / $this->openingHeight) * 100
            : 0;
    }

    /**
     * Run opening validation
     */
    public function validateOpening(): void
    {
        if (!$this->section) {
            return;
        }

        $result = $this->validator->validateSection($this->section);

        $this->isValid = $result->isValid();
        $this->validationErrors = $result->errors;
        $this->validationWarnings = $result->warnings;
    }

    /**
     * Auto-arrange components using selected strategy
     */
    public function autoArrange(): void
    {
        if (!$this->section) {
            return;
        }

        // Save gap settings first
        $this->section->top_reveal_inches = $this->topReveal;
        $this->section->bottom_reveal_inches = $this->bottomReveal;
        $this->section->component_gap_inches = $this->componentGap;
        $this->section->layout_direction = $this->layoutDirection;
        $this->section->save();

        // Run auto-arrange
        $result = $this->layoutEngine->autoArrange($this->section, $this->layoutStrategy);

        if ($result['success']) {
            Notification::make()
                ->title('Components Arranged')
                ->body(sprintf(
                    'Used %.4f" of %.4f" (%.1f%% - %.4f" remaining)',
                    $result['total_consumed'] ?? 0,
                    $this->openingHeight,
                    $this->openingHeight > 0 ? (($result['total_consumed'] ?? 0) / $this->openingHeight) * 100 : 0,
                    $result['remaining'] ?? 0
                ))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Layout Warning')
                ->body(sprintf(
                    'Components exceed opening by %.4f"',
                    $result['overflow'] ?? 0
                ))
                ->warning()
                ->send();
        }

        $this->refreshComponents();
        $this->validateOpening();
    }

    /**
     * Update gap settings
     */
    public function updateGapSettings(): void
    {
        if (!$this->section) {
            return;
        }

        $this->section->top_reveal_inches = $this->topReveal;
        $this->section->bottom_reveal_inches = $this->bottomReveal;
        $this->section->component_gap_inches = $this->componentGap;
        $this->section->save();

        // Recalculate layout
        $this->configurator->calculateSectionLayout($this->section);
        $this->refreshComponents();
        $this->validateOpening();

        Notification::make()
            ->title('Gap Settings Updated')
            ->success()
            ->send();
    }

    /**
     * Change layout direction
     */
    public function updateLayoutDirection(): void
    {
        if (!$this->section) {
            return;
        }

        $this->section->layout_direction = $this->layoutDirection;
        $this->section->save();

        // Recalculate layout
        $this->configurator->calculateSectionLayout($this->section);
        $this->refreshComponents();
        $this->validateOpening();
    }

    /**
     * Move component up in stack (increase sort_order)
     */
    public function moveUp(int $componentId, string $type): void
    {
        $model = $this->getComponentModel($componentId, $type);
        if (!$model) {
            return;
        }

        // Find the component above
        $currentOrder = $model->sort_order ?? 0;
        $above = $this->findComponentAbove($currentOrder);

        if ($above) {
            // Swap sort orders
            $aboveModel = $this->getComponentModel($above['id'], $above['type']);
            if ($aboveModel) {
                $model->sort_order = $above['sort_order'];
                $aboveModel->sort_order = $currentOrder;
                $model->save();
                $aboveModel->save();
            }
        }

        $this->autoArrange();
    }

    /**
     * Move component down in stack (decrease sort_order)
     */
    public function moveDown(int $componentId, string $type): void
    {
        $model = $this->getComponentModel($componentId, $type);
        if (!$model) {
            return;
        }

        $currentOrder = $model->sort_order ?? 0;
        $below = $this->findComponentBelow($currentOrder);

        if ($below) {
            $belowModel = $this->getComponentModel($below['id'], $below['type']);
            if ($belowModel) {
                $model->sort_order = $below['sort_order'];
                $belowModel->sort_order = $currentOrder;
                $model->save();
                $belowModel->save();
            }
        }

        $this->autoArrange();
    }

    /**
     * Open add component modal
     */
    public function openAddModal(string $type = 'drawer'): void
    {
        $this->addComponentType = $type;
        $this->addComponentHeight = match ($type) {
            'drawer' => 6.0,
            'door' => $this->openingHeight,
            'shelf' => OpeningConfiguratorService::MIN_SHELF_OPENING_HEIGHT_INCHES,
            'pullout' => 12.0,
            'false_front' => 4.0, // Typical false front height
            default => 6.0,
        };
        $this->showAddModal = true;
    }

    /**
     * Add a new component
     */
    public function addComponent(): void
    {
        if (!$this->section) {
            return;
        }

        // Check if it fits
        if (!$this->configurator->canFitComponent($this->section, $this->addComponentType, $this->addComponentHeight)) {
            Notification::make()
                ->title('Not Enough Space')
                ->body('The component does not fit in the remaining opening space.')
                ->danger()
                ->send();
            return;
        }

        $nextNumber = $this->getNextComponentNumber($this->addComponentType);
        $nextSortOrder = count($this->components) + 1;

        $data = [
            'cabinet_id' => $this->section->cabinet_id,
            'section_id' => $this->section->id,
            'sort_order' => $nextSortOrder,
        ];

        match ($this->addComponentType) {
            'drawer' => Drawer::create(array_merge($data, [
                'drawer_number' => $nextNumber,
                'drawer_name' => 'DR' . $nextNumber,
                'front_height_inches' => $this->addComponentHeight,
                'front_width_inches' => $this->openingWidth,
            ])),
            'shelf' => Shelf::create(array_merge($data, [
                'shelf_number' => $nextNumber,
                'shelf_name' => 'S' . $nextNumber,
                'width_inches' => $this->openingWidth,
                'thickness_inches' => 0.75,
                'shelf_type' => 'adjustable',
            ])),
            'door' => Door::create(array_merge($data, [
                'door_number' => $nextNumber,
                'door_name' => 'D' . $nextNumber,
                'height_inches' => $this->addComponentHeight,
                'width_inches' => $this->openingWidth,
            ])),
            'pullout' => Pullout::create(array_merge($data, [
                'pullout_number' => $nextNumber,
                'pullout_name' => 'P' . $nextNumber,
                'height_inches' => $this->addComponentHeight,
                'width_inches' => $this->openingWidth,
            ])),
            'false_front' => FalseFront::create(array_merge($data, [
                'false_front_number' => $nextNumber,
                'false_front_name' => 'FF' . $nextNumber,
                'height_inches' => $this->addComponentHeight,
                'width_inches' => $this->openingWidth,
                'false_front_type' => 'fixed',
            ])),
            default => null,
        };

        $this->showAddModal = false;
        $this->autoArrange();

        Notification::make()
            ->title('Component Added')
            ->body(ucfirst($this->addComponentType) . ' added successfully.')
            ->success()
            ->send();
    }

    /**
     * Remove a component
     */
    public function removeComponent(int $componentId, string $type): void
    {
        $model = $this->getComponentModel($componentId, $type);
        if ($model) {
            $model->delete();
            $this->autoArrange();

            Notification::make()
                ->title('Component Removed')
                ->success()
                ->send();
        }
    }

    /**
     * Get the model instance for a component
     */
    protected function getComponentModel(int $id, string $type): ?object
    {
        return match ($type) {
            'drawer' => Drawer::find($id),
            'shelf' => Shelf::find($id),
            'door' => Door::find($id),
            'pullout' => Pullout::find($id),
            'false_front' => FalseFront::find($id),
            default => null,
        };
    }

    /**
     * Find component above the given sort order
     */
    protected function findComponentAbove(int $sortOrder): ?array
    {
        $above = null;
        foreach ($this->components as $component) {
            if ($component['sort_order'] > $sortOrder) {
                if (!$above || $component['sort_order'] < $above['sort_order']) {
                    $above = $component;
                }
            }
        }
        return $above;
    }

    /**
     * Find component below the given sort order
     */
    protected function findComponentBelow(int $sortOrder): ?array
    {
        $below = null;
        foreach ($this->components as $component) {
            if ($component['sort_order'] < $sortOrder) {
                if (!$below || $component['sort_order'] > $below['sort_order']) {
                    $below = $component;
                }
            }
        }
        return $below;
    }

    /**
     * Get the next component number for a type
     */
    protected function getNextComponentNumber(string $type): int
    {
        $max = 0;
        foreach ($this->components as $component) {
            if ($component['type'] === $type) {
                // Extract number from name or use component count
                $max = max($max, count(array_filter(
                    $this->components,
                    fn($c) => $c['type'] === $type
                )));
            }
        }
        return $max + 1;
    }

    /**
     * Get available layout strategies
     */
    public function getLayoutStrategies(): array
    {
        return OpeningLayoutEngine::getStrategies();
    }

    /**
     * Get layout directions
     */
    public function getLayoutDirections(): array
    {
        return CabinetSection::LAYOUT_DIRECTIONS;
    }

    /**
     * Convert decimal to fraction for display
     */
    public function toFraction(float $decimal): string
    {
        return $this->configurator->toFraction($decimal);
    }

    /**
     * Get component type color for visual
     */
    public function getTypeColor(string $type): string
    {
        return match ($type) {
            'drawer' => '#3B82F6', // blue
            'shelf' => '#10B981', // green
            'door' => '#8B5CF6', // purple
            'pullout' => '#F59E0B', // amber
            'false_front' => '#78716C', // stone/brown
            default => '#6B7280', // gray
        };
    }

    /**
     * Get component type icon
     */
    public function getTypeIcon(string $type): string
    {
        return match ($type) {
            'drawer' => 'heroicon-o-inbox-stack',
            'shelf' => 'heroicon-o-bars-3',
            'door' => 'heroicon-o-rectangle-group',
            'pullout' => 'heroicon-o-arrow-right-on-rectangle',
            'false_front' => 'heroicon-o-stop', // solid rectangle
            default => 'heroicon-o-cube',
        };
    }

    public function render()
    {
        return view('webkul-project::livewire.opening-configurator');
    }
}
