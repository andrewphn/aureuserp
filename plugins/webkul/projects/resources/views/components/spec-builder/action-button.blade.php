@props([
    'variant' => 'secondary', // primary, secondary, danger, ghost
    'size' => 'sm', // xs, sm, md
    'icon' => null,
    'iconOnly' => false,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-1.5 font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1';

    $sizeClasses = match($size) {
        'xs' => 'px-2 py-1 text-xs',
        'sm' => $iconOnly ? 'p-1.5' : 'px-2.5 py-1.5 text-xs',
        'md' => $iconOnly ? 'p-2' : 'px-3 py-2 text-sm',
        default => 'px-2.5 py-1.5 text-xs',
    };

    $variantClasses = match($variant) {
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 dark:bg-primary-500 dark:hover:bg-primary-600',
        'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
        'danger' => 'bg-red-50 text-red-600 hover:bg-red-100 focus:ring-red-500 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30',
        'ghost' => 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:ring-gray-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200',
        'success' => 'bg-green-50 text-green-600 hover:bg-green-100 focus:ring-green-500 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30',
        default => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
    };

    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : 'cursor-pointer';

    $iconSize = match($size) {
        'xs' => 'w-3 h-3',
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        default => 'w-4 h-4',
    };
@endphp

<button
    {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses $disabledClasses"]) }}
    @if($disabled) disabled @endif
>
    @if($icon)
        <x-dynamic-component :component="$icon" class="{{ $iconSize }}" />
    @endif

    @if(!$iconOnly)
        {{ $slot }}
    @endif
</button>
