@php
    $showUnitSelector = $getShowUnitSelector();
    $unitSelectorField = $getUnitSelectorField();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($showUnitSelector)
        <div class="flex gap-2 items-end">
            <div class="flex-1">
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
                                    'x-model' => $getStatePath(),
                                ], escape: false)
                        "
                    />
                </x-filament::input.wrapper>
            </div>
            
            <div class="w-24">
                <x-filament::input.wrapper
                    :valid="true"
                    class="fi-fo-select"
                >
                    <select
                        {{ $applyStateBindingModifiers('wire:model.live') }}="{{ $unitSelectorField }}"
                        class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)]"
                    >
                        <option value="inches">in</option>
                        <option value="feet">ft</option>
                        <option value="yards">yd</option>
                        <option value="millimeters">mm</option>
                        <option value="centimeters">cm</option>
                        <option value="meters">m</option>
                    </select>
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
                            'x-model' => $getStatePath(),
                        ], escape: false)
                "
            />
        </x-filament::input.wrapper>
    @endif
</x-dynamic-component>
