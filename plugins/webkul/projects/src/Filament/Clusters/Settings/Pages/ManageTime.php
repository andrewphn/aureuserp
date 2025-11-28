<?php

namespace Webkul\Project\Filament\Clusters\Settings\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use UnitEnum;
use Webkul\Project\Settings\TimeSettings;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Manage Time class
 *
 * @see \Filament\Resources\Resource
 */
class ManageTime extends SettingsPage
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Project';

    protected static string $settings = TimeSettings::class;

    protected static ?string $cluster = Settings::class;

    public function getBreadcrumbs(): array
    {
        return [
            __('webkul-project::filament/clusters/settings/pages/manage-time.title'),
        ];
    }

    public function getTitle(): string
    {
        return __('webkul-project::filament/clusters/settings/pages/manage-time.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('webkul-project::filament/clusters/settings/pages/manage-time.title');
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
                Toggle::make('enable_timesheets')
                    ->label(__('webkul-project::filament/clusters/settings/pages/manage-time.form.enable-timesheets'))
                    ->helperText(__('webkul-project::filament/clusters/settings/pages/manage-time.form.enable-timesheets-helper-text'))
                    ->required(),
            ]);
    }
}
