<div
    x-data="{
        init() {
            // Listen for hierarchy selection from search component
            window.addEventListener('hierarchySelected', (event) => {
                const selection = event.detail[0];

                // Update all hierarchy Select fields
                this.$wire.set('data.room_id', selection.room_id);
                this.$wire.set('data.room_location_id', selection.room_location_id);
                this.$wire.set('data.cabinet_run_id', selection.cabinet_run_id);
                this.$wire.set('data.cabinet_id', selection.cabinet_id);
            });

            // Listen for hierarchy cleared
            window.addEventListener('hierarchyCleared', () => {
                this.$wire.set('data.room_id', null);
                this.$wire.set('data.room_location_id', null);
                this.$wire.set('data.cabinet_run_id', null);
                this.$wire.set('data.cabinet_id', null);
            });
        }
    }"
>
    @livewire('hierarchy-search-select', [
        'projectId' => $getRecord()?->project_id ?? null,
        'roomId' => $getRecord()?->room_id ?? null,
        'roomLocationId' => $getRecord()?->room_location_id ?? null,
        'cabinetRunId' => $getRecord()?->cabinet_run_id ?? null,
        'cabinetId' => $getRecord()?->cabinet_id ?? null,
    ])
</div>
