@props([
    'spacing' => 'md', // sm, md, lg
    'label' => null,
])

@php
    $spacingClasses = match($spacing) {
        'sm' => 'my-2',
        'md' => 'my-3',
        'lg' => 'my-4',
        default => 'my-3',
    };
@endphp

@if($label)
    <div {{ $attributes->merge(['class' => "relative $spacingClasses"]) }}>
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="px-2 text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">
                {{ $label }}
            </span>
        </div>
    </div>
@else
    <hr {{ $attributes->merge(['class' => "border-gray-200 dark:border-gray-700 $spacingClasses"]) }} />
@endif
