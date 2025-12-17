<?php

namespace Webkul\Timesheet;

use Livewire\Livewire;
use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;
use Webkul\Timesheet\Filament\Widgets\AttendanceStatsWidget;
use Webkul\Timesheet\Filament\Widgets\TodayAttendanceWidget;
use Webkul\Timesheet\Filament\Widgets\WeeklyHoursReportWidget;

/**
 * Timesheet Service Provider service provider
 *
 */
class TimesheetServiceProvider extends PackageServiceProvider
{
    public static string $name = 'timesheets';

    /**
     * Configure Custom Package
     *
     * @param Package $package
     * @return void
     */
    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasDependencies([
                'projects',
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->installDependencies();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    /**
     * Package Booted
     *
     * @return void
     */
    public function packageBooted(): void
    {
        // Register Livewire components for cross-plugin widget usage
        Livewire::component('webkul.timesheet.filament.widgets.attendance-stats-widget', AttendanceStatsWidget::class);
        Livewire::component('webkul.timesheet.filament.widgets.today-attendance-widget', TodayAttendanceWidget::class);
        Livewire::component('webkul.timesheet.filament.widgets.weekly-hours-report-widget', WeeklyHoursReportWidget::class);
    }
}
