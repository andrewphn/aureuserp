<?php

namespace Webkul\Inventory\Filament\Clusters\Settings\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Settings\LogisticSettings;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Manage Logistics class
 *
 * @see \Filament\Resources\Resource
 */
class ManageLogistics extends SettingsPage
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $slug = 'inventory/manage-logistics';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 5;

    protected static string $settings = LogisticSettings::class;

    protected static ?string $cluster = Settings::class;

    public function getBreadcrumbs(): array
    {
        return [
            __('inventories::filament/clusters/settings/pages/manage-logistics.title'),
        ];
    }

    public function getTitle(): string
    {
        return __('inventories::filament/clusters/settings/pages/manage-logistics.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/settings/pages/manage-logistics.title');
    }

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('enable_dropshipping')
                    ->label(__('inventories::filament/clusters/settings/pages/manage-logistics.form.enable-dropshipping'))
                    ->helperText(__('inventories::filament/clusters/settings/pages/manage-logistics.form.enable-dropshipping-helper-text')),
            ]);
    }

    /**
     * After Save
     *
     * @return void
     */
    protected function afterSave(): void
    {
        OperationType::withTrashed()->where('type', Enums\OperationType::DROPSHIP)->update(['deleted_at' => $this->data['enable_dropshipping'] ? null : now()]);
    }
}
