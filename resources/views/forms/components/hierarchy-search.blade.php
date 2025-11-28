<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} || {},

            init() {
                // Listen for hierarchy selection from Livewire component
                window.addEventListener('hierarchySelected', (event) => {
                    this.state = event.detail[0];
                });

                // Listen for hierarchy cleared
                window.addEventListener('hierarchyCleared', () => {
                    this.state = {};
                });
            }
        }"
    >
        @livewire('hierarchy-search-select', [
            'projectId' => $getState()['project_id'] ?? null,
            'roomId' => $getState()['room_id'] ?? null,
            'roomLocationId' => $getState()['room_location_id'] ?? null,
            'cabinetRunId' => $getState()['cabinet_run_id'] ?? null,
            'cabinetSpecificationId' => $getState()['cabinet_specification_id'] ?? null,
        ])
    </div>
</x-dynamic-component>
