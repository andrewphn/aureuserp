<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Project\Models\Project;

/**
 * Project Count Widget - Shows total projects ever created (including deleted)
 */
class ProjectCountWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        // Total projects ever (including soft-deleted)
        $totalEver = Project::withTrashed()->count();

        // Active projects (not deleted)
        $activeProjects = Project::count();

        // Deleted/archived projects
        $deletedProjects = Project::onlyTrashed()->count();

        // Projects this year
        $thisYear = Project::whereYear('created_at', now()->year)->count();

        // Projects this month
        $thisMonth = Project::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            Stat::make('Total Projects Ever', number_format($totalEver))
                ->description('All-time project count')
                ->descriptionIcon('heroicon-o-hashtag')
                ->color('primary'),

            Stat::make('Active Projects', number_format($activeProjects))
                ->description($deletedProjects . ' archived/deleted')
                ->descriptionIcon('heroicon-o-folder-open')
                ->color('success'),

            Stat::make('This Year', number_format($thisYear))
                ->description($thisMonth . ' this month')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),
        ];
    }
}
