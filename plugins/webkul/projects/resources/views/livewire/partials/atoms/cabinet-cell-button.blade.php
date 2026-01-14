{{-- Atom: Cabinet Cell Button --}}
{{-- Clickable cell that becomes an input when editing --}}

@props([
    'value' => '',
    'displayValue' => null,
    'class' => '',
])

<button
    {{ $attributes->merge([
        'class' => 'w-full text-center px-2.5 py-1.5 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:bg-gray-100 dark:hover:bg-gray-800/50 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 ' . $class
    ]) }}
    title="Click to edit"
>
    {{ $displayValue ?? $value }}
</button>
