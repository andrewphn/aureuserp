<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-6">
        {{-- PDF Viewer Side --}}
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">
                        {{ $this->pdfDocument->file_name }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <x-filament::button
                            wire:click="previousPage"
                            :disabled="$currentPage === 1"
                            size="sm"
                        >
                            Previous
                        </x-filament::button>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Page {{ $currentPage }}
                        </span>
                        <x-filament::button
                            wire:click="nextPage"
                            size="sm"
                        >
                            Next
                        </x-filament::button>
                    </div>
                </div>

                <div class="border rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-900" style="height: 800px;">
                    <iframe
                        src="{{ $this->getPdfUrl() }}#page={{ $currentPage }}"
                        class="w-full h-full"
                        frameborder="0"
                    ></iframe>
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <p class="font-medium mb-2">Instructions:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Review each page of the PDF</li>
                        <li>Add rooms and cabinet runs on the right →</li>
                        <li>Enter linear feet for each run</li>
                        <li>Select appropriate cabinet levels</li>
                        <li>Add any additional items (countertops, shelves, etc.)</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Data Entry Side --}}
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-lg font-medium mb-4">Manual Pricing Entry</h3>

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

            {{-- Live Pricing Summary --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Estimated Total</h4>
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    Enter items above to see live pricing calculation
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
