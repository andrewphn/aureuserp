@php
    $color = $status['color'] ?? '#6b7280';
    $projectCount = count($status['records'] ?? []);
    $wipLimit = $status['wip_limit'] ?? null;
    $isOverCapacity = $wipLimit && $projectCount > $wipLimit;

    // Calculate total linear feet for all projects in this column
    $totalLinearFeet = collect($status['records'] ?? [])->sum('estimated_linear_feet');

    // Stage notice settings
    $noticeMessage = $status['notice_message'] ?? null;
    $noticeSeverity = $status['notice_severity'] ?? 'info';

    // Notice styling based on severity
    $noticeStyles = [
        'info' => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#1d4ed8', 'icon' => 'heroicon-m-information-circle'],
        'warning' => ['bg' => '#fff7ed', 'border' => '#f97316', 'text' => '#c2410c', 'icon' => 'heroicon-m-exclamation-triangle'],
        'danger' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#dc2626', 'icon' => 'heroicon-m-exclamation-circle'],
    ];
    $noticeStyle = $noticeStyles[$noticeSeverity] ?? $noticeStyles['info'];
@endphp

{{-- Monday.com Style Header Bar --}}
<div
    class="flex items-center justify-between px-4 py-2 rounded-t-lg transition-all duration-150 min-h-[52px]"
    style="background-color: {{ $color }};"
>
    {{-- Stage Name / Count / Linear Feet --}}
    <div class="flex flex-col">
        <h3 class="font-medium text-white text-sm flex items-center gap-1.5">
            <span>{{ $status['title'] }}</span>
            <span class="text-white/60">/</span>
            <span @class([
                'text-white/90',
                'text-red-200 font-bold' => $isOverCapacity,
            ])>
                {{ $projectCount }}
            </span>
            @if($wipLimit && $isOverCapacity)
                <span class="text-white/50 text-xs">(max {{ $wipLimit }})</span>
            @endif
        </h3>
        <span class="text-xs">
            @if($totalLinearFeet > 0)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-white/20 text-white">
                    {{ number_format($totalLinearFeet, 1) }} LF
                </span>
            @else
                &nbsp;
            @endif
        </span>
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center gap-1">
        {{-- Sort Dropdown (Filament Component) --}}
        <x-filament::dropdown placement="bottom-end">
            <x-slot name="trigger">
                <button
                    type="button"
                    class="text-white/60 hover:text-white hover:bg-white/10 rounded p-1 transition-all duration-100 flex items-center gap-0.5"
                    :class="{ 'bg-white/20 text-white': sortBy !== 'default' }"
                    title="Sort column"
                >
                    <x-filament::icon
                        x-show="sortBy === 'default'"
                        icon="heroicon-m-bars-3"
                        class="h-4 w-4"
                    />
                    <x-filament::icon
                        x-show="sortBy === 'name'"
                        x-cloak
                        icon="heroicon-m-language"
                        class="h-4 w-4"
                    />
                    <x-filament::icon
                        x-show="sortBy === 'due_date'"
                        x-cloak
                        icon="heroicon-m-calendar"
                        class="h-4 w-4"
                    />
                    <x-filament::icon
                        x-show="sortBy === 'linear_feet'"
                        x-cloak
                        icon="heroicon-m-chart-bar"
                        class="h-4 w-4"
                    />
                    <x-filament::icon
                        x-show="sortBy === 'days_left'"
                        x-cloak
                        icon="heroicon-m-bolt"
                        class="h-4 w-4"
                    />
                    <x-filament::icon
                        icon="heroicon-m-chevron-down"
                        class="h-3 w-3 transition-transform"
                        ::class="{ 'rotate-180': sortDir === 'desc' && sortBy !== 'default' }"
                    />
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                <x-filament::dropdown.list.item
                    @click="setSort('default')"
                    icon="heroicon-m-bars-3"
                    ::class="sortBy === 'default' ? 'bg-gray-100 dark:bg-gray-800' : ''"
                >
                    <span class="flex items-center justify-between w-full">
                        Default
                        <x-filament::icon
                            x-show="sortBy === 'default'"
                            icon="heroicon-m-check"
                            class="h-4 w-4 text-primary-500"
                        />
                    </span>
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item
                    @click="setSort('name')"
                    icon="heroicon-m-language"
                >
                    <span class="flex items-center justify-between w-full">
                        Name
                        <span x-show="sortBy === 'name'" class="flex items-center text-primary-500">
                            <x-filament::icon
                                x-show="sortDir === 'asc'"
                                icon="heroicon-m-arrow-up"
                                class="h-4 w-4"
                            />
                            <x-filament::icon
                                x-show="sortDir === 'desc'"
                                x-cloak
                                icon="heroicon-m-arrow-down"
                                class="h-4 w-4"
                            />
                        </span>
                    </span>
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item
                    @click="setSort('due_date')"
                    icon="heroicon-m-calendar"
                >
                    <span class="flex items-center justify-between w-full">
                        Due Date
                        <span x-show="sortBy === 'due_date'" class="flex items-center text-primary-500">
                            <x-filament::icon
                                x-show="sortDir === 'asc'"
                                icon="heroicon-m-arrow-up"
                                class="h-4 w-4"
                            />
                            <x-filament::icon
                                x-show="sortDir === 'desc'"
                                x-cloak
                                icon="heroicon-m-arrow-down"
                                class="h-4 w-4"
                            />
                        </span>
                    </span>
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item
                    @click="setSort('linear_feet')"
                    icon="heroicon-m-chart-bar"
                >
                    <span class="flex items-center justify-between w-full">
                        Linear Feet
                        <span x-show="sortBy === 'linear_feet'" class="flex items-center text-primary-500">
                            <x-filament::icon
                                x-show="sortDir === 'asc'"
                                icon="heroicon-m-arrow-up"
                                class="h-4 w-4"
                            />
                            <x-filament::icon
                                x-show="sortDir === 'desc'"
                                x-cloak
                                icon="heroicon-m-arrow-down"
                                class="h-4 w-4"
                            />
                        </span>
                    </span>
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item
                    @click="setSort('days_left')"
                    icon="heroicon-m-bolt"
                >
                    <span class="flex items-center justify-between w-full">
                        Urgency
                        <span x-show="sortBy === 'days_left'" class="flex items-center text-primary-500">
                            <x-filament::icon
                                x-show="sortDir === 'asc'"
                                icon="heroicon-m-arrow-up"
                                class="h-4 w-4"
                            />
                            <x-filament::icon
                                x-show="sortDir === 'desc'"
                                x-cloak
                                icon="heroicon-m-arrow-down"
                                class="h-4 w-4"
                            />
                        </span>
                    </span>
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>
        </x-filament::dropdown>

        {{-- Add button --}}
        <x-filament::icon-button
            wire:click="openCreateModal('{{ $status['id'] }}')"
            icon="heroicon-m-plus"
            color="gray"
            size="sm"
            class="text-white/60 hover:text-white hover:bg-white/10"
            label="Add project"
        />
    </div>
</div>

{{-- Stage Notice (if configured) --}}
@if($noticeMessage)
    <div
        class="flex items-start gap-2 px-3 py-2 text-xs border-l-3"
        style="background-color: {{ $noticeStyle['bg'] }}; border-left: 3px solid {{ $noticeStyle['border'] }}; color: {{ $noticeStyle['text'] }};"
    >
        <x-filament::icon
            :icon="$noticeStyle['icon']"
            class="w-4 h-4 flex-shrink-0 mt-0.5"
        />
        <span class="leading-snug">{{ $noticeMessage }}</span>
    </div>
@endif
