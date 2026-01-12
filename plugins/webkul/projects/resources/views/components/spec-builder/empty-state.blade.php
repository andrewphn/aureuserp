@props([
    'icon' => 'heroicon-o-inbox',
    'title',
    'description' => null,
    'size' => 'md', // sm, md, lg
    'compact' => false,
])

@php
    $iconContainerClasses = match($size) {
        'sm' => 'w-10 h-10',
        'md' => 'w-14 h-14',
        'lg' => 'w-16 h-16',
        default => 'w-14 h-14',
    };

    $iconClasses = match($size) {
        'sm' => 'w-5 h-5',
        'md' => 'w-7 h-7',
        'lg' => 'w-8 h-8',
        default => 'w-7 h-7',
    };

    $titleClasses = match($size) {
        'sm' => 'text-xs font-medium',
        'md' => 'text-sm font-medium',
        'lg' => 'text-base font-semibold',
        default => 'text-sm font-medium',
    };

    $paddingClasses = $compact
        ? 'py-6 px-4'
        : 'py-10 px-6';
@endphp

<div {{ $attributes->merge(['class' => "flex flex-col items-center justify-center text-center $paddingClasses"]) }}>
    <div class="{{ $iconContainerClasses }} mb-3 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
        <x-dynamic-component :component="$icon" class="{{ $iconClasses }} text-gray-400 dark:text-gray-500" />
    </div>

    <p class="{{ $titleClasses }} text-gray-700 dark:text-gray-300 mb-1">{{ $title }}</p>

    @if($description)
        <p class="text-xs text-gray-500 dark:text-gray-400 max-w-xs mb-4">{{ $description }}</p>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-2">
            {{ $slot }}
        </div>
    @endif
</div>
