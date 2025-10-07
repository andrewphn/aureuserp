<x-filament-panels::page>
    {{-- PDF Page Thumbnails Grid --}}
    @if(\Storage::disk('public')->exists($this->pdfDocument->file_path))
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4">{{ $this->pdfDocument->file_name }} — {{ $this->getTotalPages() }} Pages</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @for($page = 1; $page <= $this->getTotalPages(); $page++)
                    <div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden hover:border-primary-500 transition">
                        <div class="aspect-[8.5/11] bg-gray-100 dark:bg-gray-900 relative">
                            <iframe
                                src="{{ $this->getPdfUrl() }}#page={{ $page }}"
                                class="w-full h-full pointer-events-none"
                                frameborder="0"
                            ></iframe>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-2 py-1 text-center text-sm font-medium">
                            Page {{ $page }}
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @endif

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
