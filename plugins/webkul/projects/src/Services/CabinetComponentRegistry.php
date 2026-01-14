<?php

namespace Webkul\Project\Services;

use Webkul\Project\Contracts\CabinetComponentInterface;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Pullout;
use Webkul\Project\Models\CabinetSection;
use Illuminate\Database\Eloquent\Model;

/**
 * Cabinet Component Registry
 *
 * Centralized registry for cabinet component types and their mappings.
 * Use this service to get model classes, table names, and create instances
 * based on component type strings (door, drawer, shelf, pullout).
 *
 * All registered components implement CabinetComponentInterface, enabling
 * type-safe handling throughout the application.
 *
 * This allows consistent mapping across the application:
 * - CabinetSpecBuilder (JSON draft)
 * - CabinetSpecTreeRelationManager (syncing to DB)
 * - ReviewPdfAndPrice (saving from PDF annotations)
 * - API endpoints
 * - Reports and exports
 *
 * @example
 * // Get the model class for a component type
 * $modelClass = CabinetComponentRegistry::getModelClass('drawer');
 * // Returns: Webkul\Project\Models\Drawer::class
 *
 * @example
 * // Create a component from spec data (returns CabinetComponentInterface)
 * $drawer = CabinetComponentRegistry::createFromSpec('drawer', $section, $specData);
 *
 * @example
 * // Type-safe component handling
 * public function processComponent(CabinetComponentInterface $component) {
 *     $type = CabinetComponentRegistry::getTypeFromModel($component);
 *     $code = $component->getComponentCode();
 * }
 */
class CabinetComponentRegistry
{
    /**
     * Component type definitions
     *
     * Each component type maps to:
     * - model: The Eloquent model class
     * - table: The database table name
     * - relationship: The relationship name on CabinetSection
     * - name_field: The field used for the component's name/label
     * - label: Human-readable label (singular)
     * - label_plural: Human-readable label (plural)
     * - icon: Heroicon name for UI
     */
    public const COMPONENTS = [
        'door' => [
            'model' => Door::class,
            'table' => 'projects_doors',
            'relationship' => 'doors',
            'name_field' => 'door_name',
            'label' => 'Door',
            'label_plural' => 'Doors',
            'icon' => 'heroicon-o-rectangle-group',
            'default_fields' => [
                'width_inches' => null,
                'height_inches' => null,
                'hinge_side' => 'left',
                'has_glass' => false,
            ],
        ],
        'drawer' => [
            'model' => Drawer::class,
            'table' => 'projects_drawers',
            'relationship' => 'drawers',
            'name_field' => 'drawer_name',
            'label' => 'Drawer',
            'label_plural' => 'Drawers',
            'icon' => 'heroicon-o-inbox-stack',
            'default_fields' => [
                'front_width_inches' => null,
                'front_height_inches' => 6,
                'box_depth_inches' => null,
                'box_material' => 'maple',
                'joinery_method' => 'dovetail',
            ],
        ],
        'shelf' => [
            'model' => Shelf::class,
            'table' => 'projects_shelves',
            'relationship' => 'shelves',
            'name_field' => 'shelf_name',
            'label' => 'Shelf',
            'label_plural' => 'Shelves',
            'icon' => 'heroicon-o-minus',
            'default_fields' => [
                'width_inches' => null,
                'depth_inches' => null,
                'shelf_type' => 'adjustable',
            ],
        ],
        'pullout' => [
            'model' => Pullout::class,
            'table' => 'projects_pullouts',
            'relationship' => 'pullouts',
            'name_field' => 'pullout_name',
            'label' => 'Pullout',
            'label_plural' => 'Pullouts',
            'icon' => 'heroicon-o-archive-box',
            'default_fields' => [
                'pullout_type' => 'trash',
                'width_inches' => null,
                'height_inches' => null,
                'depth_inches' => null,
            ],
        ],
    ];

