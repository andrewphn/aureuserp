<div
    x-data="{
        init() {
            // Listen for spec data updates from the CabinetSpecBuilder component
            // and call method directly on the parent Filament form component
            Livewire.on('spec-data-updated', (event) => {
                this.$wire.call('handleSpecDataUpdate', event.data);
            });
        }
    }"
>
    {{-- specData is passed directly to the Livewire component on mount --}}
    {{-- Use a stable key to prevent remounting on re-render --}}
    @livewire('cabinet-spec-builder', ['specData' => $specData ?? []], key('cabinet-spec-builder'))
</div>
