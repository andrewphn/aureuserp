<?php

namespace App\Services\Footer\Contexts;

use App\Services\Footer\Contracts\ContextProviderInterface;
use App\Services\Footer\ContextFieldBuilder;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\DB;

/**
 * Inventory Context Provider
 *
 * Provides context-specific data and field definitions for Inventory Item entities.
 */
class InventoryContextProvider implements ContextProviderInterface
{
    public function getContextType(): string
    {
        return 'inventory';
    }

    public function getContextName(): string
    {
        return 'Inventory Item';
    }

    public function getEmptyLabel(): string
    {
        return 'No Item Selected';
    }

    public function getBorderColor(): string
    {
        return 'rgb(168, 85, 247)'; // Purple
    }

    public function getIconPath(): string
    {
        // Cube/Box icon
        return 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4';
    }

    /**
     * Load Context
     *
     * @param int|string $entityId
     * @return array
     */
    public function loadContext(int|string $entityId): array
    {
        // Load inventory item data
        $item = DB::table('inventories_items')
            ->where('id', $entityId)
            ->first();

        if (!$item) {
            return [];
        }

        return (array) $item;
    }

    /**
     * Get Field Schema
     *
     * @param array $data The data array
     * @param bool $isMinimized
     * @return array
     */
    public function getFieldSchema(array $data, bool $isMinimized = false): array
    {
        if ($isMinimized) {
            return $this->getMinimizedSchema($data);
        }

        return $this->getExpandedSchema($data);
    }

    /**
     * Get Minimized Schema
     *
     * @param array $data The data array
     * @return array
     */
    protected function getMinimizedSchema(array $data): array
    {
        $quantity = $data['quantity'] ?? 0;
        $unit = $data['unit'] ?? 'units';

        return [
            ContextFieldBuilder::prominentText('name', 'Item')
                ->state($data['name'] ?? '—'),

            ContextFieldBuilder::text('_quantity_display', 'Quantity')
                ->state("{$quantity} {$unit}"),
        ];
    }

    /**
     * Get Expanded Schema
     *
     * @param array $data The data array
     * @return array
     */
    protected function getExpandedSchema(array $data): array
    {
        $fields = [
            ContextFieldBuilder::text('name', 'Item Name')
                ->state($data['name'] ?? '—')
                ->weight(FontWeight::SemiBold),

            ContextFieldBuilder::copyable('sku', 'SKU')
                ->state($data['sku'] ?? '—'),
        ];

        // Quantity with unit
        if (isset($data['quantity'])) {
            $unit = $data['unit'] ?? 'units';
            $fields[] = ContextFieldBuilder::number('quantity', 'Quantity', " {$unit}")
                ->state($data['quantity']);
        }

        // Location
        if (!empty($data['location'])) {
            $fields[] = ContextFieldBuilder::iconText('location', 'Location', 'heroicon-o-map-pin')
                ->state($data['location']);
        }

        // Reorder level (with warning if low)
        if (isset($data['reorder_level']) && isset($data['quantity'])) {
            $color = $data['quantity'] <= $data['reorder_level'] ? 'danger' : 'success';
            $fields[] = ContextFieldBuilder::badge('reorder_level', 'Reorder Level', $color)
                ->state($data['reorder_level']);
        }

        // Supplier
        if (!empty($data['supplier'])) {
            $fields[] = ContextFieldBuilder::text('supplier', 'Supplier')
                ->state($data['supplier']);
        }

        // Unit cost
        if (!empty($data['unit_cost'])) {
            $fields[] = ContextFieldBuilder::currency('unit_cost', 'Unit Cost')
                ->state($data['unit_cost']);
        }

        return $fields;
    }

    public function getDefaultPreferences(): array
    {
        return [
            'minimized_fields' => ['name', '_quantity_display'],
            'expanded_fields' => [
                'name',
                'sku',
                'quantity',
                'location',
                'reorder_level',
                'supplier',
                'unit_cost',
            ],
            'field_order' => [],
        ];
    }

    public function getApiEndpoints(): array
    {
        return [
            'fetch' => fn($id) => "/api/inventory/items/{$id}",
        ];
    }

    /**
     * Supports Feature
     *
     * @param string $feature
     * @return bool
     */
    public function supportsFeature(string $feature): bool
    {
        return false;
    }

    /**
     * Get Actions
     *
     * @param array $data The data array
     * @return array
     */
    public function getActions(array $data): array
    {
        $actions = [];

        if (!empty($data['id']) && !request()->is('*/edit')) {
            $actions[] = Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->url(route('filament.admin.resources.inventories.items.edit', ['record' => $data['id']]));
        }

        return $actions;
    }
}
