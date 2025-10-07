<x-filament-panels::page>
    {{-- Project Information Header --}}
    <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-gray-800 dark:to-gray-700 rounded-lg shadow-sm border border-primary-200 dark:border-gray-600 p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Project Information</h3>
            <x-filament::button
                type="button"
                color="primary"
                wire:click="parseCoverPage"
                icon="heroicon-o-document-magnifying-glass"
                size="xs"
                outlined
            >
                Extract from PDF
            </x-filament::button>
        </div>

        <div class="flex items-start justify-between gap-6">
            <div class="flex-1 grid grid-cols-2 gap-x-8 gap-y-3">
                <div>
                    <p class="text-xs font-semibold text-primary-700 dark:text-primary-300 uppercase tracking-wider mb-1">Project</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ $this->record->name }}</p>
                </div>

                @if($this->record->project_number)
                <div>
                    <p class="text-xs font-semibold text-primary-700 dark:text-primary-300 uppercase tracking-wider mb-1">Project Code</p>
                    <p class="text-sm font-mono font-medium text-gray-900 dark:text-white">{{ $this->record->project_number }}</p>
                </div>
                @endif

                @if($this->record->partner)
                <div>
                    <p class="text-xs font-semibold text-primary-700 dark:text-primary-300 uppercase tracking-wider mb-1">Customer</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $this->record->partner->name }}</p>
                </div>
                @endif

                @if($this->record->user)
                <div>
                    <p class="text-xs font-semibold text-primary-700 dark:text-primary-300 uppercase tracking-wider mb-1">Manager</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $this->record->user->name }}</p>
                </div>
                @endif
            </div>

            @php
                $address = $this->record->addresses()->where('is_primary', true)->first()
                           ?? $this->record->addresses()->first();
            @endphp

            @if($address)
            <div class="flex-shrink-0 bg-white dark:bg-gray-800 rounded-lg px-4 py-3 border border-gray-200 dark:border-gray-600">
                <p class="text-xs font-semibold text-primary-700 dark:text-primary-300 uppercase tracking-wider mb-1">Address</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    {{ $address->street1 }}<br>
                    {{ $address->city }}, {{ $address->state?->name }} {{ $address->postcode }}
                </p>
            </div>
            @endif
        </div>
    </div>

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
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">Manual Pricing Entry</h3>

                    <div class="flex gap-2">
                        <x-filament::button
                            type="button"
                            color="warning"
                            wire:click="tryAutomatic"
                            icon="heroicon-o-sparkles"
                            size="sm"
                            outlined
                        >
                            Auto-Parse
                        </x-filament::button>

                        <x-filament::button
                            type="button"
                            color="gray"
                            tag="a"
                            :href="\Webkul\Project\Filament\Resources\ProjectResource::getUrl('view', ['record' => $this->record])"
                            size="sm"
                            outlined
                        >
                            Cancel
                        </x-filament::button>

                        <x-filament::button
                            type="button"
                            color="success"
                            wire:click="createSalesOrder"
                            icon="heroicon-o-document-check"
                            size="sm"
                        >
                            Create Order
                        </x-filament::button>
                    </div>
                </div>

                <form wire:submit.prevent="createSalesOrder" class="relative">
                    {{ $this->form }}

                    @if(!empty($coverPageData) && array_filter($coverPageData))
                    <div class="mt-4 flex justify-end">
                        <x-filament::button
                            type="button"
                            color="success"
                            wire:click="saveExtractedData"
                            icon="heroicon-o-arrow-down-tray"
                            size="sm"
                        >
                            Save to Project
                        </x-filament::button>
                    </div>
                    @endif
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