    /**
     * Section types that can contain components
     */
    public const SECTION_TYPES = [
        'door' => [
            'label' => 'Door Section',
            'allowed_components' => ['door', 'shelf'],
            'icon' => 'heroicon-o-rectangle-group',
        ],
        'drawer_bank' => [
            'label' => 'Drawer Bank',
            'allowed_components' => ['drawer'],
            'icon' => 'heroicon-o-inbox-stack',
        ],
        'open_shelf' => [
            'label' => 'Open Shelf',
            'allowed_components' => ['shelf'],
            'icon' => 'heroicon-o-squares-2x2',
        ],
        'appliance' => [
            'label' => 'Appliance Opening',
            'allowed_components' => [],
            'icon' => 'heroicon-o-cube',
        ],
        'pullout' => [
            'label' => 'Pullout Section',
            'allowed_components' => ['pullout', 'shelf'],
            'icon' => 'heroicon-o-archive-box',
        ],
        'mixed' => [
            'label' => 'Mixed (Doors & Drawers)',
            'allowed_components' => ['door', 'drawer', 'shelf', 'pullout'],
            'icon' => 'heroicon-o-squares-plus',
        ],
    ];

    // =========================================================================
    // Component Type Lookups
    // =========================================================================

    /**
     * Get all registered component types
     *
     * @return array<string>
     */
    public static function getTypes(): array
    {
        return array_keys(self::COMPONENTS);
    }

    /**
     * Check if a component type is valid
     */
    public static function isValidType(string $type): bool
    {
        return isset(self::COMPONENTS[$type]);
    }

    /**
     * Get the full definition for a component type
     */
    public static function getDefinition(string $type): ?array
    {
        return self::COMPONENTS[$type] ?? null;
    }

    /**
     * Get the Eloquent model class for a component type
     *
     * @param string $type Component type (door, drawer, shelf, pullout)
     * @return string|null Model class name
     */
    public static function getModelClass(string $type): ?string
    {
        return self::COMPONENTS[$type]['model'] ?? null;
    }

    /**
     * Get the database table name for a component type
     */
    public static function getTable(string $type): ?string
    {
        return self::COMPONENTS[$type]['table'] ?? null;
    }

    /**
     * Get the relationship name on CabinetSection for a component type
     */
    public static function getRelationship(string $type): ?string
    {
        return self::COMPONENTS[$type]['relationship'] ?? null;
    }

    /**
     * Get the name field for a component type
     */
    public static function getNameField(string $type): ?string
    {
        return self::COMPONENTS[$type]['name_field'] ?? null;
    }

    /**
     * Get human-readable label for a component type
     */
    public static function getLabel(string $type, bool $plural = false): ?string
    {
        $def = self::COMPONENTS[$type] ?? null;
        if (!$def) return null;

        return $plural ? $def['label_plural'] : $def['label'];
    }

    /**
     * Get icon for a component type
     */
    public static function getIcon(string $type): ?string
    {
        return self::COMPONENTS[$type]['icon'] ?? null;
    }

    /**
     * Get default fields for a component type
     */
    public static function getDefaultFields(string $type): array
    {
        return self::COMPONENTS[$type]['default_fields'] ?? [];
    }

    /**
     * Get component types as options for a select field
     *
     * @return array<string, string>
     */
    public static function getTypeOptions(): array
    {
        $options = [];
        foreach (self::COMPONENTS as $type => $def) {
            $options[$type] = $def['label'];
        }
        return $options;
    }

    // =========================================================================
    // Section Type Lookups
    // =========================================================================

    /**
     * Get all section types
     */
    public static function getSectionTypes(): array
    {
        return array_keys(self::SECTION_TYPES);
    }

    /**
     * Get section type definition
     */
    public static function getSectionTypeDefinition(string $sectionType): ?array
    {
        return self::SECTION_TYPES[$sectionType] ?? null;
    }

    /**
     * Get allowed component types for a section type
     */
    public static function getAllowedComponentsForSection(string $sectionType): array
    {
        return self::SECTION_TYPES[$sectionType]['allowed_components'] ?? [];
    }

    /**
     * Check if a component type is allowed in a section type
     */
    public static function isComponentAllowedInSection(string $componentType, string $sectionType): bool
    {
        $allowed = self::getAllowedComponentsForSection($sectionType);
        return in_array($componentType, $allowed);
    }

    /**
     * Get section types as options for a select field
     */
    public static function getSectionTypeOptions(): array
    {
        $options = [];
        foreach (self::SECTION_TYPES as $type => $def) {
            $options[$type] = $def['label'];
        }
        return $options;
    }

    // =========================================================================
    // Component Creation & Sync
    // =========================================================================

