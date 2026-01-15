<div class="opening-configurator">
    @if(!$section)
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <x-heroicon-o-square-3-stack-3d class="mx-auto mb-4 w-12 h-12 opacity-50" />
            <p>Select a section to configure its opening</p>
        </div>
    @else
        {{-- Header with section info --}}
        <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $section->name ?? 'Section ' . $section->section_number }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Opening: {{ $this->toFraction($openingWidth) }}"W x {{ $this->toFraction($openingHeight) }}"H
                </p>
            </div>
            <div class="flex gap-2 items-center">
                {{-- Validation status --}}
                @if($isValid && empty($validationWarnings))
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-800 dark:text-green-100">
                        <x-heroicon-s-check-circle class="mr-1 w-4 h-4" />
                        Valid
                    </span>
                @elseif($isValid && !empty($validationWarnings))
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-800 dark:text-yellow-100">
                        <x-heroicon-s-exclamation-triangle class="mr-1 w-4 h-4" />
                        {{ count($validationWarnings) }} Warning(s)
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-red-800 bg-red-100 rounded-full dark:bg-red-800 dark:text-red-100">
                        <x-heroicon-s-x-circle class="mr-1 w-4 h-4" />
                        {{ count($validationErrors) }} Error(s)
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-3">
            {{-- Visual Opening Representation --}}
            <div class="lg:col-span-2">
                <div class="p-4 bg-gray-100 rounded-lg dark:bg-gray-800">
                    <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Opening Visual</h4>

                    {{-- Space Usage Bar --}}
                    <div class="mb-4">
                        <div class="flex justify-between mb-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>Space Usage</span>
                            <span>{{ number_format($usagePercentage, 1) }}% ({{ $this->toFraction($consumedHeight) }}" / {{ $this->toFraction($openingHeight) }}")</span>
                        </div>
                        <div class="overflow-hidden h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                            <div
                                class="h-full transition-all duration-300 {{ $usagePercentage > 100 ? 'bg-red-500' : ($usagePercentage > 90 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                                style="width: {{ min($usagePercentage, 100) }}%"
                            ></div>
                        </div>
                        @if($remainingHeight > 0)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $this->toFraction($remainingHeight) }}" remaining
                            </p>
                        @elseif($remainingHeight < 0)
                            <p class="mt-1 text-xs text-red-500">
                                {{ $this->toFraction(abs($remainingHeight)) }}" overflow!
                            </p>
                        @endif
                    </div>

                    {{-- Opening Visual Box --}}
                    <div class="relative mx-auto bg-white rounded border-2 border-gray-400 dark:border-gray-600 dark:bg-gray-900" style="height: 400px; max-width: 300px;">
                        {{-- Opening label --}}
                        <div class="absolute right-0 left-0 -top-6 text-xs text-center text-gray-500">
                            {{ $this->toFraction($openingWidth) }}"
                        </div>
                        <div class="flex absolute top-0 bottom-0 -right-8 items-center">
                            <span class="text-xs text-gray-500 whitespace-nowrap transform -rotate-90">
                                {{ $this->toFraction($openingHeight) }}"
                            </span>
                        </div>

                        {{-- Components stacked from bottom --}}
                        @php
                            $scaleFactor = $openingHeight > 0 ? 380 / $openingHeight : 1;
                            $bottomOffset = 10; // px from bottom
                        @endphp

                        @foreach($components as $index => $component)
                            @php
                                $componentPixelHeight = max(20, ($component['height'] ?? 0) * $scaleFactor);
                                $componentPixelPosition = ($component['position'] ?? 0) * $scaleFactor;
                            @endphp
                            <div
                                class="flex absolute right-2 left-2 justify-between items-center px-2 rounded border transition-all hover:ring-2 hover:ring-blue-500"
                                style="
                                    bottom: {{ $bottomOffset + $componentPixelPosition }}px;
                                    height: {{ $componentPixelHeight }}px;
                                    background-color: {{ $this->getTypeColor($component['type']) }}20;
                                    border-color: {{ $this->getTypeColor($component['type']) }};
                                "
                                title="{{ $component['name'] }}: {{ $this->toFraction($component['height']) }}&quot;H at {{ $this->toFraction($component['position'] ?? 0) }}&quot; from bottom"
                            >
                                <div class="flex gap-1 items-center min-w-0">
                                    <span class="text-xs font-medium truncate" style="color: {{ $this->getTypeColor($component['type']) }}">
                                        {{ $component['name'] }}
                                    </span>
                                </div>
                                <div class="flex gap-1 items-center shrink-0">
                                    <span class="text-xs opacity-75">{{ $this->toFraction($component['height']) }}"</span>
                                    <button wire:click="moveUp({{ $component['id'] }}, '{{ $component['type'] }}')" class="p-0.5 rounded hover:bg-white/50" title="Move Up">
                                        <x-heroicon-m-chevron-up class="w-3 h-3" />
                                    </button>
                                    <button wire:click="moveDown({{ $component['id'] }}, '{{ $component['type'] }}')" class="p-0.5 rounded hover:bg-white/50" title="Move Down">
                                        <x-heroicon-m-chevron-down class="w-3 h-3" />
                                    </button>
                                    <button wire:click="removeComponent({{ $component['id'] }}, '{{ $component['type'] }}')" class="p-0.5 text-red-600 rounded hover:bg-red-100" title="Remove">
                                        <x-heroicon-m-x-mark class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        @if(empty($components))
                            <div class="flex absolute inset-0 justify-center items-center text-gray-400">
                                <p class="text-sm">No components</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Controls Panel --}}
            <div class="space-y-4">
                {{-- Add Component --}}
                <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                    <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Add Component</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            wire:click="openAddModal('drawer')"
                            class="flex flex-col items-center p-3 rounded-lg border-2 border-gray-300 border-dashed transition-colors dark:border-gray-600 hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                        >
                            <x-heroicon-o-inbox-stack class="w-6 h-6 text-blue-500" />
                            <span class="mt-1 text-xs">Drawer</span>
                        </button>
                        <button
                            wire:click="openAddModal('shelf')"
                            class="flex flex-col items-center p-3 rounded-lg border-2 border-gray-300 border-dashed transition-colors dark:border-gray-600 hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/20"
                        >
                            <x-heroicon-o-bars-3 class="w-6 h-6 text-green-500" />
                            <span class="mt-1 text-xs">Shelf</span>
                        </button>
                        <button
                            wire:click="openAddModal('door')"
                            class="flex flex-col items-center p-3 rounded-lg border-2 border-gray-300 border-dashed transition-colors dark:border-gray-600 hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20"
                        >
                            <x-heroicon-o-rectangle-group class="w-6 h-6 text-purple-500" />
                            <span class="mt-1 text-xs">Door</span>
                        </button>
                        <button
                            wire:click="openAddModal('pullout')"
                            class="flex flex-col items-center p-3 rounded-lg border-2 border-gray-300 border-dashed transition-colors dark:border-gray-600 hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                        >
                            <x-heroicon-o-arrow-right-on-rectangle class="w-6 h-6 text-amber-500" />
                            <span class="mt-1 text-xs">Pullout</span>
                        </button>
                    </div>
                </div>

                {{-- Layout Strategy --}}
                <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                    <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Auto-Arrange</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Strategy</label>
                            <select
                                wire:model="layoutStrategy"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            >
                                @foreach($this->getLayoutStrategies() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button
                            wire:click="autoArrange"
                            class="px-4 py-2 w-full text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <x-heroicon-m-arrows-up-down class="inline mr-1 w-4 h-4" />
                            Auto-Arrange
                        </button>
                    </div>
                </div>

                {{-- Gap Settings --}}
                <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                    <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Gap Settings</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Top Reveal (inches)</label>
                            <input
                                type="number"
                                wire:model.defer="topReveal"
                                step="0.0625"
                                min="0"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            />
                        </div>
                        <div>
                            <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Bottom Reveal (inches)</label>
                            <input
                                type="number"
                                wire:model.defer="bottomReveal"
                                step="0.0625"
                                min="0"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            />
                        </div>
                        <div>
                            <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Component Gap (inches)</label>
                            <input
                                type="number"
                                wire:model.defer="componentGap"
                                step="0.0625"
                                min="0"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            />
                        </div>
                        <button
                            wire:click="updateGapSettings"
                            class="px-4 py-2 w-full text-sm font-medium text-gray-700 bg-gray-100 rounded-lg dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Update Gaps
                        </button>
                    </div>
                </div>

                {{-- Validation Messages --}}
                @if(!empty($validationErrors) || !empty($validationWarnings))
                    <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                        <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Validation</h4>
                        @foreach($validationErrors as $error)
                            <div class="flex gap-2 items-start mb-2 text-sm text-red-600 dark:text-red-400">
                                <x-heroicon-s-x-circle class="mt-0.5 w-4 h-4 shrink-0" />
                                <span>{{ $error }}</span>
                            </div>
                        @endforeach
                        @foreach($validationWarnings as $warning)
                            <div class="flex gap-2 items-start mb-2 text-sm text-yellow-600 dark:text-yellow-400">
                                <x-heroicon-s-exclamation-triangle class="mt-0.5 w-4 h-4 shrink-0" />
                                <span>{{ $warning }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Add Component Modal --}}
        @if($showAddModal)
            <div class="flex fixed inset-0 z-50 justify-center items-center bg-black/50">
                <div class="p-6 mx-4 w-full max-w-md bg-white rounded-xl shadow-xl dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        Add {{ ucfirst($addComponentType) }}
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Component Type
                            </label>
                            <select
                                wire:model="addComponentType"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            >
                                <option value="drawer">Drawer</option>
                                <option value="shelf">Shelf</option>
                                <option value="door">Door</option>
                                <option value="pullout">Pullout</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Height (inches)
                            </label>
                            <input
                                type="number"
                                wire:model="addComponentHeight"
                                step="0.125"
                                min="0"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Remaining space: {{ $this->toFraction($remainingHeight) }}"
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end mt-6">
                        <button
                            wire:click="$set('showAddModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="addComponent"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                        >
                            Add {{ ucfirst($addComponentType) }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
