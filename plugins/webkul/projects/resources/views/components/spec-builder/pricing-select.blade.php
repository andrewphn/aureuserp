@props([
    'label',
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => 'Select...',
    'inheritLabel' => 'Inherit from parent',
    'allowInherit' => false,
    'size' => 'sm', // sm, md
    'disabled' => false,
])

@php
    $selectClasses = match($size) {
        'sm' => 'text-xs py-1.5 pl-2 pr-7',
        'md' => 'text-sm py-2 pl-3 pr-8',
        default => 'text-xs py-1.5 pl-2 pr-7',
    };

    $labelClasses = match($size) {
        'sm' => 'text-[10px]',
        'md' => 'text-xs',
        default => 'text-[10px]',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1']) }}>
    <label class="{{ $labelClasses }} font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
        {{ $label }}
    </label>
    <select
        name="{{ $name }}"
        class="{{ $selectClasses }} w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
        @if($disabled) disabled @endif
    >
        @if($allowInherit)
            <option value="">{{ $inheritLabel }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value == $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
</div>
