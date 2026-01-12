<?php

namespace Webkul\Project\Observers;

use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Pullout;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Services\ComplexityScoreService;

/**
 * Observer for automatic complexity score recalculation.
 *
 * Watches component models (Door, Drawer, Shelf, Pullout) for changes
 * and triggers cascade recalculation up the hierarchy when relevant
 * attributes are modified.
 */
class ComplexityScoreObserver
{
    /**
     * Attributes that trigger recalculation for Door model.
     */
    protected const DOOR_WATCHED_ATTRIBUTES = [
        'hinge_type',
        'has_glass',
        'glass_type',
        'profile_type',
        'has_check_rail',
        'width_inches',
        'height_inches',
        'fabrication_method',
        'has_decorative_hardware',
    ];

    /**
     * Attributes that trigger recalculation for Drawer model.
     */
    protected const DRAWER_WATCHED_ATTRIBUTES = [
        'soft_close',
        'slide_type',
        'joinery_method',
        'front_width_inches',
        'front_height_inches',
        'box_width_inches',
        'box_depth_inches',
    ];

    /**
     * Attributes that trigger recalculation for Shelf model.
     */
    protected const SHELF_WATCHED_ATTRIBUTES = [
        'shelf_type',
        'slide_type',
        'soft_close',
        'width_inches',
        'depth_inches',
        'material',
    ];

    /**
     * Attributes that trigger recalculation for Pullout model.
     */
    protected const PULLOUT_WATCHED_ATTRIBUTES = [
        'pullout_type',
        'soft_close',
        'slide_type',
        'width_inches',
        'height_inches',
        'depth_inches',
    ];

    protected ComplexityScoreService $service;

    public function __construct(ComplexityScoreService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle the created event.
     * Recalculate complexity for the new component and cascade up.
     */
    public function created(Model $model): void
    {
        $this->triggerRecalculation($model);
    }

    /**
     * Handle the updated event.
     * Only recalculate if relevant attributes changed.
     */
    public function updated(Model $model): void
    {
        if ($this->hasRelevantChanges($model)) {
            $this->triggerRecalculation($model);
        }
    }

    /**
     * Handle the deleted event.
     * Recalculate parent complexity when a component is removed.
     */
    public function deleted(Model $model): void
    {
        $this->triggerParentRecalculation($model);
    }

    /**
     * Handle the restored event (for soft deletes).
     * Recalculate complexity when a component is restored.
     */
    public function restored(Model $model): void
    {
        $this->triggerRecalculation($model);
    }

    /**
     * Check if the model has changes to attributes that affect complexity.
     */
    protected function hasRelevantChanges(Model $model): bool
    {
        $watchedAttributes = $this->getWatchedAttributes($model);
        $changedAttributes = array_keys($model->getDirty());

        return ! empty(array_intersect($watchedAttributes, $changedAttributes));
    }

    /**
     * Get the list of watched attributes for a given model.
     */
    protected function getWatchedAttributes(Model $model): array
    {
        return match (true) {
            $model instanceof Door => self::DOOR_WATCHED_ATTRIBUTES,
            $model instanceof Drawer => self::DRAWER_WATCHED_ATTRIBUTES,
            $model instanceof Shelf => self::SHELF_WATCHED_ATTRIBUTES,
            $model instanceof Pullout => self::PULLOUT_WATCHED_ATTRIBUTES,
            default => [],
        };
    }

    /**
     * Trigger complexity recalculation for the model and cascade up.
     */
    protected function triggerRecalculation(Model $model): void
    {
        // Queue the recalculation to avoid blocking the request
        // For now, we do it synchronously but this could be dispatched to a job
        try {
            $this->service->cascadeRecalculation($model);
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            \Log::warning('Complexity recalculation failed', [
                'model' => get_class($model),
                'id' => $model->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger recalculation for parent entities when a component is deleted.
     */
    protected function triggerParentRecalculation(Model $model): void
    {
        try {
            // Get the parent section
            $section = $model->section ?? null;

            if ($section) {
                $this->service->cascadeRecalculation($section);
            }
        } catch (\Exception $e) {
            \Log::warning('Parent complexity recalculation failed after delete', [
                'model' => get_class($model),
                'id' => $model->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
