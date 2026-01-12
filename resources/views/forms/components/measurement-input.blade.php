@php
    $showUnitSelector = $getShowUnitSelector();
    $unitSelectorField = $getUnitSelectorField();
    
    // Convert state path to Alpine-compatible bracket notation for numeric indices
    // e.g., "mountedActions.0.data.runs..." -> "mountedActions[0].data.runs..."
    $statePath = $getStatePath();
    $alpineStatePath = preg_replace_callback(
        '/\.(\d+)(?=\.|$)/',
        fn($matches) => '[' . $matches[1] . ']',
        $statePath
    );
    
    // Build unit selector field path (same as state path but with _unit suffix)
    // For nested repeaters, we need the full path
    $unitSelectorPath = $statePath . '_unit';
    $alpineUnitSelectorPath = preg_replace_callback(
        '/\.(\d+)(?=\.|$)/',
        fn($matches) => '[' . $matches[1] . ']',
        $unitSelectorPath
    );
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($showUnitSelector)
        <div class="flex gap-2 items-stretch">
            <div class="flex-1 min-w-0">
                <x-filament::input.wrapper
                    :disabled="$isDisabled()"
                    :prefix="$getPrefixLabel()"
                    :valid="! $errors->has($getStatePath())"
                    class="fi-fo-text-input"
                >
                    <x-filament::input
                        type="text"
                        :id="$getId()"
                        :disabled="$isDisabled()"
                        :required="$isRequired()"
                        :placeholder="$getPlaceholder()"
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes($attributes)
                                ->merge([
                                    'x-model' => $alpineStatePath,
                                ], escape: false)
                        "
                    />
                </x-filament::input.wrapper>
            </div>
            
            <div class="flex-shrink-0 w-20 relative">
                <x-filament::input.wrapper
                    :disabled="$isDisabled()"
                    :valid="! $errors->has($unitSelectorField)"
                    class="fi-fo-select"
                >
                    <select
                        x-model="{{ $alpineUnitSelectorPath }}"
                        @if($isDisabled()) disabled @endif
                        class="fi-input block w-full border-none bg-transparent px-3 py-1.5 pr-8 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] appearance-none cursor-pointer"
                    >
                        <option value="inches">in</option>
                        <option value="feet">ft</option>
                        <option value="yards">yd</option>
                        <option value="millimeters">mm</option>
                        <option value="centimeters">cm</option>
                        <option value="meters">m</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                        <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </x-filament::input.wrapper>
            </div>
        </div>
    @else
        <x-filament::input.wrapper
            :disabled="$isDisabled()"
            :prefix="$getPrefixLabel()"
            :suffix="'in'"
            :valid="! $errors->has($getStatePath())"
            class="fi-fo-text-input"
        >
            <x-filament::input
                type="text"
                :id="$getId()"
                :disabled="$isDisabled()"
                :required="$isRequired()"
                :placeholder="$getPlaceholder()"
                :attributes="
                    \Filament\Support\prepare_inherited_attributes($attributes)
                        ->merge([
                            'x-model' => $alpineStatePath,
                        ], escape: false)
                "
            />
        </x-filament::input.wrapper>
    @endif
</x-dynamic-component>
