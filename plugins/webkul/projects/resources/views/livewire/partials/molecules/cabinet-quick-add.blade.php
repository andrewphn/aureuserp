{{-- Molecule: Cabinet Quick Add --}}
{{-- Quick add input bar at bottom of table with cabinet code parsing --}}

<div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
    <div class="flex items-center gap-2">
        <x-filament::input.wrapper class="flex-1">
            <x-filament::input
                type="text"
                x-ref="quickAddInput"
                placeholder="Quick add: B24, W30, SB36..."
                x-on:keydown.enter.prevent.stop="addCabinetFromCode($event.target.value); $event.target.value = ''"
                class="bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
            />
        </x-filament::input.wrapper>
        <x-filament::button
            color="primary"
            size="sm"
            x-on:click="addCabinetFromCode($refs.quickAddInput.value); $refs.quickAddInput.value = ''"
            class="dark:bg-primary-600 dark:hover:bg-primary-700"
        >
            Add
        </x-filament::button>
    </div>
    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
        Codes: B=Base, W=Wall, SB=Sink Base, T=Tall, V=Vanity + width (e.g., B24, W3012)
    </p>
</div>
