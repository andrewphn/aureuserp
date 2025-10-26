<?php

namespace App\Services\Footer\Contracts;

use Filament\Infolists\Infolist;

/**
 * Context Provider Interface
 *
 * Defines the contract for all context providers in the global footer system.
 * Each entity type (project, sale, inventory, etc.) implements this interface
 * to provide context-specific data and field definitions.
 */
interface ContextProviderInterface
{
    /**
     * Get the unique identifier for this context type.
     *
     * @return string (e.g., 'project', 'sale', 'inventory', 'production')
     */
    public function getContextType(): string;

    /**
     * Get the display name for this context type.
     *
     * @return string (e.g., 'Project', 'Sales Order', 'Inventory Item')
     */
    public function getContextName(): string;

    /**
     * Get the empty state label when no context is active.
     *
     * @return string (e.g., 'No Project Selected', 'No Order Active')
     */
    public function getEmptyLabel(): string;

    /**
     * Get the border color for this context type (CSS color value).
     *
     * @return string (e.g., 'rgb(59, 130, 246)', '#3B82F6')
     */
    public function getBorderColor(): string;

    /**
     * Get the SVG path for the icon representing this context type.
     * Should be a valid SVG path 'd' attribute value.
     *
     * @return string (e.g., 'M3 7v10a2 2 0 002 2h14...')
     */
    public function getIconPath(): string;

    /**
     * Load context data for a specific entity ID.
     *
     * @param int|string $entityId
     * @return array<string, mixed> Entity data
     */
    public function loadContext(int|string $entityId): array;

    /**
     * Get the Infolist schema for displaying this context's fields.
     *
     * @param array<string, mixed> $data Context data
     * @param bool $isMinimized Whether footer is in minimized state
     * @return array Infolist components array
     */
    public function getFieldSchema(array $data, bool $isMinimized = false): array;

    /**
     * Get default preferences for this context type.
     *
     * @return array{
     *     minimized_fields: array<string>,
     *     expanded_fields: array<string>,
     *     field_order: array<string>
     * }
     */
    public function getDefaultPreferences(): array;

    /**
     * Get API endpoints configuration for this context.
     *
     * @return array{
     *     fetch?: callable,
     *     tags?: callable,
     *     additional?: array<string, callable>
     * }
     */
    public function getApiEndpoints(): array;

    /**
     * Check if this context type supports the given feature.
     *
     * @param string $feature (e.g., 'tags', 'timeline_alerts', 'estimates')
     * @return bool
     */
    public function supportsFeature(string $feature): bool;

    /**
     * Get additional actions available for this context.
     * Returns array of Filament Action instances.
     *
     * @param array<string, mixed> $data Context data
     * @return array<\Filament\Actions\Action>
     */
    public function getActions(array $data): array;
}
