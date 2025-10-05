<div class="flex gap-2">
    <x-filament::button
        color="info"
        size="xs"
        tag="a"
        href="{{ route('filament.admin.inventory.products.resources.cabinet-reports.index', [
            'tableFilters' => [
                'length_range' => [
                    'min' => $getRecord()->length_inches - 2,
                    'max' => $getRecord()->length_inches + 2,
                ],
            ],
        ]) }}"
    >
        <x-filament::icon
            icon="heroicon-m-magnifying-glass"
            class="h-4 w-4"
        />
        Find Similar
    </x-filament::button>

    <x-filament::button
        color="success"
        size="xs"
        wire:click="$dispatch('open-modal', { id: 'create-template-{{ $getRecord()->id }}' })"
    >
        <x-filament::icon
            icon="heroicon-m-document-duplicate"
            class="h-4 w-4"
        />
        Create Template
    </x-filament::button>
</div>
