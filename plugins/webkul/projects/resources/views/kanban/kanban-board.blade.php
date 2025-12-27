@php
    // View mode from controller
    $currentViewMode = $viewMode ?? 'projects';

    // Layout settings
    $compactFilters = $layoutSettings['compact_filters'] ?? true;
    $showKpiRow = $layoutSettings['show_kpi_row'] ?? false;
    $showChart = $layoutSettings['show_chart'] ?? false;

    // Exclude "To Do" from workflow stages - leads are the inbox now (only for projects)
    $boardStatuses = $currentViewMode === 'projects'
        ? $statuses->reject(fn($s) => $s['title'] === 'To Do')
        : $statuses;

    // Leads are now the inbox (only shown in projects mode)
    $inboxOpen = $this->leadsInboxOpen ?? true;
    $inboxCount = $leadsCount ?? 0;
    $newInboxCount = $newLeadsCount ?? 0;
@endphp

<x-filament-panels::page class="!p-0">
    {{-- Combined Filter Bar: View Toggle + Filter Widgets --}}
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

            // No LF for tasks
            $totalLF = $blockedLF = $overdueLF = $dueSoonLF = $onTrackLF = 0;
        }
    @endphp
    <div
        x-data="{ isDark: document.documentElement.classList.contains('dark') }"
        x-init="new MutationObserver(() => isDark = document.documentElement.classList.contains('dark')).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })"
        class="px-3 border-b {{ $compactFilters ? 'py-2' : 'py-3' }}"
        :style="isDark ? 'background-color: #111827; border-color: #374151;' : 'background-color: #f9fafb; border-color: #e5e7eb;'"
    >
        <div class="flex items-center gap-3">
            {{-- View Mode Toggle (Projects/Tasks) --}}
            <div class="flex items-center rounded-lg overflow-hidden border" :style="isDark ? 'border-color: #374151;' : 'border-color: #e5e7eb;'">
                <button
                    wire:click="setViewMode('projects')"
                    class="flex items-center gap-1.5 {{ $compactFilters ? 'px-3 py-1.5 text-xs' : 'px-4 py-2.5 text-sm' }} font-medium transition-all duration-150"
                    :style="isDark
                        ? '{{ $currentViewMode === 'projects' ? 'background-color: #D4A574; color: #111827;' : 'background-color: #1f2937; color: #9ca3af;' }}'
                        : '{{ $currentViewMode === 'projects' ? 'background-color: #D4A574; color: #111827;' : 'background-color: #fff; color: #6b7280;' }}'"
                >
                    <x-heroicon-m-folder class="{{ $compactFilters ? 'w-3.5 h-3.5' : 'w-4 h-4' }}" />
                    Projects
                </button>
                <button
                    wire:click="setViewMode('tasks')"
                    class="flex items-center gap-1.5 {{ $compactFilters ? 'px-3 py-1.5 text-xs' : 'px-4 py-2.5 text-sm' }} font-medium transition-all duration-150"
                    :style="isDark
                        ? '{{ $currentViewMode === 'tasks' ? 'background-color: #D4A574; color: #111827;' : 'background-color: #1f2937; color: #9ca3af;' }}'
                        : '{{ $currentViewMode === 'tasks' ? 'background-color: #D4A574; color: #111827;' : 'background-color: #fff; color: #6b7280;' }}'"
                >
                    <x-heroicon-m-clipboard-document-check class="{{ $compactFilters ? 'w-3.5 h-3.5' : 'w-4 h-4' }}" />
                    Tasks
                </button>
            </div>

            {{-- Project Filter (Tasks mode only) --}}
            @if($currentViewMode === 'tasks')
                <select
                    wire:model.live="projectFilter"
                    class="{{ $compactFilters ? 'text-xs py-1.5' : 'text-sm py-2' }} rounded-lg px-3"
                    :style="isDark ? 'background-color: #1f2937; border-color: #374151; color: #fff;' : 'background-color: #fff; border-color: #e5e7eb; color: #111827;'"
                >
                    <option value="">All Projects</option>
                    @foreach($projects ?? [] as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            @endif

            {{-- Divider --}}
            <div class="w-px {{ $compactFilters ? 'h-6' : 'h-8' }}" :style="isDark ? 'background-color: #374151;' : 'background-color: #e5e7eb;'"></div>

            @if($compactFilters)
                {{-- COMPACT FILTER TABS --}}
                <div class="flex items-center gap-1">
                    {{-- All --}}
                    <button
                        wire:click="toggleWidgetFilter('all')"
                        class="px-2.5 py-1 rounded text-xs font-medium transition-all"
                        :style="isDark
                            ? '{{ ($this->widgetFilter ?? 'all') === 'all' ? 'background-color: rgba(249, 115, 22, 0.3); color: #fb923c;' : 'color: #9ca3af;' }}'
                            : '{{ ($this->widgetFilter ?? 'all') === 'all' ? 'background-color: #fff7ed; color: #ea580c;' : 'color: #6b7280;' }}'"
                    >
                        All ({{ $totalProjects }})
                    </button>

                    {{-- Blocked --}}
                    @if($blockedCount > 0)
                    <button
                        wire:click="toggleWidgetFilter('blocked')"
                        class="px-2.5 py-1 rounded text-xs font-medium transition-all"
                        :style="isDark
                            ? '{{ ($this->widgetFilter ?? null) === 'blocked' ? 'background-color: rgba(124, 58, 237, 0.3); color: #a78bfa;' : 'color: #a78bfa;' }}'
                            : '{{ ($this->widgetFilter ?? null) === 'blocked' ? 'background-color: #f5f3ff; color: #7c3aed;' : 'color: #7c3aed;' }}'"
                    >
                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-1" style="background-color: #7c3aed;"></span>
                        Blocked ({{ $blockedCount }})
                    </button>
                    @endif

                    {{-- Overdue --}}
                    @if($overdueCount > 0)
                    <button
                        wire:click="toggleWidgetFilter('overdue')"
                        class="px-2.5 py-1 rounded text-xs font-medium transition-all"
                        :style="isDark
                            ? '{{ ($this->widgetFilter ?? null) === 'overdue' ? 'background-color: rgba(220, 38, 38, 0.3); color: #f87171;' : 'color: #f87171;' }}'
                            : '{{ ($this->widgetFilter ?? null) === 'overdue' ? 'background-color: #fef2f2; color: #dc2626;' : 'color: #dc2626;' }}'"
                    >
                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-1" style="background-color: #dc2626;"></span>
                        Overdue ({{ $overdueCount }})
                    </button>
                    @endif

                    {{-- Due Soon --}}
                    @if($dueSoonCount > 0)
                    <button
                        wire:click="toggleWidgetFilter('due_soon')"
                        class="px-2.5 py-1 rounded text-xs font-medium transition-all"
                        :style="isDark
                            ? '{{ ($this->widgetFilter ?? null) === 'due_soon' ? 'background-color: rgba(234, 88, 12, 0.3); color: #fb923c;' : 'color: #fb923c;' }}'
                            : '{{ ($this->widgetFilter ?? null) === 'due_soon' ? 'background-color: #fff7ed; color: #ea580c;' : 'color: #ea580c;' }}'"
                    >
                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-1" style="background-color: #ea580c;"></span>
                        Due Soon ({{ $dueSoonCount }})
                    </button>
                    @endif

                    {{-- On Track / In Progress --}}
                    @if($currentViewMode === 'projects')
                        @if($onTrackCount > 0)
                        <button
                            wire:click="toggleWidgetFilter('on_track')"
                            class="px-2.5 py-1 rounded text-xs font-medium transition-all"
                            :style="isDark
                                ? '{{ ($this->widgetFilter ?? null) === 'on_track' ? 'background-color: rgba(22, 163, 74, 0.3); color: #4ade80;' : 'color: #4ade80;' }}'
                                : '{{ ($this->widgetFilter ?? null) === 'on_track' ? 'background-color: #f0fdf4; color: #16a34a;' : 'color: #16a34a;' }}'"
                        >
                            <span class="inline-block w-1.5 h-1.5 rounded-full mr-1" style="background-color: #16a34a;"></span>
                            On Track ({{ $onTrackCount }})
                        </button>
                        @endif
                    @else
                        @if(($inProgressCount ?? 0) > 0)
                        <button
                            wire:click="toggleWidgetFilter('in_progress')"
                            class="px-2.5 py-1 rounded text-xs font-medium transition-all"
                            :style="isDark
                                ? '{{ ($this->widgetFilter ?? null) === 'in_progress' ? 'background-color: rgba(37, 99, 235, 0.3); color: #60a5fa;' : 'color: #60a5fa;' }}'
                                : '{{ ($this->widgetFilter ?? null) === 'in_progress' ? 'background-color: #eff6ff; color: #2563eb;' : 'color: #2563eb;' }}'"
                        >
                            <span class="inline-block w-1.5 h-1.5 rounded-full mr-1" style="background-color: #2563eb;"></span>
                            In Progress ({{ $inProgressCount ?? 0 }})
                        </button>
                        @endif
                    @endif
                </div>

                {{-- Compact LF Summary (Projects only) --}}
                @if($currentViewMode === 'projects' && $totalLF > 0)
                    <div class="flex items-center gap-1.5 text-[10px]" style="color: #9ca3af;">
                        <span class="font-semibold">{{ number_format($totalLF, 0) }} LF</span>
                        @if(($kpiStats['lf_in_production'] ?? 0) > 0)
                            <span>•</span>
                            <span style="color: #ea580c;">{{ number_format($kpiStats['lf_in_production'], 0) }} LF in prod</span>
                        @endif
                    </div>
                @endif
            @else
                {{-- FULL FILTER WIDGETS --}}
                {{-- All Items Widget --}}
                <button
                    wire:click="toggleWidgetFilter('all')"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer"
                    :style="isDark
                        ? '{{ ($this->widgetFilter ?? 'all') === 'all' ? 'border-color: #f97316; background-color: rgba(249, 115, 22, 0.2);' : 'border-color: #374151; background-color: #1f2937;' }}'
                        : '{{ ($this->widgetFilter ?? 'all') === 'all' ? 'border-color: #f97316; background-color: #fff7ed;' : 'border-color: #e5e7eb; background-color: #fff;' }}'"
                >
                    <div class="text-left">
                        <div class="text-2xl font-bold" :style="isDark ? 'color: #fff;' : 'color: #111827;'">{{ $totalProjects }}</div>
                        <div class="text-xs" style="color: #6b7280;">All {{ $currentViewMode === 'projects' ? 'Projects' : 'Tasks' }}</div>
                        @if($currentViewMode === 'projects' && $totalLF > 0)
                            <div class="text-[10px] font-medium" style="color: #9ca3af;">{{ number_format($totalLF, 0) }} LF</div>
                        @endif
                    </div>
                </button>

                {{-- Blocked Widget --}}
                <button
                    wire:click="toggleWidgetFilter('blocked')"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer"
                    :style="isDark
                        ? '{{ ($this->widgetFilter ?? null) === 'blocked' ? 'border-color: #7c3aed; background-color: rgba(124, 58, 237, 0.2);' : 'border-color: #374151; background-color: #1f2937;' }}'
                        : '{{ ($this->widgetFilter ?? null) === 'blocked' ? 'border-color: #7c3aed; background-color: #f5f3ff;' : 'border-color: #e5e7eb; background-color: #fff;' }}'"
                >
                    <div class="w-3 h-8 rounded-sm" style="background-color: #7c3aed;"></div>
                    <div class="text-left">
                        <div class="text-2xl font-bold" style="color: #7c3aed;">{{ $blockedCount }}</div>
                        <div class="text-xs" style="color: #6b7280;">Blocked</div>
                        @if($currentViewMode === 'projects' && $blockedLF > 0)
                            <div class="text-[10px] font-medium" style="color: #a78bfa;">{{ number_format($blockedLF, 0) }} LF</div>
                        @endif
                    </div>
                </button>

                {{-- Overdue Widget --}}
                <button
                    wire:click="toggleWidgetFilter('overdue')"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer"
                    :style="isDark
                        ? '{{ ($this->widgetFilter ?? null) === 'overdue' ? 'border-color: #dc2626; background-color: rgba(220, 38, 38, 0.2);' : 'border-color: #374151; background-color: #1f2937;' }}'
                        : '{{ ($this->widgetFilter ?? null) === 'overdue' ? 'border-color: #dc2626; background-color: #fef2f2;' : 'border-color: #e5e7eb; background-color: #fff;' }}'"
                >
                    <div class="w-3 h-8 rounded-sm" style="background-color: #dc2626;"></div>
                    <div class="text-left">
                        <div class="text-2xl font-bold" style="color: #dc2626;">{{ $overdueCount }}</div>
                        <div class="text-xs" style="color: #6b7280;">Overdue</div>
                        @if($currentViewMode === 'projects' && $overdueLF > 0)
                            <div class="text-[10px] font-medium" style="color: #f87171;">{{ number_format($overdueLF, 0) }} LF</div>
                        @endif
                    </div>
                </button>

                {{-- Due Soon Widget --}}
                <button
                    wire:click="toggleWidgetFilter('due_soon')"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer"
                    :style="isDark
                        ? '{{ ($this->widgetFilter ?? null) === 'due_soon' ? 'border-color: #ea580c; background-color: rgba(234, 88, 12, 0.2);' : 'border-color: #374151; background-color: #1f2937;' }}'
                        : '{{ ($this->widgetFilter ?? null) === 'due_soon' ? 'border-color: #ea580c; background-color: #fff7ed;' : 'border-color: #e5e7eb; background-color: #fff;' }}'"
                >
                    <div class="w-3 h-8 rounded-sm" style="background-color: #ea580c;"></div>
                    <div class="text-left">
                        <div class="text-2xl font-bold" style="color: #ea580c;">{{ $dueSoonCount }}</div>
                        <div class="text-xs" style="color: #6b7280;">Due Soon</div>
                        @if($currentViewMode === 'projects' && $dueSoonLF > 0)
                            <div class="text-[10px] font-medium" style="color: #fb923c;">{{ number_format($dueSoonLF, 0) }} LF</div>
                        @endif
                    </div>
                </button>

                {{-- On Track / In Progress Widget --}}
                @if($currentViewMode === 'projects')
                    <button
                        wire:click="toggleWidgetFilter('on_track')"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer"
                        :style="isDark
                            ? '{{ ($this->widgetFilter ?? null) === 'on_track' ? 'border-color: #16a34a; background-color: rgba(22, 163, 74, 0.2);' : 'border-color: #374151; background-color: #1f2937;' }}'
                            : '{{ ($this->widgetFilter ?? null) === 'on_track' ? 'border-color: #16a34a; background-color: #f0fdf4;' : 'border-color: #e5e7eb; background-color: #fff;' }}'"
                    >
                        <div class="w-3 h-8 rounded-sm" style="background-color: #16a34a;"></div>
                        <div class="text-left">
                            <div class="text-2xl font-bold" style="color: #16a34a;">{{ $onTrackCount }}</div>
                            <div class="text-xs" style="color: #6b7280;">On Track</div>
                            @if($onTrackLF > 0)
                                <div class="text-[10px] font-medium" style="color: #4ade80;">{{ number_format($onTrackLF, 0) }} LF</div>
                            @endif
                        </div>
                    </button>
                @else
                    <button
                        wire:click="toggleWidgetFilter('in_progress')"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer"
                        :style="isDark
                            ? '{{ ($this->widgetFilter ?? null) === 'in_progress' ? 'border-color: #2563eb; background-color: rgba(37, 99, 235, 0.2);' : 'border-color: #374151; background-color: #1f2937;' }}'
                            : '{{ ($this->widgetFilter ?? null) === 'in_progress' ? 'border-color: #2563eb; background-color: #eff6ff;' : 'border-color: #e5e7eb; background-color: #fff;' }}'"
                    >
                        <div class="w-3 h-8 rounded-sm" style="background-color: #2563eb;"></div>
                        <div class="text-left">
                            <div class="text-2xl font-bold" style="color: #2563eb;">{{ $inProgressCount ?? 0 }}</div>
                            <div class="text-xs" style="color: #6b7280;">In Progress</div>
                        </div>
                    </button>
                @endif
            @endif

            {{-- Active Filter Indicator --}}
            @if(($this->widgetFilter ?? 'all') !== 'all')
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                        :style="isDark ? 'background-color: #374151; color: #d1d5db;' : 'background-color: #f3f4f6; color: #374151;'"
                    >
                        {{ ucfirst(str_replace('_', ' ', $this->widgetFilter ?? 'all')) }}
                        <button wire:click="toggleWidgetFilter('all')" class="ml-1 hover:text-red-500">
                            <x-heroicon-m-x-mark class="w-3 h-3" />
                        </button>
                    </span>
                </div>
            @endif

            {{-- Right Side Controls --}}
            <div class="ml-auto flex items-center gap-2">
                {{-- KPI Row Toggle (Projects mode) --}}
                @if($currentViewMode === 'projects')
                    <button
                        wire:click="toggleKpiRow"
                        class="flex items-center gap-1 px-2 py-1 rounded text-xs font-medium transition-all"
                        :style="isDark
                            ? '{{ $showKpiRow ? 'background-color: rgba(212, 165, 116, 0.2); color: #D4A574;' : 'color: #9ca3af;' }}'
                            : '{{ $showKpiRow ? 'background-color: rgba(212, 165, 116, 0.1); color: #92400e;' : 'color: #6b7280;' }}'"
                        title="Toggle Analytics"
                    >
                        <x-heroicon-m-chart-pie class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Analytics</span>
                        <x-heroicon-m-chevron-down class="w-3 h-3 transition-transform {{ $showKpiRow ? 'rotate-180' : '' }}" />
                    </button>
                @endif

                {{-- Chart Toggle (Projects mode only) --}}
                @if($currentViewMode === 'projects')
                    <button
                        wire:click="toggleChartVisibility"
                        class="flex items-center gap-1 px-2 py-1 rounded text-xs font-medium transition-all"
                        :style="isDark
                            ? '{{ $showChart ? 'background-color: rgba(212, 165, 116, 0.2); color: #D4A574;' : 'color: #9ca3af;' }}'
                            : '{{ $showChart ? 'background-color: rgba(212, 165, 116, 0.1); color: #92400e;' : 'color: #6b7280;' }}'"
                        title="Toggle Chart"
                    >
                        <x-heroicon-m-chart-bar class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">{{ $chartYear ?? now()->year }}</span>
                        <x-heroicon-m-chevron-down class="w-3 h-3 transition-transform {{ $showChart ? 'rotate-180' : '' }}" />
                    </button>
                @endif
            </div>
        </div>

        {{-- Business Owner KPI Row (Projects mode only, collapsible) --}}
        @if($currentViewMode === 'projects' && $showKpiRow)
            <div class="flex items-center gap-3 mt-3 pt-3 border-t" :style="isDark ? 'border-color: #374151;' : 'border-color: #e5e7eb;'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
            >
                {{-- Time Range Selector --}}
                <div class="flex items-center rounded-lg overflow-hidden border" :style="isDark ? 'border-color: #374151;' : 'border-color: #e5e7eb;'">
                    @foreach(['this_week' => 'Week', 'this_month' => 'Month', 'this_quarter' => 'Quarter', 'ytd' => 'YTD'] as $range => $label)
                        <button
                            wire:click="setKpiTimeRange('{{ $range }}')"
                            class="px-3 py-1.5 text-xs font-medium transition-all duration-150"
                            :style="isDark
                                ? '{{ ($kpiTimeRange ?? 'this_week') === $range ? 'background-color: #D4A574; color: #111827;' : 'background-color: #1f2937; color: #9ca3af;' }}'
                                : '{{ ($kpiTimeRange ?? 'this_week') === $range ? 'background-color: #D4A574; color: #111827;' : 'background-color: #fff; color: #6b7280;' }}'"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Divider --}}
                <div class="w-px h-8" :style="isDark ? 'background-color: #374151;' : 'background-color: #e5e7eb;'"></div>

                {{-- LF This Period Widget --}}
                <div
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border transition-all duration-150"
                    :style="isDark ? 'border-color: #374151; background-color: #1f2937;' : 'border-color: #e5e7eb; background-color: #fff;'"
                >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: rgba(37, 99, 235, 0.2);">
                        <x-heroicon-s-queue-list class="w-4 h-4" style="color: #2563eb;" />
                    </div>
                    <div class="text-left">
                        <div class="text-lg font-bold" style="color: #2563eb;">{{ number_format($kpiStats['lf_this_period'] ?? 0, 0) }} LF</div>
                        <div class="text-[10px] uppercase tracking-wider" style="color: #6b7280;">New {{ $kpiStats['time_range_label'] ?? 'This Week' }}</div>
                    </div>
                </div>

                {{-- In Production Widget --}}
                <div
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border transition-all duration-150"
                    :style="isDark ? 'border-color: #374151; background-color: #1f2937;' : 'border-color: #e5e7eb; background-color: #fff;'"
                >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: rgba(234, 88, 12, 0.2);">
                        <x-heroicon-s-cog-6-tooth class="w-4 h-4" style="color: #ea580c;" />
                    </div>
                    <div class="text-left">
                        <div class="text-lg font-bold" style="color: #ea580c;">{{ number_format($kpiStats['lf_in_production'] ?? 0, 0) }} LF</div>
                        <div class="text-[10px] uppercase tracking-wider" style="color: #6b7280;">In Production ({{ $kpiStats['projects_in_production'] ?? 0 }})</div>
                    </div>
                </div>

                {{-- On Target Widget --}}
                <div
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border transition-all duration-150"
                    :style="isDark ? 'border-color: #374151; background-color: #1f2937;' : 'border-color: #e5e7eb; background-color: #fff;'"
                >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: rgba(22, 163, 74, 0.2);">
                        <x-heroicon-s-check-circle class="w-4 h-4" style="color: #16a34a;" />
                    </div>
                    <div class="text-left">
                        <div class="text-lg font-bold" style="color: #16a34a;">{{ $kpiStats['on_target'] ?? 0 }}</div>
                        <div class="text-[10px] uppercase tracking-wider" style="color: #6b7280;">On Target</div>
                    </div>
                </div>

                {{-- Off Target Widget --}}
                <div
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border transition-all duration-150"
                    :style="isDark ? 'border-color: #374151; background-color: #1f2937;' : 'border-color: #e5e7eb; background-color: #fff;'"
                >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: rgba(220, 38, 38, 0.2);">
                        <x-heroicon-s-exclamation-triangle class="w-4 h-4" style="color: #dc2626;" />
                    </div>
                    <div class="text-left">
                        <div class="text-lg font-bold" style="color: #dc2626;">{{ $kpiStats['off_target'] ?? 0 }}</div>
                        <div class="text-[10px] uppercase tracking-wider" style="color: #6b7280;">Off Target</div>
                    </div>
                </div>

                {{-- Completed This Period Widget --}}
                <div
                    class="flex items-center gap-3 px-4 py-2 rounded-lg border transition-all duration-150"
                    :style="isDark ? 'border-color: #374151; background-color: #1f2937;' : 'border-color: #e5e7eb; background-color: #fff;'"
                >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: rgba(124, 58, 237, 0.2);">
                        <x-heroicon-s-trophy class="w-4 h-4" style="color: #7c3aed;" />
                    </div>
                    <div class="text-left">
                        <div class="text-lg font-bold" style="color: #7c3aed;">{{ $kpiStats['completed_this_period'] ?? 0 }}</div>
                        <div class="text-[10px] uppercase tracking-wider" style="color: #6b7280;">Completed ({{ number_format($kpiStats['lf_completed_this_period'] ?? 0, 0) }} LF)</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Task Status Filters (Tasks mode only) --}}
        @if($currentViewMode === 'tasks')
            @php
                $taskQuery = \Webkul\Project\Models\Task::query()
                    ->when($projectFilter ?? null, fn($q) => $q->where('project_id', $projectFilter));
                $doneCount = (clone $taskQuery)->where('state', 'done')->count();
                $cancelledCount = (clone $taskQuery)->where('state', 'cancelled')->count();
            @endphp
            <div class="flex items-center gap-2 mt-2 pt-2 border-t" :style="isDark ? 'border-color: #374151;' : 'border-color: #e5e7eb;'">
                <span class="text-xs font-medium" style="color: #6b7280;">Status:</span>

                {{-- In Progress --}}
                <button
                    wire:click="toggleWidgetFilter('in_progress')"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 border"
                    :style="isDark
                        ? '{{ ($this->widgetFilter ?? null) === 'in_progress' ? 'border-color: #2563eb; background-color: rgba(37, 99, 235, 0.2); color: #60a5fa;' : 'border-color: #374151; background-color: #1f2937; color: #9ca3af;' }}'
                        : '{{ ($this->widgetFilter ?? null) === 'in_progress' ? 'border-color: #2563eb; background-color: #eff6ff; color: #2563eb;' : 'border-color: #e5e7eb; background-color: #fff; color: #6b7280;' }}'"
                >
                    <span class="w-2 h-2 rounded-full" style="background-color: #2563eb;"></span>
                    In Progress ({{ $inProgressCount ?? 0 }})
                </button>

                {{-- Done --}}
                <button
                    wire:click="toggleWidgetFilter('done')"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 border"
                    :style="isDark
                        ? '{{ ($this->widgetFilter ?? null) === 'done' ? 'border-color: #16a34a; background-color: rgba(22, 163, 74, 0.2); color: #4ade80;' : 'border-color: #374151; background-color: #1f2937; color: #9ca3af;' }}'
                        : '{{ ($this->widgetFilter ?? null) === 'done' ? 'border-color: #16a34a; background-color: #f0fdf4; color: #16a34a;' : 'border-color: #e5e7eb; background-color: #fff; color: #6b7280;' }}'"
                >
                    <span class="w-2 h-2 rounded-full" style="background-color: #16a34a;"></span>
                    Done ({{ $doneCount ?? 0 }})
                </button>

                {{-- Cancelled --}}
                <button
                    wire:click="toggleWidgetFilter('cancelled')"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 border"
                    :style="isDark
                        ? '{{ ($this->widgetFilter ?? null) === 'cancelled' ? 'border-color: #6b7280; background-color: rgba(107, 114, 128, 0.2); color: #9ca3af;' : 'border-color: #374151; background-color: #1f2937; color: #9ca3af;' }}'
                        : '{{ ($this->widgetFilter ?? null) === 'cancelled' ? 'border-color: #6b7280; background-color: #f9fafb; color: #6b7280;' : 'border-color: #e5e7eb; background-color: #fff; color: #6b7280;' }}'"
                >
                    <span class="w-2 h-2 rounded-full" style="background-color: #6b7280;"></span>
                    Cancelled ({{ $cancelledCount ?? 0 }})
                </button>
            </div>
        @endif

        {{-- Chart Container (Collapsible - uses showChart from layout settings) --}}
        @if($currentViewMode === 'projects' && $showChart)
            <div
                class="mt-3 pt-3 border-t"
                :style="isDark ? 'border-color: #374151;' : 'border-color: #e5e7eb;'"
            >
                {{-- Chart Legend Row with Year Selector --}}
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm" style="background-color: #16a34a;"></span>
                            <span style="color: #6b7280;">Completed ({{ $yearlyStats['totals']['completed'] ?? 0 }})</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm" style="background-color: #2563eb;"></span>
                            <span style="color: #6b7280;">In Progress ({{ $yearlyStats['totals']['in_progress'] ?? 0 }})</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm" style="background-color: #6b7280;"></span>
                            <span style="color: #6b7280;">Cancelled ({{ $yearlyStats['totals']['cancelled'] ?? 0 }})</span>
                        </span>
                    </div>

                    {{-- Year Selector --}}
                    <div class="flex items-center gap-1">
                        @foreach($availableYears ?? [] as $year => $label)
                            <button
                                wire:click="setChartYear({{ $year }})"
                                class="px-2 py-0.5 text-xs rounded transition-colors"
                                :style="isDark
                                    ? '{{ ($chartYear ?? now()->year) == $year ? 'background-color: #D4A574; color: #111827;' : 'background-color: #374151; color: #9ca3af;' }}'
                                    : '{{ ($chartYear ?? now()->year) == $year ? 'background-color: #D4A574; color: #111827;' : 'background-color: #e5e7eb; color: #6b7280;' }}'"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Chart --}}
                <div class="h-40">
                    <canvas
                        id="yearly-stats-chart"
                        wire:ignore
                        x-init="
                            const ctx = document.getElementById('yearly-stats-chart');
                            if (ctx) {
                                const isDark = document.documentElement.classList.contains('dark');
                                const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
                                const textColor = isDark ? '#9ca3af' : '#6b7280';

                                if (window.yearlyStatsChart) {
                                    window.yearlyStatsChart.destroy();
                                }

                                window.yearlyStatsChart = new Chart(ctx.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($yearlyStats['labels'] ?? []),
                                        datasets: @js($yearlyStats['datasets'] ?? [])
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: {
                                                backgroundColor: isDark ? '#374151' : '#ffffff',
                                                titleColor: isDark ? '#f9fafb' : '#111827',
                                                bodyColor: isDark ? '#d1d5db' : '#4b5563',
                                                borderColor: isDark ? '#4b5563' : '#e5e7eb',
                                                borderWidth: 1
                                            }
                                        },
                                        scales: {
                                            x: {
                                                stacked: true,
                                                grid: { display: false },
                                                ticks: { color: textColor, font: { size: 10 } }
                                            },
                                            y: {
                                                stacked: true,
                                                beginAtZero: true,
                                                grid: { color: gridColor },
                                                ticks: {
                                                    color: textColor,
                                                    font: { size: 10 },
                                                    stepSize: 1,
                                                    precision: 0
                                                }
                                            }
                                        }
                                    }
                                });

                                Livewire.on('chartDataUpdated', (data) => {
                                    if (window.yearlyStatsChart && data[0]) {
                                        window.yearlyStatsChart.data.labels = data[0].labels;
                                        window.yearlyStatsChart.data.datasets = data[0].datasets;
                                        window.yearlyStatsChart.update();
                                    }
                                });
                            }
                        "
                    ></canvas>
                </div>
            </div>
        @endif
    </div>

    {{-- Main Kanban Board - Full Height --}}
    <div
        x-data="{
            inboxOpen: {{ $inboxOpen ? 'true' : 'false' }},
            hasNewItems: {{ $newInboxCount > 0 ? 'true' : 'false' }},
            toggleInbox() {
                this.inboxOpen = !this.inboxOpen;
                $wire.leadsInboxOpen = this.inboxOpen;
            }
        }"
        class="h-[calc(100vh-180px)]"
    >
        {{-- Single Flex Container for ALL columns (Inbox + Workflow Stages) --}}
        <div
            wire:ignore.self
            class="flex gap-3 h-full min-h-0 overflow-x-auto overflow-y-hidden px-3 py-2"
            style="scrollbar-width: thin;"
        >
            {{-- INBOX COLUMN (Leads / New Inquiries) - Only show in projects mode --}}
            @if($currentViewMode === 'projects')
            <div class="flex-shrink-0 h-full min-h-0">
                {{-- Collapsed State - Icon with count --}}
                <div
                    x-show="!inboxOpen"
                    @click="toggleInbox()"
                    x-data="{ isDark: document.documentElement.classList.contains('dark') }"
                    x-init="new MutationObserver(() => isDark = document.documentElement.classList.contains('dark')).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })"
                    class="w-12 h-full cursor-pointer flex flex-col items-center pt-3 gap-1 transition-all duration-150 rounded-lg border-2 hover:opacity-80"
                    :style="isDark ? 'background-color: #1f2937; border-color: #4b5563;' : 'background-color: #fff; border-color: #111827;'"
                    title="Open Inbox ({{ $inboxCount }} inquiries)"
                >
                    {{-- Count badge at top --}}
                    <span
                        class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold"
                        style="background-color: {{ $newInboxCount > 0 ? '#ef4444' : '#6b7280' }}; color: #fff;"
                    >
                        {{ $inboxCount }}
                    </span>
                    {{-- Inbox icon --}}
                    <div :style="isDark ? 'color: #9ca3af;' : 'color: #6b7280;'">
                        <x-heroicon-o-inbox class="w-5 h-5" />
                    </div>
                </div>

                {{-- Expanded Inbox Panel --}}
                <div
                    x-show="inboxOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="flex flex-col h-full min-h-0"
                    style="width: 280px; min-width: 280px; max-width: 280px;"
                >
                    @php
                        $inboxLinearFeet = collect($leads ?? [])->sum('estimated_linear_feet');
                    @endphp
                    {{-- Header - Black outlined, matches column header height --}}
                    <div
                        x-data="{ isDark: document.documentElement.classList.contains('dark') }"
                        x-init="new MutationObserver(() => isDark = document.documentElement.classList.contains('dark')).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })"
                        class="flex items-center justify-between px-4 py-2 rounded-t-lg transition-all duration-150 border-2 border-b-0 min-h-[52px]"
                        :style="isDark ? 'background-color: #1f2937; border-color: #4b5563;' : 'background-color: #fff; border-color: #111827;'"
                    >
                        <div class="flex flex-col">
                            <h3 class="font-medium text-sm flex items-center gap-1.5" :style="isDark ? 'color: #fff;' : 'color: #111827;'">
                                <span>Inbox</span>
                                <span :style="isDark ? 'color: #6b7280;' : 'color: #9ca3af;'">/</span>
                                <span :style="isDark ? 'color: #d1d5db;' : 'color: #374151;'">{{ $inboxCount }}</span>
                            </h3>
                            <span class="text-xs">
                                @if($inboxLinearFeet > 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                        {{ number_format($inboxLinearFeet, 1) }} LF
                                    </span>
                                @else
                                    &nbsp;
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            {{-- Add Lead Button --}}
                            <a
                                href="{{ route('filament.admin.resources.leads.create') }}"
                                class="rounded p-1 transition-all duration-100"
                                :style="isDark ? 'color: #9ca3af;' : 'color: #6b7280;'"
                                title="Add new lead"
                            >
                                <x-heroicon-m-plus class="w-4 h-4" />
                            </a>
                            {{-- Collapse Button --}}
                            <button
                                @click="toggleInbox()"
                                class="rounded p-1 transition-all duration-100"
                                :style="isDark ? 'color: #9ca3af;' : 'color: #6b7280;'"
                                title="Collapse inbox"
                            >
                                <x-heroicon-m-chevron-double-left class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Lead Cards - Black outlined container --}}
                    <div
                        x-data="{ isDark: document.documentElement.classList.contains('dark') }"
                        x-init="new MutationObserver(() => isDark = document.documentElement.classList.contains('dark')).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })"
                        class="flex-1 flex flex-col gap-2 p-2 overflow-y-auto min-h-0 border-2 border-t-0"
                        :style="isDark ? 'background-color: rgba(31, 41, 55, 0.5); border-color: #4b5563; scrollbar-width: thin; scrollbar-color: transparent transparent;' : 'background-color: #fff; border-color: #111827; scrollbar-width: thin; scrollbar-color: transparent transparent;'"
                        onmouseenter="this.style.scrollbarColor = 'rgba(156,163,175,0.3) transparent'"
                        onmouseleave="this.style.scrollbarColor = 'transparent transparent'"
                    >
                        @forelse($leads ?? [] as $lead)
                            <x-filament::section
                                compact
                                class="cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all"
                                x-data
                                x-on:click="$wire.openLeadDetails({{ $lead->id }})"
                            >
                                {{-- Lead Header --}}
                                <div class="flex items-start justify-between mb-1">
                                    <span class="font-medium text-gray-900 dark:text-white text-sm truncate flex-1">
                                        {{ $lead->full_name }}
                                    </span>
                                    @if($lead->is_new)
                                        <x-filament::badge color="success" size="sm">
                                            New
                                        </x-filament::badge>
                                    @endif
                                </div>

                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mb-2">
                                    {{ $lead->email }}
                                </p>

                                {{-- Lead Info --}}
                                @if($lead->project_type || $lead->budget_range)
                                    <div class="space-y-1.5 text-xs mb-2">
                                        @if($lead->project_type)
                                            <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                                <x-filament::icon
                                                    icon="heroicon-m-briefcase"
                                                    class="h-3.5 w-3.5 text-gray-400"
                                                />
                                                <span class="truncate">{{ is_array($lead->project_type) ? implode(', ', $lead->project_type) : $lead->project_type }}</span>
                                            </div>
                                        @endif
                                        @if($lead->budget_range)
                                            <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                                <x-filament::icon
                                                    icon="heroicon-m-currency-dollar"
                                                    class="h-3.5 w-3.5 text-gray-400"
                                                />
                                                <span class="font-medium text-success-600 dark:text-success-400">
                                                    @switch($lead->budget_range)
                                                        @case('under_10k') < $10K @break
                                                        @case('10k_25k') $10K-$25K @break
                                                        @case('25k_50k') $25K-$50K @break
                                                        @case('50k_100k') $50K-$100K @break
                                                        @case('over_100k') > $100K @break
                                                        @default {{ $lead->budget_range }}
                                                    @endswitch
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Footer with source and time --}}
                                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                    @if($lead->source)
                                        <x-filament::badge color="gray" size="sm">
                                            {{ $lead->source->getLabel() }}
                                        </x-filament::badge>
                                    @else
                                        <span></span>
                                    @endif
                                    <span class="text-[10px] text-gray-400">{{ $lead->created_at->diffForHumans(null, true) }}</span>
                                </div>
                            </x-filament::section>
                        @empty
                            {{-- Empty State --}}
                            <x-filament::section class="flex-1">
                                <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 py-8">
                                    <x-filament::icon
                                        icon="heroicon-o-inbox"
                                        class="h-8 w-8 mb-2 opacity-40"
                                    />
                                    <p class="text-xs">No leads</p>
                                </div>
                            </x-filament::section>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

            {{-- Workflow Stage Columns --}}
            @foreach($boardStatuses as $status)
                @include(static::$statusView)
            @endforeach

            <div wire:ignore>
                @include(static::$scriptsView)
            </div>
        </div>
    </div>

    {{-- Edit Record Modal (from package) --}}
    @unless($disableEditModal)
        <x-filament-kanban::edit-record-modal/>
    @endunless

    {{-- Chatter Modal --}}
    <x-filament::modal
        id="kanban--chatter-modal"
        :close-by-clicking-away="true"
        :close-button="true"
        slide-over
        width="2xl"
    >
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-primary-500" />
                <span>Project Chatter</span>
            </div>
        </x-slot>

        @if($chatterRecord)
            <div class="flex w-full">
                @livewire('chatter-panel', [
                    'record' => $chatterRecord,
                    'activityPlans' => collect(),
                    'resource' => \Webkul\Project\Filament\Resources\ProjectResource::class,
                    'followerViewMail' => null,
                    'messageViewMail' => null,
                ], key('chatter-' . $chatterRecord->id))
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-heroicon-o-chat-bubble-left-ellipsis class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>Select a project to view chatter</p>
            </div>
        @endif
    </x-filament::modal>

    {{-- Quick Actions / Project Details Modal --}}
    <x-filament::modal
        id="kanban--quick-actions-modal"
        :close-by-clicking-away="true"
        :close-button="true"
        slide-over
        width="lg"
    >
        @if($quickActionsRecord ?? null)
            @php
                $project = $quickActionsRecord;
                $daysLeft = null;
                $isOverdue = false;
                if ($project->desired_completion_date) {
                    $days = now()->diffInDays($project->desired_completion_date, false);
                    $daysLeft = (int) $days;
                    $isOverdue = $days < 0;
                }
                $blockers = $this->getProjectBlockers($project);
                $hasBlockers = !empty($blockers);
                $milestones = $project->milestones ?? collect();
                $totalMilestones = $milestones->count();
                $completedMilestones = $milestones->where('is_completed', true)->count();
                $progressPercent = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;
                $availableUsers = $this->getAvailableUsers();
                $availableStages = $this->getAvailableStages();
            @endphp

            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $project->stage?->color ?? '#6b7280' }}"></div>
                    <span class="font-medium text-gray-900 dark:text-white truncate">{{ $project->name }}</span>
                </div>
            </x-slot>

            <div class="space-y-4">
                {{-- Status Badge Row + Blocked Toggle --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        @if($hasBlockers)
                            <x-filament::badge color="gray" class="!bg-purple-100 !text-purple-700 dark:!bg-purple-900/30 dark:!text-purple-300">Blocked</x-filament::badge>
                        @endif
                        @if($isOverdue)
                            <x-filament::badge color="danger">{{ abs($daysLeft) }}d Overdue</x-filament::badge>
                        @elseif($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0)
                            <x-filament::badge color="warning">{{ $daysLeft }}d Left</x-filament::badge>
                        @endif
                    </div>
                    {{-- Blocked Toggle Button --}}
                    <button
                        wire:click="toggleProjectBlocked"
                        class="text-xs px-2 py-1 rounded-md transition-colors {{ $hasBlockers ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' : 'bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400' }}"
                    >
                        {{ $hasBlockers ? '✓ Unblock' : '⚠ Mark Blocked' }}
                    </button>
                </div>

                {{-- Stage Selector --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stage</h4>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($availableStages as $stage)
                            <button
                                wire:click="changeProjectStage({{ $stage->id }})"
                                @class([
                                    'px-2.5 py-1 text-xs rounded-full transition-all',
                                    'ring-2 ring-offset-1 ring-primary-500 font-medium' => $project->stage_id === $stage->id,
                                    'hover:ring-1 hover:ring-gray-300' => $project->stage_id !== $stage->id,
                                ])
                                style="background-color: {{ $stage->color ?? '#6b7280' }}20; color: {{ $stage->color ?? '#6b7280' }}"
                            >
                                {{ $stage->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700" />

                {{-- Customer & Details Row --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <span class="text-xs text-gray-500">Customer</span>
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $project->partner?->name ?? '—' }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs text-gray-500">Due Date</span>
                        <p class="text-sm font-medium {{ $isOverdue ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                            {{ $project->desired_completion_date?->format('M j, Y') ?? '—' }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs text-gray-500">Project #</span>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->project_number ?? '—' }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs text-gray-500">Linear Feet</span>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->estimated_linear_feet ? number_format($project->estimated_linear_feet, 0) . ' LF' : '—' }}</p>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700" />

                {{-- Team Assignment Widget --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Team</h4>
                    <div class="space-y-2">
                        {{-- Project Manager --}}
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center flex-shrink-0">
                                @if($project->user)
                                    <span class="text-xs font-bold text-primary-700 dark:text-primary-300">{{ strtoupper(substr($project->user->name, 0, 1)) }}</span>
                                @else
                                    <x-heroicon-o-user class="w-3 h-3 text-gray-400" />
                                @endif
                            </div>
                            <select
                                wire:change="assignTeamMember('pm', $event.target.value)"
                                class="flex-1 text-sm border-0 bg-transparent focus:ring-0 py-0 cursor-pointer text-gray-900 dark:text-white"
                            >
                                <option value="">Unassigned PM</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->id }}" @selected($project->user_id == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Designer --}}
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-pink-100 dark:bg-pink-900 flex items-center justify-center flex-shrink-0">
                                @if($project->designer)
                                    <span class="text-xs font-bold text-pink-700 dark:text-pink-300">{{ strtoupper(substr($project->designer->name, 0, 1)) }}</span>
                                @else
                                    <x-heroicon-o-paint-brush class="w-3 h-3 text-gray-400" />
                                @endif
                            </div>
                            <select
                                wire:change="assignTeamMember('designer', $event.target.value)"
                                class="flex-1 text-sm border-0 bg-transparent focus:ring-0 py-0 cursor-pointer text-gray-900 dark:text-white"
                            >
                                <option value="">Unassigned Designer</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->id }}" @selected($project->designer_id == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Purchasing Manager --}}
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                                @if($project->purchasingManager)
                                    <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300">{{ strtoupper(substr($project->purchasingManager->name, 0, 1)) }}</span>
                                @else
                                    <x-heroicon-o-shopping-cart class="w-3 h-3 text-gray-400" />
                                @endif
                            </div>
                            <select
                                wire:change="assignTeamMember('purchasing', $event.target.value)"
                                class="flex-1 text-sm border-0 bg-transparent focus:ring-0 py-0 cursor-pointer text-gray-900 dark:text-white"
                            >
                                <option value="">Unassigned Purchasing</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->id }}" @selected($project->purchasing_manager_id == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700" />

                {{-- Milestones Widget with Checkboxes --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Milestones
                            <span class="text-gray-400 font-normal">({{ $completedMilestones }}/{{ $totalMilestones }})</span>
                        </h4>
                    </div>

                    {{-- Milestone List with Checkboxes --}}
                    @if($milestones->isNotEmpty())
                        <div class="space-y-1">
                            @foreach($milestones->sortBy('sort') as $milestone)
                                <label class="flex items-center gap-2 py-1 px-2 -mx-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleMilestoneStatus({{ $milestone->id }})"
                                        @checked($milestone->is_completed)
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="text-sm flex-1 {{ $milestone->is_completed ? 'text-gray-500 line-through' : 'text-gray-900 dark:text-white' }}">
                                        {{ $milestone->name }}
                                    </span>
                                    @if($milestone->deadline)
                                        <span class="text-xs text-gray-400">{{ $milestone->deadline->format('M j') }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endif

                    {{-- Add Milestone Inline --}}
                    <div class="flex items-center gap-2 mt-2">
                        <input
                            type="text"
                            wire:model="quickMilestoneTitle"
                            wire:keydown.enter="addQuickMilestone"
                            placeholder="+ Add milestone..."
                            class="flex-1 text-sm border-0 border-b border-dashed border-gray-300 dark:border-gray-600 bg-transparent focus:ring-0 focus:border-primary-500 px-0 py-1 placeholder-gray-400"
                        />
                        @if($this->quickMilestoneTitle)
                            <button wire:click="addQuickMilestone" class="text-primary-600 hover:text-primary-800">
                                <x-heroicon-m-plus-circle class="w-5 h-5" />
                            </button>
                        @endif
                    </div>

                    {{-- Progress Bar --}}
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                        <div
                            class="h-1.5 rounded-full transition-all duration-300 {{ $hasBlockers ? 'bg-purple-500' : ($isOverdue ? 'bg-red-500' : 'bg-primary-600') }}"
                            style="width: {{ $progressPercent }}%"
                        ></div>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700" />

                {{-- Tasks Widget with Status Dropdowns --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tasks</h4>
                        <a href="{{ \Webkul\Project\Filament\Resources\TaskResource::getUrl('index', ['tableFilters' => ['project_id' => ['value' => $project->id]]]) }}"
                           class="text-xs text-primary-600 hover:text-primary-800">
                            View all
                        </a>
                    </div>

                    @php
                        $recentTasks = $project->tasks->sortByDesc('updated_at')->take(5);
                    @endphp

                    {{-- Task List with Status Dropdowns --}}
                    @if($recentTasks->isNotEmpty())
                        <div class="space-y-1">
                            @foreach($recentTasks as $task)
                                <div class="flex items-center gap-2 py-1 px-2 -mx-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 group">
                                    <select
                                        wire:change="updateTaskStatus({{ $task->id }}, $event.target.value)"
                                        @class([
                                            'text-xs border-0 rounded py-0.5 px-1.5 focus:ring-1 cursor-pointer',
                                            'bg-green-100 text-green-700' => $task->state === 'done',
                                            'bg-purple-100 text-purple-700' => $task->state === 'blocked',
                                            'bg-blue-100 text-blue-700' => $task->state === 'in_progress',
                                            'bg-gray-100 text-gray-700' => !in_array($task->state, ['done', 'blocked', 'in_progress']),
                                        ])
                                    >
                                        <option value="pending" @selected($task->state === 'pending')>To Do</option>
                                        <option value="in_progress" @selected($task->state === 'in_progress')>In Progress</option>
                                        <option value="blocked" @selected($task->state === 'blocked')>Blocked</option>
                                        <option value="done" @selected($task->state === 'done')>Done</option>
                                    </select>
                                    <span class="text-sm flex-1 truncate {{ $task->state === 'done' ? 'text-gray-500 line-through' : 'text-gray-900 dark:text-white' }}">
                                        {{ $task->title }}
                                    </span>
                                    <a href="{{ \Webkul\Project\Filament\Resources\TaskResource::getUrl('edit', ['record' => $task]) }}"
                                       class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-primary-600"
                                       wire:click.stop>
                                        <x-heroicon-m-pencil class="w-3.5 h-3.5" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Add Task Inline --}}
                    <div class="flex items-center gap-2 mt-2">
                        <input
                            type="text"
                            wire:model="quickTaskTitle"
                            wire:keydown.enter="addQuickTask"
                            placeholder="+ Add task..."
                            class="flex-1 text-sm border-0 border-b border-dashed border-gray-300 dark:border-gray-600 bg-transparent focus:ring-0 focus:border-primary-500 px-0 py-1 placeholder-gray-400"
                        />
                        @if($this->quickTaskTitle)
                            <button wire:click="addQuickTask" class="text-primary-600 hover:text-primary-800">
                                <x-heroicon-m-plus-circle class="w-5 h-5" />
                            </button>
                        @endif
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700" />

                {{-- Orders Widget --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Orders</h4>
                    @php
                        $orders = $project->orders;
                        $totalValue = $orders->sum('grand_total');
                    @endphp
                    @if($orders->isEmpty())
                        <p class="text-sm text-gray-400 italic">No orders</p>
                    @else
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-currency-dollar class="h-4 w-4 text-green-500 flex-shrink-0" />
                            <span class="text-lg font-semibold text-green-600 dark:text-green-400">${{ number_format($totalValue, 2) }}</span>
                            <span class="text-xs text-gray-500">({{ $orders->count() }} {{ Str::plural('order', $orders->count()) }})</span>
                        </div>
                    @endif
                </div>

                <hr class="border-gray-200 dark:border-gray-700" />

                {{-- Quick Comment Widget --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quick Note</h4>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            wire:model.live="quickComment"
                            wire:keydown.enter="postQuickComment"
                            placeholder="Add a comment..."
                            class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 focus:ring-primary-500 focus:border-primary-500"
                        />
                        <x-filament::button
                            wire:click="postQuickComment"
                            size="sm"
                            color="gray"
                            :disabled="empty($this->quickComment)"
                        >
                            Post
                        </x-filament::button>
                    </div>
                </div>
            </div>

            {{-- Footer Actions --}}
            <x-slot name="footer">
                <div class="flex items-center gap-2 w-full">
                    <x-filament::button
                        tag="a"
                        href="{{ \Webkul\Project\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $project]) }}"
                        color="primary"
                        icon="heroicon-m-pencil-square"
                        size="sm"
                        class="flex-1"
                    >
                        Full Edit
                    </x-filament::button>

                    <x-filament::icon-button
                        wire:click="openChatter('{{ $project->id }}')"
                        x-on:click="$dispatch('close-modal', { id: 'kanban--quick-actions-modal' })"
                        color="gray"
                        icon="heroicon-m-chat-bubble-left-right"
                        size="sm"
                        label="Messages"
                    />

                    <x-filament::icon-button
                        tag="a"
                        href="{{ route('filament.admin.resources.project.projects.view', $project) }}"
                        color="gray"
                        icon="heroicon-m-arrow-top-right-on-square"
                        size="sm"
                        label="Full Page"
                    />
                </div>
            </x-slot>
        @else
            <x-slot name="heading">Project Details</x-slot>
            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                <x-heroicon-o-folder class="w-10 h-10 mb-2 opacity-50" />
                <p class="text-sm">Select a project to view details</p>
            </div>
        @endif
    </x-filament::modal>

    {{-- Lead Detail Modal --}}
    <x-filament::modal
        id="kanban--lead-detail-modal"
        :close-by-clicking-away="true"
        :close-button="true"
        slide-over
        width="2xl"
    >
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-user-plus class="w-5 h-5 text-amber-500" />
                <span>Lead Details</span>
            </div>
        </x-slot>

        @if($selectedLead ?? null)
            <div x-data="{ activeTab: 'contact' }" class="space-y-4">
                {{-- Filament Native Tabs --}}
                <x-filament::tabs label="Lead details">
                    <x-filament::tabs.item
                        alpine-active="activeTab === 'contact'"
                        x-on:click="activeTab = 'contact'"
                        icon="heroicon-m-user"
                    >
                        Contact
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        alpine-active="activeTab === 'project'"
                        x-on:click="activeTab = 'project'"
                        icon="heroicon-m-briefcase"
                    >
                        Project
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        alpine-active="activeTab === 'location'"
                        x-on:click="activeTab = 'location'"
                        icon="heroicon-m-map-pin"
                    >
                        Location
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        alpine-active="activeTab === 'tracking'"
                        x-on:click="activeTab = 'tracking'"
                        icon="heroicon-m-chart-bar"
                    >
                        Tracking
                    </x-filament::tabs.item>
                </x-filament::tabs>

                {{-- Tab Content --}}
                <div class="min-h-[300px]">
                    {{-- Contact Tab --}}
                    <div x-show="activeTab === 'contact'" x-cloak class="space-y-4">
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <x-heroicon-m-user class="w-4 h-4" />
                                Contact Information
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Name:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $selectedLead->full_name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Email:</span>
                                    <a href="mailto:{{ $selectedLead->email }}" class="text-primary-600 hover:underline ml-1">{{ $selectedLead->email }}</a>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Phone:</span>
                                    <a href="tel:{{ $selectedLead->phone }}" class="text-primary-600 hover:underline ml-1">{{ $selectedLead->phone }}</a>
                                </div>
                                @if($selectedLead->company_name)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Company:</span>
                                        <span class="ml-1">{{ $selectedLead->company_name }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->preferred_contact_method)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Preferred Contact:</span>
                                        <span class="ml-1 capitalize">{{ $selectedLead->preferred_contact_method }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->source)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Lead Source:</span>
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                            {{ $selectedLead->source->getLabel() }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Metadata --}}
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <x-heroicon-m-clock class="w-4 h-4" />
                                Submission Info
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Submitted:</span>
                                    <span class="ml-1">{{ $selectedLead->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Days Ago:</span>
                                    <span class="ml-1">{{ $selectedLead->days_since_submission }} days</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Status:</span>
                                    <span class="ml-1 capitalize">{{ $selectedLead->status->value ?? 'New' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Project Tab --}}
                    <div x-show="activeTab === 'project'" x-cloak class="space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <x-heroicon-m-briefcase class="w-4 h-4" />
                                Project Details
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                @if($selectedLead->project_type)
                                    <div class="col-span-2">
                                        <span class="text-gray-500 dark:text-gray-400">Project Type:</span>
                                        <span class="ml-1 font-medium">{{ is_array($selectedLead->project_type) ? implode(', ', $selectedLead->project_type) : $selectedLead->project_type }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->project_phase)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Project Phase:</span>
                                        <span class="ml-1 capitalize">{{ str_replace('_', ' ', $selectedLead->project_phase) }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->budget_range)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Budget Range:</span>
                                        <span class="ml-1 font-medium text-green-600">
                                            @switch($selectedLead->budget_range)
                                                @case('under_10k') Under $10,000 @break
                                                @case('10k_25k') $10,000 - $25,000 @break
                                                @case('25k_50k') $25,000 - $50,000 @break
                                                @case('50k_100k') $50,000 - $100,000 @break
                                                @case('over_100k') Over $100,000 @break
                                                @default {{ $selectedLead->budget_range }}
                                            @endswitch
                                        </span>
                                    </div>
                                @endif
                                @if($selectedLead->timeline_start_date)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Start Date:</span>
                                        <span class="ml-1">{{ $selectedLead->timeline_start_date?->format('M d, Y') }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->timeline_completion_date)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Completion:</span>
                                        <span class="ml-1">{{ $selectedLead->timeline_completion_date?->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($selectedLead->design_style || $selectedLead->wood_species || $selectedLead->finish_choices)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-paint-brush class="w-4 h-4" />
                                    Design Preferences
                                </h3>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    @if($selectedLead->design_style)
                                        <div class="col-span-2">
                                            <span class="text-gray-500 dark:text-gray-400">Design Style:</span>
                                            <span class="ml-1">{{ is_array($selectedLead->design_style) ? implode(', ', $selectedLead->design_style) : $selectedLead->design_style }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->wood_species)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Wood Species:</span>
                                            <span class="ml-1">{{ ucfirst(str_replace('_', ' ', $selectedLead->wood_species)) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->finish_choices)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Finish:</span>
                                            <span class="ml-1">{{ is_array($selectedLead->finish_choices) ? implode(', ', $selectedLead->finish_choices) : $selectedLead->finish_choices }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($selectedLead->message || $selectedLead->project_description || $selectedLead->additional_information)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-chat-bubble-left-ellipsis class="w-4 h-4" />
                                    Message / Notes
                                </h3>
                                @if($selectedLead->message || $selectedLead->project_description)
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-3">{{ $selectedLead->message ?? $selectedLead->project_description }}</p>
                                @endif
                                @if($selectedLead->additional_information)
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                        <span class="text-xs text-gray-500 font-medium">Additional Info:</span>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $selectedLead->additional_information }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Location Tab --}}
                    <div x-show="activeTab === 'location'" x-cloak class="space-y-4">
                        @if($selectedLead->city || $selectedLead->street1)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-map-pin class="w-4 h-4" />
                                    Project Location
                                </h3>
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    @if($selectedLead->street1)<p>{{ $selectedLead->street1 }}</p>@endif
                                    @if($selectedLead->street2)<p>{{ $selectedLead->street2 }}</p>@endif
                                    <p>
                                        {{ $selectedLead->city }}@if($selectedLead->state), {{ $selectedLead->state }}@endif
                                        @if($selectedLead->zip) {{ $selectedLead->zip }}@endif
                                    </p>
                                    @if($selectedLead->country && $selectedLead->country !== 'United States')
                                        <p>{{ $selectedLead->country }}</p>
                                    @endif
                                </div>
                                @if($selectedLead->project_address_notes)
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                        <span class="text-xs text-gray-500 font-medium">Location Notes:</span>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $selectedLead->project_address_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-8 text-center">
                                <x-heroicon-o-map-pin class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                                <p class="text-gray-500">No location information provided</p>
                            </div>
                        @endif
                    </div>

                    {{-- Tracking Tab --}}
                    <div x-show="activeTab === 'tracking'" x-cloak class="space-y-4">
                        {{-- UTM Attribution --}}
                        @if($selectedLead->utm_source || $selectedLead->utm_medium || $selectedLead->utm_campaign)
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-megaphone class="w-4 h-4 text-blue-600" />
                                    Marketing Attribution
                                </h3>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    @if($selectedLead->utm_source)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Source:</span>
                                            <span class="ml-1 font-medium">{{ $selectedLead->utm_source }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->utm_medium)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Medium:</span>
                                            <span class="ml-1">{{ $selectedLead->utm_medium }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->utm_campaign)
                                        <div class="col-span-2">
                                            <span class="text-gray-500 dark:text-gray-400">Campaign:</span>
                                            <span class="ml-1">{{ $selectedLead->utm_campaign }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->utm_content)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Content:</span>
                                            <span class="ml-1">{{ $selectedLead->utm_content }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->utm_term)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Term:</span>
                                            <span class="ml-1">{{ $selectedLead->utm_term }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Click IDs --}}
                        @if($selectedLead->gclid || $selectedLead->fbclid || $selectedLead->msclkid)
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-cursor-arrow-rays class="w-4 h-4 text-purple-600" />
                                    Ad Platform Click IDs
                                </h3>
                                <div class="space-y-2 text-sm">
                                    @if($selectedLead->gclid)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Google</span>
                                            <span class="text-gray-600 truncate text-xs">{{ Str::limit($selectedLead->gclid, 30) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->fbclid)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-600 text-white">Facebook</span>
                                            <span class="text-gray-600 truncate text-xs">{{ Str::limit($selectedLead->fbclid, 30) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->msclkid)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800">Microsoft</span>
                                            <span class="text-gray-600 truncate text-xs">{{ Str::limit($selectedLead->msclkid, 30) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Device & Session Info --}}
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <x-heroicon-m-device-phone-mobile class="w-4 h-4" />
                                Device & Session
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                @if($selectedLead->device_type)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Device:</span>
                                        <span class="ml-1 capitalize">{{ $selectedLead->device_type }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->browser)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Browser:</span>
                                        <span class="ml-1">{{ $selectedLead->browser }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->operating_system)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">OS:</span>
                                        <span class="ml-1">{{ $selectedLead->operating_system }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->visit_count)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Visits:</span>
                                        <span class="ml-1">{{ $selectedLead->visit_count }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->pages_viewed)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Pages Viewed:</span>
                                        <span class="ml-1">{{ $selectedLead->pages_viewed }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->time_on_site_seconds)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Time on Site:</span>
                                        <span class="ml-1">{{ gmdate('i:s', $selectedLead->time_on_site_seconds) }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->ip_address)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">IP:</span>
                                        <span class="ml-1 text-xs">{{ $selectedLead->ip_address }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- First/Last Touch Attribution --}}
                        @if($selectedLead->first_touch_source || $selectedLead->last_touch_source)
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-arrow-path class="w-4 h-4 text-green-600" />
                                    Attribution Journey
                                </h3>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    @if($selectedLead->first_touch_source)
                                        <div class="space-y-1">
                                            <span class="text-xs font-medium text-gray-500 uppercase">First Touch</span>
                                            <div class="text-gray-900 dark:text-white">{{ $selectedLead->first_touch_source }}</div>
                                            @if($selectedLead->first_touch_medium)
                                                <div class="text-xs text-gray-500">{{ $selectedLead->first_touch_medium }}</div>
                                            @endif
                                        </div>
                                    @endif
                                    @if($selectedLead->last_touch_source)
                                        <div class="space-y-1">
                                            <span class="text-xs font-medium text-gray-500 uppercase">Last Touch</span>
                                            <div class="text-gray-900 dark:text-white">{{ $selectedLead->last_touch_source }}</div>
                                            @if($selectedLead->last_touch_medium)
                                                <div class="text-xs text-gray-500">{{ $selectedLead->last_touch_medium }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Landing/Referrer --}}
                        @if($selectedLead->landing_page || $selectedLead->referrer_url)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-globe-alt class="w-4 h-4" />
                                    Page Info
                                </h3>
                                <div class="space-y-2 text-sm">
                                    @if($selectedLead->landing_page)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-xs">Landing Page:</span>
                                            <span class="text-xs text-gray-600 break-all">{{ $selectedLead->landing_page }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->referrer_url)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-xs">Referrer:</span>
                                            <span class="text-xs text-gray-600 break-all">{{ $selectedLead->referrer_url }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if(!$selectedLead->utm_source && !$selectedLead->device_type && !$selectedLead->first_touch_source)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-8 text-center">
                                <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                                <p class="text-gray-500">No tracking data available</p>
                                <p class="text-xs text-gray-400 mt-1">This lead was submitted before tracking was enabled</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions (always visible) --}}
                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <x-filament::button
                        wire:click="convertLeadToProject({{ $selectedLead->id }})"
                        color="success"
                        icon="heroicon-m-arrow-right-circle"
                        class="flex-1"
                    >
                        Convert to Project
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.admin.resources.leads.edit', $selectedLead->id) }}"
                        color="gray"
                        icon="heroicon-m-pencil"
                    >
                        Edit
                    </x-filament::button>

                    <x-filament::button
                        wire:click="updateLeadStatus({{ $selectedLead->id }}, 'disqualified')"
                        x-on:click="$dispatch('close-modal', { id: 'kanban--lead-detail-modal' })"
                        color="danger"
                        icon="heroicon-m-x-circle"
                        outlined
                    >
                        Disqualify
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-heroicon-o-user-plus class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>Select a lead to view details</p>
            </div>
        @endif
    </x-filament::modal>
</x-filament-panels::page>
