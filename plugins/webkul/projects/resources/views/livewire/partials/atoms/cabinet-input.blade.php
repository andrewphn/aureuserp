{{-- Atom: Cabinet Input Field --}}
{{-- Reusable input component for cabinet table cells with dark mode support --}}

@props([
    'value' => '',
    'type' => 'text',
    'step' => null,
    'min' => null,
    'placeholder' => '',
    'class' => '',
])

<input
    type="{{ $type }}"
    value="{{ $value }}"
    @if($step) step="{{ $step }}" @endif
    @if($min) min="{{ $min }}" @endif
    placeholder="{{ $placeholder }}"
    {{ $attributes->merge([
        'class' => 'w-full px-2.5 py-1.5 text-sm text-center border-2 border-primary-500 dark:border-primary-400 rounded-md focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all ' . $class
    ]) }}
/>
