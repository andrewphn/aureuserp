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
        {{-- Sort Button with Dropdown --}}
        <div class="relative">
            <button
                @click="sortMenuOpen = !sortMenuOpen"
                class="text-white/60 hover:text-white hover:bg-white/10 rounded p-1 transition-all duration-100 flex items-center gap-0.5"
                :class="{ 'bg-white/20 text-white': sortBy !== 'default' }"
                title="Sort column"
            >
                <span class="text-xs font-medium" x-text="currentSortLabel"></span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3 h-3" :class="{ 'rotate-180': sortDir === 'desc' && sortBy !== 'default' }">
                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </button>

            {{-- Sort Dropdown Menu --}}
            <div
                x-show="sortMenuOpen"
                @click.away="sortMenuOpen = false"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                style="position: absolute; top: 100%; right: 0; margin-top: 4px; background: white; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; min-width: 150px; z-index: 50;"
            >
                <div style="padding: 4px 0;">
                    <template x-for="opt in sortOptions" :key="opt.key">
                        <button
                            @click="setSort(opt.key)"
                            type="button"
                            style="width: 100%; padding: 8px 12px; text-align: left; display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: #374151; background: transparent; border: none; cursor: pointer;"
                            :style="sortBy === opt.key ? 'background: #f3f4f6; font-weight: 600;' : ''"
                            onmouseover="if(this.style.background !== 'rgb(243, 244, 246)') this.style.background='#f9fafb'"
                            onmouseout="if(this.style.fontWeight !== '600') this.style.background='transparent'"
                        >
                            <span class="flex items-center gap-2">
                                <span x-text="opt.icon" style="width: 20px; text-align: center;"></span>
                                <span x-text="opt.label"></span>
                            </span>
                            <span x-show="sortBy === opt.key" style="color: #3b82f6;">
                                <svg x-show="sortDir === 'asc'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width: 14px; height: 14px;">
                                    <path fill-rule="evenodd" d="M11.78 9.78a.75.75 0 0 1-1.06 0L8 7.06 5.28 9.78a.75.75 0 0 1-1.06-1.06l3.25-3.25a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" />
                                </svg>
                                <svg x-show="sortDir === 'desc'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width: 14px; height: 14px;">
                                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Add button --}}
        <button
            wire:click="openCreateModal('{{ $status['id'] }}')"
            class="text-white/60 hover:text-white hover:bg-white/10 rounded p-1 transition-all duration-100"
            title="Add project"
        >
            <x-heroicon-m-plus class="w-4 h-4" />
        </button>
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
