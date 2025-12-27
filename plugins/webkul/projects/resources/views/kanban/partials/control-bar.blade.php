{{-- Control Bar: View Toggle + Filter Widgets + KPI Row --}}
@php
    if ($currentViewMode === 'projects') {
        // Count and LF for All Projects
        $totalProjects = \Webkul\Project\Models\Project::count();
        $totalLF = \Webkul\Project\Models\Project::sum('estimated_linear_feet') ?? 0;

        // Blocked = has blocked tasks OR no orders OR no customer
        $blockedQuery = \Webkul\Project\Models\Project::where(function($q) {
            $q->whereHas('tasks', fn($t) => $t->where('state', 'blocked'))
              ->orWhereDoesntHave('orders')
              ->orWhereNull('partner_id');
        });
        $blockedCount = (clone $blockedQuery)->count();
        $blockedLF = (clone $blockedQuery)->sum('estimated_linear_feet') ?? 0;

        // Overdue
        $overdueQuery = \Webkul\Project\Models\Project::where('desired_completion_date', '<', now());
        $overdueCount = (clone $overdueQuery)->count();
        $overdueLF = (clone $overdueQuery)->sum('estimated_linear_feet') ?? 0;

        // Due Soon
        $dueSoonQuery = \Webkul\Project\Models\Project::whereBetween('desired_completion_date', [now(), now()->addDays(7)]);
        $dueSoonCount = (clone $dueSoonQuery)->count();
        $dueSoonLF = (clone $dueSoonQuery)->sum('estimated_linear_feet') ?? 0;

        // On Track = not overdue AND not blocked
        $onTrackQuery = \Webkul\Project\Models\Project::where(function($q) {
            $q->whereNull('desired_completion_date')
              ->orWhere('desired_completion_date', '>=', now());
        })
        ->whereDoesntHave('tasks', fn($t) => $t->where('state', 'blocked'))
        ->whereHas('orders')
        ->whereNotNull('partner_id');
        $onTrackCount = (clone $onTrackQuery)->count();
        $onTrackLF = (clone $onTrackQuery)->sum('estimated_linear_feet') ?? 0;
    } else {
        // Task mode stats (no LF for tasks)
        $taskQuery = \Webkul\Project\Models\Task::query()
            ->when($projectFilter ?? null, fn($q) => $q->where('project_id', $projectFilter));

        $totalProjects = (clone $taskQuery)->count();
        $blockedCount = (clone $taskQuery)->where('state', 'blocked')->count();
        $overdueCount = (clone $taskQuery)->where('deadline', '<', now())->where('state', '!=', 'done')->count();
        $dueSoonCount = (clone $taskQuery)->whereBetween('deadline', [now(), now()->addDays(7)])->where('state', '!=', 'done')->count();
        $inProgressCount = (clone $taskQuery)->where('state', 'in_progress')->count();
        $doneCount = (clone $taskQuery)->where('state', 'done')->count();
        $cancelledCount = (clone $taskQuery)->where('state', 'cancelled')->count();

        // No LF for tasks
        $totalLF = $blockedLF = $overdueLF = $dueSoonLF = $onTrackLF = 0;
    }
@endphp

