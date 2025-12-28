{{-- Reusable Status Badge Component --}}
@props([
    'status' => null,      // 'blocked', 'overdue', 'due_soon', 'done', 'in_progress'
    'isBlocked' => false,  // For purple blocked styling
    'size' => 'sm'
])

@php
    $colorMap = [
        'blocked' => 'gray',
        'overdue' => 'danger',
        'due_soon' => 'warning',
        'done' => 'success',
        'in_progress' => 'info',
    ];

    $labelMap = [
        'blocked' => 'Blocked',
        'overdue' => 'Overdue',
        'due_soon' => 'Due Soon',
        'done' => 'Done',
        'in_progress' => 'In Progress',
    ];

    $color = $colorMap[$status] ?? 'gray';
    $label = $labelMap[$status] ?? ucfirst(str_replace('_', ' ', $status ?? ''));
@endphp

@if($status)
    <x-filament::badge
        :color="$color"
        :size="$size"
        @class([
            '!bg-purple-100 !text-purple-700 dark:!bg-purple-900/30 dark:!text-purple-300' => $isBlocked || $status === 'blocked',
        ])
    >
        {{ $label }}
    </x-filament::badge>
@endif
