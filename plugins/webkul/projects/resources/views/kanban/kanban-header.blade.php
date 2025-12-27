@php
    $color = $status['color'] ?? '#6b7280';
    $projectCount = count($status['records'] ?? []);
    $wipLimit = $status['wip_limit'] ?? null;
    $isOverCapacity = $wipLimit && $projectCount > $wipLimit;

    // Calculate total linear feet for all projects in this column
    $totalLinearFeet = collect($status['records'] ?? [])->sum('estimated_linear_feet');
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
        <span class="text-white/70 text-xs">
            @if($totalLinearFeet > 0)
                {{ number_format($totalLinearFeet, 1) }} LF
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
