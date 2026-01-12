@props([
    'label',
    'value' => null,
    'icon' => null,
    'layout' => 'horizontal', // horizontal, vertical
])

@php
    $containerClasses = match($layout) {
        'horizontal' => 'flex items-center justify-between gap-2',
        'vertical' => 'flex flex-col gap-0.5',
        default => 'flex items-center justify-between gap-2',
    };
@endphp

<div {{ $attributes->merge(['class' => $containerClasses]) }}>
    <div class="flex items-center gap-1.5">
        @if($icon)
            <x-dynamic-component :component="$icon" class="w-4 h-4 text-gray-400 dark:text-gray-500" />
        @endif
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
    </div>

    @if($value !== null)
        <strong class="text-xs font-semibold text-gray-900 dark:text-white">{{ $value }}</strong>
    @elseif($slot->isNotEmpty())
        <div class="text-xs font-semibold text-gray-900 dark:text-white">
            {{ $slot }}
        </div>
    @endif
</div>
