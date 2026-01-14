{{-- Atom: Cabinet Icon Button --}}
{{-- Icon-only action buttons with dark mode support --}}

@props([
    'icon' => 'heroicon-m-pencil-square',
    'color' => 'gray',
    'size' => 'sm',
    'tooltip' => '',
])

@php
    $sizeClasses = [
        'xs' => 'p-1',
        'sm' => 'p-1.5',
        'md' => 'p-2',
    ];
    
    $colorClasses = [
        'gray' => 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800',
        'primary' => 'text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20',
        'danger' => 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20',
        'success' => 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20',
    ];
@endphp

<button
    {{ $attributes->merge([
        'class' => 'rounded-md transition-colors ' . ($sizeClasses[$size] ?? $sizeClasses['sm']) . ' ' . ($colorClasses[$color] ?? $colorClasses['gray']),
        'title' => $tooltip,
    ]) }}
>
    <x-dynamic-component :component="$icon" class="w-4 h-4" />
</button>
