@props(['score', 'level', 'color'])

@php
    $colorClasses = match ($color) {
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
    };

    $iconClass = match ($level) {
        'high' => 'heroicon-o-check-badge',
        'medium' => 'heroicon-o-exclamation-triangle',
        'low' => 'heroicon-o-x-circle',
        default => 'heroicon-o-question-mark-circle',
    };
@endphp

<div class="flex items-center gap-2">
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium {{ $colorClasses }}">
        @if ($level === 'high')
            <x-heroicon-o-check-badge class="w-4 h-4" />
        @elseif ($level === 'medium')
            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
        @elseif ($level === 'low')
            <x-heroicon-o-x-circle class="w-4 h-4" />
        @else
            <x-heroicon-o-question-mark-circle class="w-4 h-4" />
        @endif
        {{ number_format($score, 0) }}%
        <span class="text-xs opacity-75">({{ ucfirst($level) }})</span>
    </span>
</div>
