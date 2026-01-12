@props([
    'amount',
    'suffix' => '/LF',
    'label' => null,
    'size' => 'md', // sm, md, lg
    'variant' => 'default', // default, success, muted
])

@php
    $amountClasses = match($size) {
        'sm' => 'text-sm font-semibold',
        'md' => 'text-base font-bold',
        'lg' => 'text-xl font-bold',
        default => 'text-base font-bold',
    };

    $variantClasses = match($variant) {
        'default' => 'text-gray-900 dark:text-white',
        'success' => 'text-green-600 dark:text-green-400',
        'muted' => 'text-gray-500 dark:text-gray-400',
        default => 'text-gray-900 dark:text-white',
    };

    $labelClasses = match($size) {
        'sm' => 'text-[10px]',
        'md' => 'text-xs',
        'lg' => 'text-sm',
        default => 'text-xs',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col']) }}>
    @if($label)
        <span class="{{ $labelClasses }} text-gray-500 dark:text-gray-400 mb-0.5">{{ $label }}</span>
    @endif
    <span class="{{ $amountClasses }} {{ $variantClasses }} tabular-nums">
        ${{ is_numeric($amount) ? number_format($amount, 2) : $amount }}
        @if($suffix)
            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $suffix }}</span>
        @endif
    </span>
</div>
