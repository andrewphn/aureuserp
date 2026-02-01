@php
    $gridDirection = $getGridDirection() ?? 'column';
    $hasInlineLabel = $hasInlineLabel();
    $id = $getId();
    $isDisabled = $isDisabled();
    $isInline = $isInline();
    $isMultiple = $isMultiple();
    $statePath = $getStatePath();
    $colors = method_exists($field, 'getColors') ? $field->getColors() : [];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :has-inline-label="$hasInlineLabel"
>
    <x-slot
        name="label"
        @class([
            'sm:pt-1.5' => $hasInlineLabel,
        ])
    >
        {{ $getLabel() }}
    </x-slot>

    <div
        {{
            \Filament\Support\prepare_inherited_attributes($attributes)
                ->merge($getExtraAttributes(), escape: false)
                ->class([
                    'fi-fo-progress-stepper',
                    'grid gap-1' => ! $isInline && $gridDirection === 'row',
                    'flex flex-wrap gap-0' => $isInline,
                ])
        }}
    >
        {{ $getChildComponentContainer() }}

        @foreach ($getOptions() as $value => $label)
            @php
                $inputId = "{$id}-{$value}";
                $shouldOptionBeDisabled = $isDisabled || $isOptionDisabled($value, $label);
                $color = $getColor($value);

                // Get the color from the colors array if available
                // Filament v4 uses OKLCH color format: [500 => "oklch(0.623 0.188 259.815)"]
                $stageColor = null;
                if (is_array($colors) && isset($colors[$value])) {
                    $colorValue = $colors[$value];
                    if (is_array($colorValue) && isset($colorValue[500])) {
                        // Get the 500 shade - this is already a valid CSS color string (OKLCH in v4)
                        $stageColor = $colorValue[500];
                    } elseif (is_string($colorValue)) {
                        // Named color - will use fallback
                        $stageColor = null;
                    }
                }
            @endphp

            <div
                @class([
                    'fi-fo-progress-stepper-option relative',
                    'break-inside-avoid' => (! $isInline) && ($gridDirection === 'column'),
                ])
                @if($stageColor)
                    style="--stage-color: {{ $stageColor }};"
                @endif
            >
                <input
                    @disabled($shouldOptionBeDisabled)
                    id="{{ $inputId }}"
                    @if (! $isMultiple)
                        name="{{ $id }}"
                    @endif
                    type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                    value="{{ $value }}"
                    wire:loading.attr="disabled"
                    {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
                    class="fi-fo-progress-stepper-input peer pointer-events-none absolute opacity-0"
                />

                <label
                    for="{{ $inputId }}"
                    class="fi-fo-progress-stepper-btn relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 cursor-pointer select-none transition-all duration-150 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    {{ $label }}
                </label>
            </div>
        @endforeach
    </div>
</x-dynamic-component>

@push('styles')
    <style>
        /* Progress Stepper - Arrow/Chevron Style */
        .fi-fo-progress-stepper {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
        }

        .fi-fo-progress-stepper-option {
            position: relative;
            margin-right: 8px;
            margin-bottom: 4px;
        }

        .fi-fo-progress-stepper-option:last-child {
            margin-right: 0;
        }

        .fi-fo-progress-stepper-btn {
            min-height: 38px;
            padding-left: 24px;
            padding-right: 16px;
            border-radius: 0 !important;
            position: relative;
            z-index: 1;
        }

        /* First button - rounded left */
        .fi-fo-progress-stepper-option:first-child .fi-fo-progress-stepper-btn {
            border-radius: 6px 0 0 6px !important;
            padding-left: 16px;
        }

        /* Last button - rounded right, no arrow */
        .fi-fo-progress-stepper-option:last-child .fi-fo-progress-stepper-btn {
            border-radius: 0 6px 6px 0 !important;
            padding-right: 16px;
        }

        /* Arrow pseudo-element */
        .fi-fo-progress-stepper-btn::after {
            content: "";
            position: absolute;
            top: 50%;
            right: -12px;
            width: 22px;
            height: 22px;
            z-index: 2;
            transform: translateY(-50%) rotate(45deg);
            background-color: white;
            border-right: 1px solid rgb(209, 213, 219);
            border-top: 1px solid rgb(209, 213, 219);
            transition: all 150ms;
        }

        /* Hide arrow on last item */
        .fi-fo-progress-stepper-option:last-child .fi-fo-progress-stepper-btn::after {
            display: none;
        }

        /* Checked state - use stage color */
        .fi-fo-progress-stepper-input:checked + .fi-fo-progress-stepper-btn {
            background-color: var(--stage-color, rgb(20, 184, 166));
            border-color: var(--stage-color, rgb(20, 184, 166));
            color: white;
        }

        .fi-fo-progress-stepper-input:checked + .fi-fo-progress-stepper-btn::after {
            background-color: var(--stage-color, rgb(20, 184, 166));
            border-color: var(--stage-color, rgb(20, 184, 166));
        }

        /* Dark mode adjustments */
        .dark .fi-fo-progress-stepper-btn {
            background-color: rgb(31, 41, 55);
            border-color: rgb(75, 85, 99);
            color: rgb(209, 213, 219);
        }

        .dark .fi-fo-progress-stepper-btn:hover {
            background-color: rgb(55, 65, 81);
        }

        .dark .fi-fo-progress-stepper-btn::after {
            background-color: rgb(31, 41, 55);
            border-color: rgb(75, 85, 99);
        }

        .dark .fi-fo-progress-stepper-input:checked + .fi-fo-progress-stepper-btn::after {
            background-color: var(--stage-color, rgb(20, 184, 166));
        }
    </style>
@endpush
