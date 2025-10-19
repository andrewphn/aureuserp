{{-- Filament-styled Slideover for Annotation Editing --}}
<div>
    @if($showModal)
    {{-- Backdrop with Filament styling --}}
    <div
        class="fixed inset-0 z-50 flex items-center justify-end"
        style="background-color: rgba(0, 0, 0, 0.3);"
        wire:click="cancel"
    >
        {{-- Slideover Panel - Filament native styling --}}
        <div
            class="bg-white dark:bg-gray-900 h-full w-full max-w-md shadow-2xl overflow-y-auto border-l border-gray-200 dark:border-gray-700"
            @click.stop
            x-data
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="transform translate-x-full"
            x-transition:enter-end="transform translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="transform translate-x-0"
            x-transition:leave-end="transform translate-x-full"
        >
            {{-- Header --}}
            <div class="sticky top-0 z-10 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-pencil-square" class="h-5 w-5" style="color: var(--primary-600);" />
                        Edit Annotation
                    </h2>
                    <button
                        type="button"
                        wire:click="cancel"
                        class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition-colors"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6" />
                    </button>
                </div>
            </div>

            {{-- Form Content with Filament Form --}}
            <div class="p-6">
                {{-- Type Display --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                    <div class="text-sm font-semibold px-3 py-2 rounded-lg border" style="background-color: var(--gray-50); border-color: var(--gray-200); color: var(--gray-700);">
                        @if($annotationType === 'location')
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-map-pin" class="h-4 w-4" style="color: var(--info-600);" />
                                Location
                            </div>
                        @elseif($annotationType === 'cabinet_run')
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-squares-2x2" class="h-4 w-4" style="color: var(--success-600);" />
                                Cabinet Run
                            </div>
                        @elseif($annotationType === 'cabinet')
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-archive-box" class="h-4 w-4" style="color: var(--warning-600);" />
                                Cabinet
                            </div>
                        @else
                            {{ ucfirst(str_replace('_', ' ', $annotationType ?? 'unknown')) }}
                        @endif
                    </div>
                </div>

                {{-- Filament Form Components --}}
                {{ $this->form }}

                {{-- Action Buttons - Sticky Footer with Filament Actions --}}
                <div class="sticky bottom-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6 -mx-6 px-6 pb-6">
                    <div class="flex justify-between items-center gap-3">
                        {{-- Delete on the left --}}
                        <div>
                            {{ ($this->deleteAction)(['size' => 'md']) }}
                        </div>
                        {{-- Cancel and Save on the right --}}
                        <div class="flex gap-3">
                            {{ ($this->cancelAction)(['size' => 'md']) }}
                            {{ ($this->saveAction)(['size' => 'md']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
