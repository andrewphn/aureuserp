<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Inventory\Filament\Clusters\Operations;
use Webkul\Inventory\Filament\Widgets\LowStockWidget;

/**
 * Operations Dashboard - Displays inventory widgets
 */
class OperationsDashboard extends BaseDashboard
{
    use HasPageShield;

    protected static ?string $cluster = Operations::class;

    protected static string $routePath = '/';

    protected static ?int $navigationSort = -1;

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-chart-bar';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Inventory Operations Dashboard';
    }

    public function getWidgets(): array
    {
        return [
            LowStockWidget::class,
        ];
    }
}