    /**
     * Create a new component instance (not saved)
     *
     * @param string $type Component type
     * @param array $attributes Initial attributes
     * @return CabinetComponentInterface|null
     */
    public static function newInstance(string $type, array $attributes = []): ?CabinetComponentInterface
    {
        $modelClass = self::getModelClass($type);
        if (!$modelClass) {
            return null;
        }

        $defaults = self::getDefaultFields($type);
        return new $modelClass(array_merge($defaults, $attributes));
    }

    /**
     * Create a component from spec builder data and attach to section
     *
     * @param string $type Component type (door, drawer, shelf, pullout)
     * @param CabinetSection $section Parent section
     * @param array $specData Data from spec builder JSON
     * @return CabinetComponentInterface|null Created model
     */
    public static function createFromSpec(string $type, CabinetSection $section, array $specData): ?CabinetComponentInterface
    {
        $modelClass = self::getModelClass($type);
        $relationship = self::getRelationship($type);
        $nameField = self::getNameField($type);

        if (!$modelClass || !$relationship) {
            return null;
        }

        // Map common spec fields to model fields
        $fields = self::mapSpecToModelFields($type, $specData);

        // Set the name field
        if ($nameField && isset($specData['name'])) {
            $fields[$nameField] = $specData['name'];
        }

        // Always include cabinet_id
        $fields['cabinet_id'] = $section->cabinet_id;

        // Create via relationship
        return $section->$relationship()->create($fields);
    }

    /**
     * Update an existing component from spec data
     *
     * @param string $type Component type
     * @param CabinetComponentInterface&Model $model Existing model instance
     * @param array $specData Data from spec builder JSON
     * @return CabinetComponentInterface&Model Updated model
     */
    public static function updateFromSpec(string $type, CabinetComponentInterface $model, array $specData): CabinetComponentInterface
    {
        $nameField = self::getNameField($type);
        $fields = self::mapSpecToModelFields($type, $specData);

        // Set the name field
        if ($nameField && isset($specData['name'])) {
            $fields[$nameField] = $specData['name'];
        }

        $model->update($fields);
        return $model;
    }

    /**
     * Sync a component (create or update based on db_id)
     *
     * @param string $type Component type
     * @param CabinetSection $section Parent section
     * @param array $specData Spec data with optional db_id
     * @return array{id: int, model: CabinetComponentInterface}|null
     */
    public static function syncFromSpec(string $type, CabinetSection $section, array $specData): ?array
    {
        $modelClass = self::getModelClass($type);
        if (!$modelClass) {
            return null;
        }

        $dbId = $specData['db_id'] ?? null;
        /** @var CabinetComponentInterface|null $model */
        $model = null;

        if ($dbId) {
            $model = $modelClass::find($dbId);
            if ($model) {
                self::updateFromSpec($type, $model, $specData);
            }
        }

        if (!$model) {
            $model = self::createFromSpec($type, $section, $specData);
        }

        return $model ? ['id' => $model->id, 'model' => $model] : null;
    }

    /**
     * Map spec builder JSON fields to model fields
     *
     * @param string $type Component type
     * @param array $specData Spec builder data
     * @return array Model-ready fields
     */
    protected static function mapSpecToModelFields(string $type, array $specData): array
    {
        return match ($type) {
            'door' => [
                'width_inches' => $specData['width_inches'] ?? null,
                'height_inches' => $specData['height_inches'] ?? null,
                'hinge_side' => $specData['hinge_side'] ?? null,
                'has_glass' => $specData['has_glass'] ?? false,
                'profile_type' => $specData['profile_type'] ?? null,
                'hinge_type' => $specData['hinge_type'] ?? null,
            ],
            'drawer' => [
                'front_width_inches' => $specData['width_inches'] ?? null,
                'front_height_inches' => $specData['height_inches'] ?? null,
                'box_depth_inches' => $specData['depth_inches'] ?? null,
                'box_width_inches' => $specData['box_width_inches'] ?? null,
                'box_height_inches' => $specData['box_height_inches'] ?? null,
                'box_material' => $specData['box_material'] ?? null,
                'joinery_method' => $specData['joinery_method'] ?? null,
                'slide_type' => $specData['slide_type'] ?? null,
                'slide_model' => $specData['slide_model'] ?? null,
                'soft_close' => $specData['soft_close'] ?? true,
            ],
            'shelf' => [
                'width_inches' => $specData['width_inches'] ?? null,
                'depth_inches' => $specData['depth_inches'] ?? null,
                'thickness_inches' => $specData['thickness_inches'] ?? null,
                'shelf_type' => $specData['shelf_type'] ?? 'adjustable',
                'material' => $specData['material'] ?? null,
                'edge_treatment' => $specData['edge_treatment'] ?? null,
            ],
            'pullout' => [
                'pullout_type' => $specData['pullout_type'] ?? 'trash',
                'width_inches' => $specData['width_inches'] ?? null,
                'height_inches' => $specData['height_inches'] ?? null,
                'depth_inches' => $specData['depth_inches'] ?? null,
                'manufacturer' => $specData['manufacturer'] ?? null,
                'model_number' => $specData['model_number'] ?? null,
                'mounting_type' => $specData['mounting_type'] ?? null,
            ],
            default => [],
        };
    }

