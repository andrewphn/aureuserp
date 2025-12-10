{{-- Unified Wizard Footer --}}
{{-- Follows "Don't Make Me Think" UX principles: --}}
{{-- - Clear visual hierarchy (primary action prominent) --}}
{{-- - Auto-save indicator removes anxiety about losing work --}}
{{-- - Back button hidden on Step 1 (no place to go back to) --}}
{{-- - Outcome-focused labels --}}

<div class="wizard-footer flex items-center justify-between gap-4 py-4 px-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-b-xl -mx-6 -mb-6 mt-6">
    {{-- Left Side: Auto-save Status --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <template x-if="$wire.lastSavedAt">
            <span class="flex items-center gap-1.5">
                <x-heroicon-o-cloud-arrow-up class="w-4 h-4 text-green-500" />
                <span x-text="'Draft saved ' + $wire.lastSavedAt"></span>
            </span>
        </template>
        <template x-if="!$wire.lastSavedAt">
            <span class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500">
                <x-heroicon-o-cloud class="w-4 h-4" />
                <span>Auto-saving...</span>
            </span>
        </template>
    </div>

    {{-- Right Side: Navigation Buttons --}}
    <div class="flex items-center gap-3">
        {{-- Back Button (hidden on Step 1) --}}
        <div x-show="! isFirstStep()">
            <x-filament::button
                type="button"
                color="gray"
                x-on:click="previousStep"
                icon="heroicon-o-arrow-left"
            >
                Back
            </x-filament::button>
        </div>

        {{-- Create Now Button (available on all steps except last) --}}
        <div x-show="! isLastStep()">
            <x-filament::button
                type="submit"
                color="success"
                icon="heroicon-o-rocket-launch"
            >
                Create Now
            </x-filament::button>
        </div>

        {{-- Next Button (not on last step) --}}
        <div x-show="! isLastStep()">
            <x-filament::button
                type="button"
                color="primary"
                x-on:click="
                    const nextStep = $wire.getNextStep();
                    if (nextStep) {
                        $wire.nextStep();
                    }
                "
                icon="heroicon-o-arrow-right"
                icon-position="after"
            >
                Next
            </x-filament::button>
        </div>

        {{-- Create Project Button (Final Step Only) --}}
        <div x-show="isLastStep()">
            <x-filament::button
                type="submit"
                color="success"
                icon="heroicon-o-check-circle"
                size="lg"
            >
                Create Project
            </x-filament::button>
        </div>
    </div>
</div>
