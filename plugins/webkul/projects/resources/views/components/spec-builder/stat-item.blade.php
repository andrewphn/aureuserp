@props([
    'label',
    'value',
    'suffix' => null,
    'size' => 'sm', // sm, md, lg
])

@php
    $labelClasses = match($size) {
        'sm' => 'text-[10px]',
        'md' => 'text-xs',
        'lg' => 'text-sm',
        default => 'text-[10px]',
    };

    $valueClasses = match($size) {
        'sm' => 'text-xs font-semibold',
        'md' => 'text-sm font-semibold',
        'lg' => 'text-base font-bold',
        default => 'text-xs font-semibold',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col']) }}>
    <span class="{{ $labelClasses }} text-gray-500 dark:text-gray-400">{{ $label }}</span>
    <strong class="{{ $valueClasses }} text-gray-900 dark:text-white tabular-nums">
        {{ $value }}@if($suffix)<span class="font-normal text-gray-500 dark:text-gray-400 ml-0.5">{{ $suffix }}</span>@endif
    </strong>
</div>
