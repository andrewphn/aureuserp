@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'level' => 3, // heading level: 2, 3, 4
    'size' => 'md', // sm, md, lg
    'border' => false,
])

@php
    $headingTag = "h{$level}";

    $titleClasses = match($size) {
        'sm' => 'text-xs font-semibold',
        'md' => 'text-sm font-semibold',
        'lg' => 'text-base font-bold',
        default => 'text-sm font-semibold',
    };

    $iconClasses = match($size) {
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
        default => 'w-5 h-5',
    };

    $containerClasses = $border
        ? 'pb-2 mb-3 border-b border-gray-200 dark:border-gray-700'
        : 'mb-2';
@endphp

<div {{ $attributes->merge(['class' => "flex items-center justify-between gap-3 $containerClasses"]) }}>
    <div class="flex items-center gap-2">
        @if($icon)
            <x-dynamic-component :component="$icon" class="{{ $iconClasses }} text-gray-400 dark:text-gray-500" />
        @endif
        <div>
            <{{ $headingTag }} class="{{ $titleClasses }} text-gray-900 dark:text-white">
                {{ $title }}
            </{{ $headingTag }}>
            @if($subtitle)
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if($slot->isNotEmpty())
        <div class="flex items-center gap-1">
            {{ $slot }}
        </div>
    @endif
</div>
