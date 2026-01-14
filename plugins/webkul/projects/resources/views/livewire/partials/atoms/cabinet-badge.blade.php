{{-- Atom: Cabinet Badge --}}
{{-- Status/type indicators with dark mode support --}}

@props([
    'text' => '',
    'color' => 'gray',
    'size' => 'sm',
    'icon' => null,
])

@php
    $sizeClasses = [
        'xs' => 'text-xs px-1.5 py-0.5',
        'sm' => 'text-xs px-2 py-1',
        'md' => 'text-sm px-2.5 py-1',
    ];
    
    $colorClasses = [
        'gray' => 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700',
        'primary' => 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 border-primary-200 dark:border-primary-700',
        'success' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700',
        'warning' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
        'danger' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700',
    ];
@endphp

<span class="inline-flex items-center gap-1 {{ $sizeClasses[$size] ?? $sizeClasses['sm'] }} {{ $colorClasses[$color] ?? $colorClasses['gray'] }} rounded-md border font-medium">
    @if($icon)
        <x-dynamic-component :component="$icon" class="w-3 h-3" />
    @endif
    {{ $text }}
</span>