    // =========================================================================
    // Bulk Operations
    // =========================================================================

    /**
     * Sync multiple components of mixed types within a section
     *
     * @param CabinetSection $section Parent section
     * @param array $contents Array of content items from spec builder
     * @return array{created: int, updated: int, deleted: int}
     */
    public static function syncSectionContents(CabinetSection $section, array $contents): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'deleted' => 0];

        // Track existing IDs by type
        $existing = [];
        $processed = [];

        foreach (self::getTypes() as $type) {
            $relationship = self::getRelationship($type);
            $existing[$type] = $section->$relationship()->pluck('id')->toArray();
            $processed[$type] = [];
        }

        // Process each content item
        foreach ($contents as $contentData) {
            $type = $contentData['content_type'] ?? $contentData['type'] ?? null;

            if (!self::isValidType($type)) {
                continue;
            }

            $dbId = $contentData['db_id'] ?? null;
            $result = self::syncFromSpec($type, $section, $contentData);

            if ($result) {
                $processed[$type][] = $result['id'];

                if ($dbId) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
            }
        }

        // Delete removed items
        foreach (self::getTypes() as $type) {
            $toDelete = array_diff($existing[$type], $processed[$type]);
            if (!empty($toDelete)) {
                $modelClass = self::getModelClass($type);
                $modelClass::whereIn('id', $toDelete)->delete();
                $stats['deleted'] += count($toDelete);
            }
        }

        return $stats;
    }

    /**
     * Get all components for a section, organized by type
     *
     * @param CabinetSection $section
     * @return array<string, \Illuminate\Database\Eloquent\Collection>
     */
    public static function getAllComponentsForSection(CabinetSection $section): array
    {
        $components = [];

        foreach (self::getTypes() as $type) {
            $relationship = self::getRelationship($type);
            $components[$type] = $section->$relationship;
        }

        return $components;
    }

    /**
     * Get total component count for a section
     */
    public static function getTotalComponentCount(CabinetSection $section): int
    {
        $count = 0;

        foreach (self::getTypes() as $type) {
            $relationship = self::getRelationship($type);
            $count += $section->$relationship()->count();
        }

        return $count;
    }

    // =========================================================================
    // Reverse Lookup (from model to type)
    // =========================================================================

    /**
     * Get the component type from a model instance
     *
     * @param CabinetComponentInterface|Model $model
     */
    public static function getTypeFromModel(CabinetComponentInterface|Model $model): ?string
    {
        // If it implements the interface, use the static method
        if ($model instanceof CabinetComponentInterface) {
            return $model::getComponentType();
        }

        // Fallback to class lookup
        $modelClass = get_class($model);

        foreach (self::COMPONENTS as $type => $def) {
            if ($def['model'] === $modelClass) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Check if a model implements CabinetComponentInterface
     */
    public static function isComponent(Model $model): bool
    {
        return $model instanceof CabinetComponentInterface;
    }

    /**
     * Get all component model classes
     *
     * @return array<class-string<CabinetComponentInterface>>
     */
    public static function getModelClasses(): array
    {
        return array_column(self::COMPONENTS, 'model');
    }

    /**
     * Get the component type from a table name
     */
    public static function getTypeFromTable(string $table): ?string
    {
        foreach (self::COMPONENTS as $type => $def) {
            if ($def['table'] === $table) {
                return $type;
            }
        }

        return null;
    }
}
