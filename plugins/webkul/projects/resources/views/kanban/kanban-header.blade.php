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

    {{-- Add button --}}
    <button
        wire:click="openCreateModal('{{ $status['id'] }}')"
        class="text-white/60 hover:text-white hover:bg-white/10 rounded p-1 transition-all duration-100"
        title="Add project"
    >
        <x-heroicon-m-plus class="w-4 h-4" />
    </button>
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