{{-- UNIFIED CONTROL BAR - Single container for all controls --}}
<x-filament::section compact class="!rounded-none !border-x-0 !border-t-0">
    <div class="flex items-center gap-4 flex-wrap">
        {{-- LEFT: View Mode Toggle --}}
        <x-filament::tabs>
            <x-filament::tabs.item
                wire:click="setViewMode('projects')"
                :active="$currentViewMode === 'projects'"
                icon="heroicon-m-folder"
            >
                Projects
            </x-filament::tabs.item>
            <x-filament::tabs.item
                wire:click="setViewMode('tasks')"
                :active="$currentViewMode === 'tasks'"
                icon="heroicon-m-clipboard-document-check"
            >
                Tasks
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- Project Filter (Tasks mode only) --}}
        @if($currentViewMode === 'tasks')
            <x-filament::input.wrapper class="max-w-[200px]">
                <x-filament::input.select wire:model.live="projectFilter" class="text-sm">
                    <option value="">All Projects</option>
                    @foreach($projects ?? [] as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        @endif

        {{-- CENTER: Status Filter Tabs --}}
        <x-filament::tabs>
            <x-filament::tabs.item
                wire:click="toggleWidgetFilter('all')"
                :active="($this->widgetFilter ?? 'all') === 'all'"
            >
                All
                <x-slot name="badge">{{ $totalProjects }}</x-slot>
            </x-filament::tabs.item>

            @if($blockedCount > 0)
                <x-filament::tabs.item
                    wire:click="toggleWidgetFilter('blocked')"
                    :active="($this->widgetFilter ?? null) === 'blocked'"
                >
                    Blocked
                    <x-slot name="badge">
                        <span class="text-purple-600 dark:text-purple-400">{{ $blockedCount }}</span>
                    </x-slot>
                </x-filament::tabs.item>
            @endif

            @if($overdueCount > 0)
                <x-filament::tabs.item
                    wire:click="toggleWidgetFilter('overdue')"
                    :active="($this->widgetFilter ?? null) === 'overdue'"
                >
                    Overdue
                    <x-slot name="badge">
                        <span class="text-danger-600 dark:text-danger-400">{{ $overdueCount }}</span>
                    </x-slot>
                </x-filament::tabs.item>
            @endif

            @if($dueSoonCount > 0)
                <x-filament::tabs.item
                    wire:click="toggleWidgetFilter('due_soon')"
                    :active="($this->widgetFilter ?? null) === 'due_soon'"
                >
                    Due Soon
                    <x-slot name="badge">
                        <span class="text-warning-600 dark:text-warning-400">{{ $dueSoonCount }}</span>
                    </x-slot>
                </x-filament::tabs.item>
            @endif

            @if($currentViewMode === 'projects' && $onTrackCount > 0)
                <x-filament::tabs.item
                    wire:click="toggleWidgetFilter('on_track')"
                    :active="($this->widgetFilter ?? null) === 'on_track'"
                >
                    On Track
                    <x-slot name="badge">
                        <span class="text-success-600 dark:text-success-400">{{ $onTrackCount }}</span>
                    </x-slot>
                </x-filament::tabs.item>
            @endif

            {{-- Task-specific filters --}}
            @if($currentViewMode === 'tasks')
                @if(($inProgressCount ?? 0) > 0)
                    <x-filament::tabs.item
                        wire:click="toggleWidgetFilter('in_progress')"
                        :active="($this->widgetFilter ?? null) === 'in_progress'"
                    >
                        In Progress
                        <x-slot name="badge">
                            <span class="text-primary-600 dark:text-primary-400">{{ $inProgressCount ?? 0 }}</span>
                        </x-slot>
                    </x-filament::tabs.item>
                @endif

                @if(($doneCount ?? 0) > 0)
                    <x-filament::tabs.item
                        wire:click="toggleWidgetFilter('done')"
                        :active="($this->widgetFilter ?? null) === 'done'"
                    >
                        Done
                        <x-slot name="badge">
                            <span class="text-success-600 dark:text-success-400">{{ $doneCount ?? 0 }}</span>
                        </x-slot>
                    </x-filament::tabs.item>
                @endif

                @if(($cancelledCount ?? 0) > 0)
                    <x-filament::tabs.item
                        wire:click="toggleWidgetFilter('cancelled')"
                        :active="($this->widgetFilter ?? null) === 'cancelled'"
                    >
                        Cancelled
                        <x-slot name="badge">
                            <span class="text-gray-500 dark:text-gray-400">{{ $cancelledCount ?? 0 }}</span>
                        </x-slot>
                    </x-filament::tabs.item>
                @endif
            @endif
        </x-filament::tabs>

        {{-- RIGHT: Time Range + Metrics + Toggle (Projects mode) --}}
        @if($currentViewMode === 'projects')
            <div class="ml-auto flex items-center gap-2">
                {{-- Compact Time Range Tabs --}}
                <x-filament::tabs contained>
                    <x-filament::tabs.item
                        wire:click="setKpiTimeRange('this_week')"
                        :active="($kpiTimeRange ?? 'this_week') === 'this_week'"
                    >
                        Wk
                    </x-filament::tabs.item>
                    <x-filament::tabs.item
                        wire:click="setKpiTimeRange('this_month')"
                        :active="($kpiTimeRange ?? 'this_week') === 'this_month'"
                    >
                        Mo
                    </x-filament::tabs.item>
                    <x-filament::tabs.item
                        wire:click="setKpiTimeRange('this_quarter')"
                        :active="($kpiTimeRange ?? 'this_week') === 'this_quarter'"
                    >
                        Qtr
                    </x-filament::tabs.item>
                    <x-filament::tabs.item
                        wire:click="setKpiTimeRange('ytd')"
                        :active="($kpiTimeRange ?? 'this_week') === 'ytd'"
                    >
                        YTD
                    </x-filament::tabs.item>
                </x-filament::tabs>

                {{-- Total LF Badge --}}
                @if($totalLF > 0)
                    <x-filament::badge color="gray" size="lg">
                        {{ number_format($totalLF, 0) }} LF
                    </x-filament::badge>
                @endif

                {{-- Analytics Toggle Icon --}}
                @php $analyticsActive = ($this->layoutSettings['show_kpi_row'] ?? false) || ($this->layoutSettings['show_chart'] ?? false); @endphp
                <x-filament::icon-button
                    wire:click="toggleKpiRow"
                    icon="heroicon-m-chart-bar-square"
                    :color="$analyticsActive ? 'primary' : 'gray'"
                    size="sm"
                    label="Toggle Analytics"
                />
            </div>
        @else
            {{-- Tasks mode: just show active filter clear if needed --}}
            @if(($this->widgetFilter ?? 'all') !== 'all')
                <div class="ml-auto">
                    <x-filament::badge color="primary" class="cursor-pointer" wire:click="toggleWidgetFilter('all')">
                        {{ ucfirst(str_replace('_', ' ', $this->widgetFilter ?? 'all')) }}
                        <x-heroicon-m-x-mark class="w-3 h-3 ml-1" />
                    </x-filament::badge>
                </div>
            @endif
        @endif
    </div>
</x-filament::section>

{{-- EXPANDABLE ANALYTICS ROW (Projects mode only, collapsed by default) --}}
@if($currentViewMode === 'projects' && ($this->layoutSettings['show_kpi_row'] ?? false))
    <x-filament::section compact class="!rounded-none !border-x-0 !border-t-0 !mt-0 bg-gray-50/50 dark:bg-gray-900/50">
        <div class="flex items-center gap-3 flex-wrap">
            {{-- KPI Stats as Compact Badges --}}
            <x-filament::badge color="info" size="lg" icon="heroicon-m-queue-list">
                {{ number_format($kpiStats['lf_this_period'] ?? 0, 0) }} LF {{ $kpiStats['time_range_label'] ?? 'This Week' }}
            </x-filament::badge>

            <x-filament::badge color="warning" size="lg" icon="heroicon-m-cog-6-tooth">
                {{ number_format($kpiStats['lf_in_production'] ?? 0, 0) }} LF Production
            </x-filament::badge>

            <x-filament::badge color="success" size="lg" icon="heroicon-m-check-circle">
                {{ $kpiStats['on_target'] ?? 0 }} On Target
            </x-filament::badge>

            <x-filament::badge color="danger" size="lg" icon="heroicon-m-exclamation-triangle">
                {{ $kpiStats['off_target'] ?? 0 }} Off Target
            </x-filament::badge>

            <x-filament::badge color="gray" size="lg" icon="heroicon-m-flag">
                {{ $kpiStats['completed_this_period'] ?? 0 }} Completed
            </x-filament::badge>

            {{-- Mini Chart (right side) --}}
            @if($this->layoutSettings['show_chart'] ?? false)
                <div class="ml-auto w-48">
                    @livewire(\Webkul\Project\Filament\Widgets\ProjectYearlyStatsChart::class, ['filter' => $kpiTimeRange ?? 'this_month'], key('yearly-chart-' . ($kpiTimeRange ?? 'this_month')))
                </div>
            @else
                {{-- Chart toggle button --}}
                <div class="ml-auto">
                    <x-filament::icon-button
                        wire:click="toggleChartVisibility"
                        icon="heroicon-m-chart-bar"
                        color="gray"
                        size="sm"
                        label="Show Chart"
                    />
                </div>
            @endif
        </div>
    </x-filament::section>
@endif
