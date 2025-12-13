<div
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6"
    x-data="{ show: false }"
    x-init="$nextTick(() => show = true)"
    x-show="show"
    x-on:keydown.escape.window="$wire.closeModal()"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    {{-- Backdrop --}}
    <div
        class="fixed inset-0 bg-gray-900/60 dark:bg-gray-950/80 backdrop-blur-sm"
        x-on:click="$wire.closeModal()"
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- Modal Container --}}
    <div
        class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-2xl ring-1 ring-gray-900/5 dark:ring-white/10 transform"
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        x-on:click.stop
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ $this->getModalHeading() }}
            </h2>
            <button
                wire:click="closeModal"
                type="button"
                class="p-1.5 -m-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Body --}}
        <div class="px-5 py-4 space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto overscroll-contain">

            {{-- Room Form --}}
            @if($modalEntityType === 'room')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Room Name <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="formData.name"
                            class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow"
                            placeholder="e.g., Kitchen, Master Bathroom"
                            autofocus
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Room Type
                            </label>
                            <select
                                wire:model="formData.room_type"
                                class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                @foreach($roomTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Floor Number
                            </label>
                            <input
                                type="number"
                                wire:model="formData.floor_number"
                                class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                min="0"
                                max="99"
                            />
                        </div>
                    </div>
                </div>
            @endif

            {{-- Room Location Form --}}
            @if($modalEntityType === 'room_location')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Location Name <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="formData.name"
                            class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="e.g., Sink Wall, Island, Pantry Alcove"
                            autofocus
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Location Type
                            </label>
                            <select
                                wire:model="formData.location_type"
                                class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                @foreach($locationTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Cabinet Level
                            </label>
                            <select
                                wire:model="formData.cabinet_level"
                                class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                @foreach($pricingTiers as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            <strong>Pricing by Level:</strong> L1=$225/LF • L2=$298/LF • L3=$348/LF • L4=$425/LF • L5=$550/LF
                        </p>
                    </div>
                </div>
            @endif

            {{-- Cabinet Run Form --}}
            @if($modalEntityType === 'cabinet_run')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Run Name <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="formData.name"
                            class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="e.g., Base Run 1, Upper Cabinets"
                            autofocus
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Run Type
                        </label>
                        <select
                            wire:model="formData.run_type"
                            class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                            @foreach($runTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            {{-- Cabinet Form --}}
            @if($modalEntityType === 'cabinet')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Cabinet Name/Number
                        </label>
                        <input
                            type="text"
                            wire:model.live.debounce.150ms="formData.name"
                            class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="e.g., B24, W3012, SB36"
                            autofocus
                        />
                        <p class="mt-1 text-xs text-gray-500">Smart detection: B24, SB36, W3012, DB24, T24, V30</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Cabinet Type
                            </label>
                            <select
                                wire:model="formData.cabinet_type"
                                class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                @foreach($cabinetTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Quantity
                            </label>
                            <input
                                type="number"
                                wire:model.live="formData.quantity"
                                class="w-full h-10 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                min="1"
                            />
                        </div>
                    </div>

                    {{-- Dimensions --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Dimensions (inches)
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Width</label>
                                <input
                                    type="number"
                                    wire:model.live="formData.length_inches"
                                    class="w-full h-9 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    step="0.5"
                                    placeholder="24"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Depth</label>
                                <input
                                    type="number"
                                    wire:model="formData.depth_inches"
                                    class="w-full h-9 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    step="0.5"
                                    placeholder="24"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Height</label>
                                <input
                                    type="number"
                                    wire:model="formData.height_inches"
                                    class="w-full h-9 px-3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    step="0.5"
                                    placeholder="30"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- LF Calculator --}}
                    @if(!empty($formData['length_inches']))
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Linear Feet:</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400">
                                    {{ number_format(($formData['length_inches'] / 12) * ($formData['quantity'] ?? 1), 2) }} LF
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 rounded-b-xl">
            <button
                wire:click="closeModal"
                type="button"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                Cancel
            </button>
            <button
                wire:click="save"
                type="button"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 rounded-lg transition-colors shadow-sm"
            >
                {{ $modalMode === 'create' ? 'Add' : 'Save Changes' }}
            </button>
        </div>
    </div>
</div>
