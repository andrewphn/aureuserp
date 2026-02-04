<?php

namespace Webkul\Project\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class GanttChart extends Page
{

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-chart-bar-square';

    protected string $view = 'webkul-project::filament.pages.gantt-chart';

    protected static ?string $slug = 'project/gantt';

    protected static ?int $navigationSort = 2;

    // Hide from main navigation - accessible via Project views
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return 'Gantt Chart';
    }

    public static function getNavigationGroup(): string
    {
        return __('webkul-project::filament/resources/project.navigation.group');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Project Timeline - Gantt Chart';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Project Timeline';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'View and manage project timelines with drag-and-drop scheduling';
    }
}
