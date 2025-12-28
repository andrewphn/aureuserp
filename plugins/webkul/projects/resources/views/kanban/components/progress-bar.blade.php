{{-- Reusable Progress Bar Component --}}
@props([
    'percent' => 0,
    'label' => null,
    'status' => null,      // 'blocked', 'overdue', 'due_soon', 'done', 'in_progress', 'on_track'
    'showPercent' => true,
    'height' => 'h-6'
])

@php
    // Progress bar colors based on status
    $colorMap = [
        'blocked' => ['bar' => 'bg-purple-500', 'bg' => 'bg-purple-100 dark:bg-purple-900/20'],
        'overdue' => ['bar' => 'bg-danger-500', 'bg' => 'bg-danger-100 dark:bg-danger-900/20'],
        'due_soon' => ['bar' => 'bg-warning-500', 'bg' => 'bg-warning-100 dark:bg-warning-900/20'],
        'done' => ['bar' => 'bg-success-500', 'bg' => 'bg-success-100 dark:bg-success-900/20'],
        'in_progress' => ['bar' => 'bg-info-500', 'bg' => 'bg-info-100 dark:bg-info-900/20'],
        'on_track' => ['bar' => 'bg-success-500', 'bg' => 'bg-success-100 dark:bg-success-900/20'],
    ];

    $colors = $colorMap[$status] ?? $colorMap['on_track'];
    $barClass = $colors['bar'];
    $bgClass = $colors['bg'];
@endphp

<div class="relative {{ $height }} {{ $bgClass }} border-t border-gray-100 dark:border-gray-700">
    {{-- Filled portion --}}
    <div
        class="absolute inset-y-0 left-0 {{ $barClass }} transition-all duration-300"
        style="width: {{ min(100, max(0, $percent)) }}%;"
    ></div>

    {{-- Progress text overlay --}}
    <div class="absolute inset-0 flex items-center justify-between px-3">
        @if($label)
            <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 relative z-10">
                {{ $label }}
            </span>
        @else
            <span></span>
        @endif

        @if($showPercent)
            <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 relative z-10">
                {{ round($percent) }}%
            </span>
        @endif
    </div>
</div>
