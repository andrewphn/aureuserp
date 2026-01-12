@props([
    'variant' => 'gray', // gray, primary, success, warning, danger, info
    'size' => 'sm', // xs, sm, md
    'icon' => null,
])

@php
    $sizeClasses = match($size) {
        'xs' => 'px-1.5 py-0.5 text-[10px]',
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
        default => 'px-2 py-0.5 text-xs',
    };

    $variantClasses = match($variant) {
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'primary' => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
        'success' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };

    $iconSize = match($size) {
        'xs' => 'w-3 h-3',
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        default => 'w-3.5 h-3.5',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 font-medium rounded-full $sizeClasses $variantClasses"]) }}>
    @if($icon)
        <x-dynamic-component :component="$icon" class="{{ $iconSize }}" />
    @endif
    {{ $slot }}
</span>
