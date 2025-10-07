<x-filament-panels::page>
    {{-- Project Information Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Project</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $this->record->name }}</p>
            </div>

            @if($this->record->project_number)
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Project #</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $this->record->project_number }}</p>
            </div>
            @endif

            @if($this->record->partner)
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Customer</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $this->record->partner->name }}</p>
            </div>
            @endif

            @if($this->record->user)
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Project Manager</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $this->record->user->name }}</p>
            </div>
            @endif
        </div>

        @php
            $address = $this->record->addresses()->where('is_primary', true)->first()
                       ?? $this->record->addresses()->first();
        @endphp

        @if($address)
        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Project Address</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ $address->street1 }}
                @if($address->street2), {{ $address->street2 }}@endif
                @if($address->city || $address->state)
                    <br>{{ $address->city }}@if($address->city && $address->state), @endif{{ $address->state?->name }} {{ $address->postcode }}
                @endif
            </p>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-6 pb-24">
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

                <form wire:submit.prevent="createSalesOrder" class="relative">
                    {{ $this->form }}
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

    {{-- Sticky Footer with Actions --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center gap-3">
                <x-filament::button
                    type="button"
                    color="warning"
                    wire:click="tryAutomatic"
                    size="lg"
                >
                    <x-slot name="icon">
                        heroicon-o-sparkles
                    </x-slot>
                    Try Automatic Parsing
                </x-filament::button>

                <div class="flex gap-3">
                    <x-filament::button
                        type="button"
                        color="gray"
                        tag="a"
                        :href="\Webkul\Project\Filament\Resources\ProjectResource::getUrl('view', ['record' => $this->record])"
                        size="lg"
                    >
                        Cancel
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        color="success"
                        wire:click="createSalesOrder"
                        size="lg"
                    >
                        <x-slot name="icon">
                            heroicon-o-document-plus
                        </x-slot>
                        Create Sales Order
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
