@props([
    'padding' => 'md', // none, sm, md, lg
    'border' => true,
    'shadow' => false,
])

@php
    $paddingClasses = match($padding) {
        'none' => '',
        'sm' => 'p-3',
        'md' => 'p-4',
        'lg' => 'p-6',
        default => 'p-4',
    };

    $borderClasses = $border
        ? 'border border-gray-200 dark:border-gray-700'
        : '';

    $shadowClasses = $shadow
        ? 'shadow-sm'
        : '';
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg bg-white dark:bg-gray-800 $paddingClasses $borderClasses $shadowClasses"]) }}>
    {{ $slot }}
</div>
