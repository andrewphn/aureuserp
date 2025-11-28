<?php

namespace Webkul\Sale\Filament\Clusters\Settings\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Webkul\Sale\Settings\PriceSettings;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Manage Pricing class
 *
 * @see \Filament\Resources\Resource
 */
class ManagePricing extends SettingsPage
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $slug = 'sale/manage-pricing';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static string $settings = PriceSettings::class;

    protected static ?string $cluster = Settings::class;

    /**
     * Get the breadcrumb items for this page
     *
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            __('sales::filament/clusters/settings/pages/manage-pricing.breadcrumb'),
        ];
    }

    /**
     * Get the page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return __('sales::filament/clusters/settings/pages/manage-pricing.title');
    }

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/settings/pages/manage-pricing.navigation.title');
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
                Toggle::make('enable_discount')
                    ->label(__('sales::filament/clusters/settings/pages/manage-pricing.form.fields.discount'))
                    ->helperText(__('sales::filament/clusters/settings/pages/manage-pricing.form.fields.discount-help')),
                Toggle::make('enable_margin')
                    ->label(__('sales::filament/clusters/settings/pages/manage-pricing.form.fields.margins'))
                    ->helperText(__('sales::filament/clusters/settings/pages/manage-pricing.form.fields.margins-help')),
            ]);
    }
}
