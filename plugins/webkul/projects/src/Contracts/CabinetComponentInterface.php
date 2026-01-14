<?php

namespace Webkul\Project\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabinet Component Interface
 *
 * Defines the contract that all cabinet components (Door, Drawer, Shelf, Pullout)
 * must implement. This enables type-safe handling of components throughout the system.
 *
 * All components share:
 * - Hierarchical relationship (Project -> Room -> Location -> Run -> Cabinet -> Section -> Component)
 * - Common traits: HasFullCode, HasComplexityScore, HasFormattedDimensions
 * - Common fields: cabinet_id, section_id, product_id, sort_order, full_code, qc_*, notes
 *
 * @see \Webkul\Project\Models\Door
 * @see \Webkul\Project\Models\Drawer
 * @see \Webkul\Project\Models\Shelf
 * @see \Webkul\Project\Models\Pullout
 * @see \Webkul\Project\Services\CabinetComponentRegistry
 */
interface CabinetComponentInterface
{
    /**
     * Get the component-specific code (e.g., DOOR1, DRW2, SHELF1, PULL1)
     * Required by HasFullCode trait.
     */
    public function getComponentCode(): string;

    /**
     * Get the parent cabinet relationship.
     */
    public function cabinet(): BelongsTo;

    /**
     * Get the parent section relationship.
     */
    public function section(): BelongsTo;

    /**
     * Get the associated product relationship.
     */
    public function product(): BelongsTo;

    /**
     * Get hardware requirements for this component.
     */
    public function hardwareRequirements(): HasMany;

    /**
     * Scope to order by sort_order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query);

    /**
     * Get the component's name (door_name, drawer_name, etc.)
     */
    public function getComponentName(): ?string;

    /**
     * Get the component's number (door_number, drawer_number, etc.)
     */
    public function getComponentNumber(): ?int;

    /**
     * Get the component type identifier (door, drawer, shelf, pullout)
     */
    public static function getComponentType(): string;

    /**
     * Get the database table name for this component.
     * Note: No return type to maintain compatibility with Eloquent's Model::getTable()
     */
    public function getTable();
}
