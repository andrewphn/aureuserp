<x-filament-panels::page>
    <div class="space-y-6">
        {{-- PDF Page Thumbnails --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-lg font-medium mb-4">
                {{ $this->pdfDocument->file_name }} — {{ $this->getTotalPages() }} Pages
            </h3>

            @if(\Storage::disk('public')->exists($this->pdfDocument->file_path))
                <div class="grid grid-cols-4 gap-4">
                    @for($page = 1; $page <= $this->getTotalPages(); $page++)
                        <div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden hover:border-primary-500 transition cursor-pointer">
                            <div class="aspect-[8.5/11] bg-gray-100 dark:bg-gray-900 relative">
                                <iframe
                                    src="{{ $this->getPdfUrl() }}#page={{ $page }}"
                                    class="w-full h-full pointer-events-none"
                                    frameborder="0"
                                ></iframe>
                                <div class="absolute inset-0 bg-transparent"></div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 px-2 py-1 text-center">
                                <span class="text-sm font-medium">Page {{ $page }}</span>
                            </div>
                        </div>
                    @endfor
                </div>
            @else
                <div class="flex items-center justify-center py-12">
                    <div class="text-center p-8">
                        <svg class="mx-auto h-16 w-16 text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            PDF File Missing
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            The PDF file has not been uploaded or was deleted.<br>
                            Please go back and re-upload the architectural PDF.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Wizard Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
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
        </div>
    </div>
</x-filament-panels::page>
