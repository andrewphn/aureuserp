<?php

namespace Webkul\Inventory\Filament\Forms\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Inventory\Enums\DeliveryStep;
use Webkul\Inventory\Enums\ReceptionStep;
use Webkul\Inventory\Models\Warehouse;

/**
 * Reusable Warehouse form schema following FilamentPHP 4.x patterns
 * Can be used for Create/Edit modals across different resources
 *
 * @see https://filamentphp.com/docs/4.x/resources/code-quality-tips
 */
class WarehouseFormSchema
{
    /**
     * Configure the full warehouse form schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('company_id'),
            ...static::getEssentialFields(),
            static::getAddressSection(),
            static::getShipmentSettingsSection(),
            static::getResupplySettingsSection(),
        ]);
    }

    /**
     * Configure simplified warehouse form (essential only)
     */
    public static function configureSimplified(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('company_id'),
            ...static::getEssentialFields(),
        ]);
    }

    // ========================================
    // REUSABLE COMPONENT METHODS
    // ========================================

    /**
     * Warehouse name input field
     */
    public static function getNameInput(): TextInput
    {
        return TextInput::make('name')
            ->label('Warehouse Name')
            ->required()
            ->maxLength(255)
            ->placeholder('e.g. Main Warehouse')
            ->autofocus();
    }

    /**
     * Warehouse code input field
     */
    public static function getCodeInput(): TextInput
    {
        return TextInput::make('code')
            ->label('Short Code')
            ->required()
            ->maxLength(10)
            ->placeholder('e.g. WH1')
            ->helperText('Short identifier for the warehouse');
    }

    /**
     * Partner address select field
     */
    public static function getAddressSelect(): Select
    {
        return Select::make('partner_address_id')
            ->label('Warehouse Address')
            ->relationship('partnerAddress', 'name')
            ->searchable()
            ->preload()
            ->helperText('Physical location of the warehouse');
    }

    /**
     * Reception steps radio field
     */
    public static function getReceptionStepsRadio(): Radio
    {
        return Radio::make('reception_steps')
            ->label('Incoming Shipments')
            ->options(ReceptionStep::class)
            ->default(ReceptionStep::ONE_STEP)
            ->helperText('Steps for receiving goods');
    }

    /**
     * Delivery steps radio field
     */
    public static function getDeliveryStepsRadio(): Radio
    {
        return Radio::make('delivery_steps')
            ->label('Outgoing Shipments')
            ->options(DeliveryStep::class)
            ->default(DeliveryStep::ONE_STEP)
            ->helperText('Steps for delivering goods');
    }

    /**
     * Supplier warehouses checkbox list
     */
    public static function getSupplierWarehousesCheckboxList(): CheckboxList
    {
        return CheckboxList::make('supplierWarehouses')
            ->label('Resupply From')
            ->relationship('supplierWarehouses', 'name')
            ->helperText('Select warehouses that can resupply this one')
            ->columns(2);
    }

    // ========================================
    // FIELD GROUPS
    // ========================================

    /**
     * Get essential warehouse fields (always visible)
     */
    public static function getEssentialFields(): array
    {
        return [
            Grid::make(2)->schema([
                static::getNameInput(),
                static::getCodeInput(),
            ]),
        ];
    }

    // ========================================
    // COLLAPSIBLE SECTIONS
    // ========================================

    /**
     * Get address section (collapsible)
     */
    public static function getAddressSection(bool $collapsed = true): Section
    {
        return Section::make('Location')
            ->description('Warehouse physical address')
            ->icon('heroicon-o-map-pin')
            ->schema([
                static::getAddressSelect()->columnSpanFull(),
            ])
            ->compact()
            ->collapsible()
            ->collapsed($collapsed);
    }

    /**
     * Get shipment settings section (collapsible)
     */
    public static function getShipmentSettingsSection(bool $collapsed = true): Section
    {
        return Section::make('Shipment Settings')
            ->description('Configure incoming and outgoing shipment steps')
            ->icon('heroicon-o-truck')
            ->schema([
                Grid::make(2)->schema([
                    static::getReceptionStepsRadio(),
                    static::getDeliveryStepsRadio(),
                ]),
            ])
            ->compact()
            ->collapsible()
            ->collapsed($collapsed);
    }

    /**
     * Get resupply settings section (collapsible)
     */
    public static function getResupplySettingsSection(bool $collapsed = true): Section
    {
        return Section::make('Resupply Settings')
            ->description('Configure warehouse resupply sources')
            ->icon('heroicon-o-arrow-path')
            ->schema([
                static::getSupplierWarehousesCheckboxList(),
            ])
            ->compact()
            ->collapsible()
            ->collapsed($collapsed)
            ->visible(fn () => Warehouse::count() > 0);
    }

    // ========================================
    // ARRAY-BASED METHODS FOR BACKWARD COMPATIBILITY
    // ========================================

    /**
     * Get full warehouse form schema as array
     */
    public static function getFullSchema(): array
    {
        return [
            Hidden::make('company_id'),
            ...static::getEssentialFields(),
            static::getAddressSection(),
            static::getShipmentSettingsSection(),
            static::getResupplySettingsSection(),
        ];
    }

    /**
     * Get simplified form schema as array
     */
    public static function getSimplifiedSchema(): array
    {
        return [
            Hidden::make('company_id'),
            ...static::getEssentialFields(),
        ];
    }

    // ========================================
    // DATA LOADING HELPERS
    // ========================================

    /**
     * Get form defaults for new warehouse
     */
    public static function getDefaults(?int $companyId = null): array
    {
        return [
            'company_id' => $companyId,
            'reception_steps' => ReceptionStep::ONE_STEP,
            'delivery_steps' => DeliveryStep::ONE_STEP,
        ];
    }

    /**
     * Load warehouse data for form fill
     */
    public static function loadFormData(?int $warehouseId, ?int $companyId = null): array
    {
        if (!$warehouseId) {
            return static::getDefaults($companyId);
        }

        $warehouse = Warehouse::with('supplierWarehouses')->find($warehouseId);
        if (!$warehouse) {
            return static::getDefaults($companyId);
        }

        return [
            'name' => $warehouse->name,
            'code' => $warehouse->code,
            'company_id' => $warehouse->company_id ?? $companyId,
            'partner_address_id' => $warehouse->partner_address_id,
            'reception_steps' => $warehouse->reception_steps ?? ReceptionStep::ONE_STEP,
            'delivery_steps' => $warehouse->delivery_steps ?? DeliveryStep::ONE_STEP,
            'supplierWarehouses' => $warehouse->supplierWarehouses->pluck('id')->toArray(),
        ];
    }
}
