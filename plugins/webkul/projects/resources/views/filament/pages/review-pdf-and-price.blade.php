<x-filament-panels::page>
    <form wire:submit.prevent="createSalesOrder">
        {{ $this->form }}

        <div class="mt-6 flex justify-between gap-3">
            <x-filament::button
                type="button"
                color="warning"
                wire:click="tryAutomatic"
            >
                Try Automatic Parsing
            </x-filament::button>

            <div class="flex gap-3">
                <x-filament::button
                    type="button"
                    color="gray"
                    tag="a"
                    :href="\Webkul\Project\Filament\Resources\ProjectResource::getUrl('view', ['record' => $this->record])"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    color="success"
                >
                    Create Sales Order
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>
