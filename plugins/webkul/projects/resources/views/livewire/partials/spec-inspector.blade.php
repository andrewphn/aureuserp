{{-- Dynamic Inspector Panel --}}
{{-- Content changes based on selection: Room → Location → Run → Cabinets --}}

<div class="space-y-4">

    {{-- ================================================================== --}}
    {{-- WHEN A CABINET IS SELECTED: Show cabinet detail with sections --}}
    {{-- ================================================================== --}}
    <template x-if="selectedRun && selectedCabinetIndex !== null">
        @include('webkul-project::livewire.partials.spec-cabinet-detail')
    </template>

    {{-- ================================================================== --}}
    {{-- WHEN A RUN IS SELECTED (but no cabinet): Show run details + cabinets table --}}
    {{-- ================================================================== --}}
    <template x-if="selectedRun && selectedCabinetIndex === null">
        <div class="space-y-4">
            {{-- Run Edit Form --}}
            <div class="rounded-lg border p-4 bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                        <div class="p-1.5 rounded bg-purple-100 dark:bg-purple-900/50">
                            <x-heroicon-s-squares-2x2 class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                        </div>
                        <span x-text="selectedRun.name || 'Cabinet Run'"></span>
                    </h3>
                    <div class="flex items-center gap-1">
                        <button
                            @click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex })"
                            class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30"
                            title="Edit Run"
                        >
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            @click="$wire.mountAction('duplicateNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex, nodeType: 'cabinet_run' })"
                            class="p-1.5 rounded-lg text-gray-500 hover:text-purple-600 transition-colors hover:bg-purple-50 dark:hover:bg-purple-900/30"
                            title="Duplicate Run"
                        >
                            <x-heroicon-m-document-duplicate class="w-4 h-4" />
                        </button>
                        <div class="w-px h-4 mx-0.5 bg-gray-200 dark:bg-gray-600"></div>
                        <button
                            @click="$wire.mountAction('deleteNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex, nodeType: 'cabinet_run' })"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 transition-colors hover:bg-red-50 dark:hover:bg-red-900/30"
                            title="Delete Run"
                        >
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Run Stats Row --}}
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-600 dark:text-gray-200">Cabinets:</span>
                        <strong class="text-gray-900 dark:text-gray-100" x-text="(selectedRun.children || []).length"></strong>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-600 dark:text-gray-200">Linear Feet:</span>
                        <strong class="tabular-nums text-blue-600 dark:text-blue-400" x-text="(selectedRun.linear_feet || 0).toFixed(2) + ' LF'"></strong>
                    </div>
                </div>
            </div>

            {{-- Pricing Configuration - Collapsible (collapsed by default) --}}
            <div
                x-data="{ pricingOpen: false }"
                class="rounded-lg border overflow-hidden bg-gray-100 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600"
            >
                {{-- Pricing Header (always visible) --}}
                <button
                    @click="pricingOpen = !pricingOpen"
                    class="w-full px-4 py-3 flex items-center justify-between transition-colors hover:bg-gray-200/50 dark:hover:bg-gray-600/50"
                >
                    <div class="flex items-center gap-3">
                        <div class="p-1.5 rounded bg-amber-100 dark:bg-amber-900/40">
                            <x-heroicon-m-currency-dollar class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="text-left">
                            <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                $<span x-text="getPricePerLF(
                                    selectedRun.cabinet_level || selectedLocation?.cabinet_level || selectedRoom?.cabinet_level || '3',
                                    selectedRun.material_category || selectedLocation?.material_category || selectedRoom?.material_category || 'stain_grade',
                                    selectedRun.finish_option || selectedLocation?.finish_option || selectedRoom?.finish_option || 'unfinished'
                                ).toFixed(2)"></span>/LF
                            </div>
                            <div class="text-xs flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                <span>Level <span x-text="selectedRun.cabinet_level || selectedLocation?.cabinet_level || selectedRoom?.cabinet_level || '3'"></span></span>
                                <template x-if="!selectedRun.cabinet_level">
                                    <span class="flex items-center gap-0.5 text-amber-500">
                                        <x-heroicon-m-link class="w-3 h-3" />
                                        <span class="text-[10px]">inherited</span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="pricingOpen ? 'Hide' : 'Edit'"></span>
                        <x-heroicon-m-chevron-down
                            :class="pricingOpen ? 'rotate-180' : ''"
                            class="w-4 h-4 transition-transform text-gray-500 dark:text-gray-400"
                        />
                    </div>
                </button>

                {{-- Pricing Details (collapsible) --}}
                <div x-show="pricingOpen" x-collapse class="px-4 pb-4 space-y-3">
                    {{-- Cabinet Level Select --}}
                    <div class="flex items-center gap-3">
                        <label class="text-sm w-20 flex-shrink-0 font-medium text-gray-600 dark:text-gray-300">Level</label>
                        <select
                            x-model="selectedRun.cabinet_level"
                            @change="updateRunPricing('cabinet_level', $event.target.value)"
                            class="flex-1 text-sm px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">Inherit from location</option>
                            <template x-for="(label, key) in pricingTiers" :key="key">
                                <option :value="key" x-text="label"></option>
                            </template>
                        </select>
                        <template x-if="!selectedRun.cabinet_level">
                            <span class="text-xs flex items-center gap-1 text-amber-600 dark:text-amber-400" title="Inherited from location">
                                <x-heroicon-m-link class="w-3.5 h-3.5" />
                                <span>L<span x-text="selectedLocation?.cabinet_level || selectedRoom?.cabinet_level || '3'"></span></span>
                            </span>
                        </template>
                    </div>

                    {{-- Material Category Select --}}
                    <div class="flex items-center gap-3">
                        <label class="text-sm w-20 flex-shrink-0 font-medium text-gray-600 dark:text-gray-300">Material</label>
                        <select
                            x-model="selectedRun.material_category"
                            @change="updateRunPricing('material_category', $event.target.value)"
                            class="flex-1 text-sm px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">Inherit from location</option>
                            <template x-for="(label, key) in materialOptions" :key="key">
                                <option :value="key" x-text="label"></option>
                            </template>
                        </select>
                        <template x-if="!selectedRun.material_category">
                            <span class="text-xs flex items-center gap-1 capitalize text-amber-600 dark:text-amber-400" title="Inherited from location">
                                <x-heroicon-m-link class="w-3.5 h-3.5" />
                                <span x-text="(selectedLocation?.material_category || selectedRoom?.material_category || 'stain_grade').replace('_', ' ')"></span>
                            </span>
                        </template>
                    </div>

                    {{-- Finish Option Select --}}
                    <div class="flex items-center gap-3">
                        <label class="text-sm w-20 flex-shrink-0 font-medium text-gray-600 dark:text-gray-300">Finish</label>
                        <select
                            x-model="selectedRun.finish_option"
                            @change="updateRunPricing('finish_option', $event.target.value)"
                            class="flex-1 text-sm px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">Inherit from location</option>
                            <template x-for="(label, key) in finishOptions" :key="key">
                                <option :value="key" x-text="label"></option>
                            </template>
                        </select>
                        <template x-if="!selectedRun.finish_option">
                            <span class="text-xs flex items-center gap-1 capitalize text-amber-600 dark:text-amber-400" title="Inherited from location">
                                <x-heroicon-m-link class="w-3.5 h-3.5" />
                                <span x-text="(selectedLocation?.finish_option || selectedRoom?.finish_option || 'unfinished').replace('_', ' ')"></span>
                            </span>
                        </template>
                    </div>

                    <p class="text-xs pt-2 border-t border-gray-200 dark:border-gray-600 text-gray-400 dark:text-gray-500">
                        <x-heroicon-m-information-circle class="w-3.5 h-3.5 inline mr-1" />
                        Override location defaults for this run. Leave blank to inherit.
                    </p>
                </div>
            </div>

            {{-- Cabinets Table --}}
            <div class="rounded-lg border overflow-hidden bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Cabinets</h4>
                    <button
                        @click="$wire.mountAction('createCabinet', { runPath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex })"
                        class="text-xs hover:text-primary-700 font-medium flex items-center gap-1 text-primary-600 dark:text-primary-400"
                    >
                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                        Add Cabinet
                    </button>
                </div>

                <div class="overflow-x-auto">
                    @include('webkul-project::livewire.partials.spec-cabinet-table')
                </div>

                {{-- Quick Add Input --}}
                <div class="px-4 py-3 border-t bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            x-ref="quickAddInput"
                            placeholder="Quick add: B24, W30, SB36..."
                            @keydown.enter.prevent.stop="addCabinetFromCode($event.target.value); $event.target.value = ''"
                            class="flex-1 px-3 py-2 text-sm border rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                        />
                        <button
                            @click="addCabinetFromCode($refs.quickAddInput.value); $refs.quickAddInput.value = ''"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors"
                        >
                            Add
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Codes: B=Base, W=Wall, SB=Sink Base, T=Tall, V=Vanity + width (e.g., B24, W3012)
                    </p>
                </div>
            </div>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- WHEN A LOCATION IS SELECTED (but no run): Show location + runs list --}}
    {{-- ================================================================== --}}
    <template x-if="selectedLocation && !selectedRun">
        <div class="space-y-4">
            {{-- Location Edit Form --}}
            <div class="rounded-lg border p-4 bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                        <div class="p-1.5 rounded bg-green-100 dark:bg-green-900/50">
                            <x-heroicon-s-map-pin class="w-4 h-4 text-green-600 dark:text-green-400" />
                        </div>
                        <span x-text="selectedLocation.name || 'Location'"></span>
                    </h3>
                    <div class="flex items-center gap-1">
                        <button
                            @click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex })"
                            class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30"
                            title="Edit Location"
                        >
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            @click="$wire.mountAction('duplicateNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex, nodeType: 'room_location' })"
                            class="p-1.5 rounded-lg text-gray-500 hover:text-purple-600 transition-colors hover:bg-purple-50 dark:hover:bg-purple-900/30"
                            title="Duplicate Location"
                        >
                            <x-heroicon-m-document-duplicate class="w-4 h-4" />
                        </button>
                        <div class="w-px h-4 mx-0.5 bg-gray-200 dark:bg-gray-600"></div>
                        <button
                            @click="$wire.mountAction('deleteNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex, nodeType: 'room_location' })"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 transition-colors hover:bg-red-50 dark:hover:bg-red-900/30"
                            title="Delete Location"
                        >
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Location Details --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm mb-3">
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-300">Runs</span>
                        <strong class="text-gray-900 dark:text-gray-100" x-text="(selectedLocation.children || []).length"></strong>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-300">Linear Feet</span>
                        <strong class="tabular-nums text-blue-600 dark:text-blue-400" x-text="(selectedLocation.linear_feet || 0).toFixed(2) + ' LF'"></strong>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-300">Estimated</span>
                        <strong class="tabular-nums text-green-600 dark:text-green-400">$<span x-text="(selectedLocation.estimated_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span></strong>
                    </div>
                </div>

                {{-- Pricing Configuration - Collapsible (collapsed by default) --}}
                <div
                    x-data="{ pricingOpen: false }"
                    class="rounded-lg border overflow-hidden bg-gray-100 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600"
                >
                    {{-- Pricing Header (always visible) --}}
                    <button
                        @click="pricingOpen = !pricingOpen"
                        class="w-full px-4 py-3 flex items-center justify-between transition-colors hover:bg-gray-200/50 dark:hover:bg-gray-600/50"
                    >
                        <div class="flex items-center gap-3">
                            <div class="p-1.5 rounded bg-amber-100 dark:bg-amber-900/40">
                                <x-heroicon-m-currency-dollar class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                    $<span x-text="getPricePerLF(
                                        selectedLocation.cabinet_level || selectedRoom?.cabinet_level || '3',
                                        selectedLocation.material_category || selectedRoom?.material_category || 'stain_grade',
                                        selectedLocation.finish_option || selectedRoom?.finish_option || 'unfinished'
                                    ).toFixed(2)"></span>/LF
                                </div>
                                <div class="text-xs flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                    <span>Level <span x-text="selectedLocation.cabinet_level || selectedRoom?.cabinet_level || '3'"></span></span>
                                    <template x-if="!selectedLocation.cabinet_level">
                                        <span class="flex items-center gap-0.5 text-amber-500">
                                            <x-heroicon-m-link class="w-3 h-3" />
                                            <span class="text-[10px]">inherited</span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="pricingOpen ? 'Hide' : 'Edit'"></span>
                            <x-heroicon-m-chevron-down
                                :class="pricingOpen ? 'rotate-180' : ''"
                                class="w-4 h-4 transition-transform text-gray-500 dark:text-gray-400"
                            />
                        </div>
                    </button>

                    {{-- Pricing Details (collapsible) --}}
                    <div x-show="pricingOpen" x-collapse class="px-4 pb-4 space-y-3">
                        {{-- Cabinet Level Select --}}
                        <div class="flex items-center gap-3">
                            <label class="text-sm w-20 flex-shrink-0 font-medium text-gray-600 dark:text-gray-300">Level</label>
                            <select
                                x-model="selectedLocation.cabinet_level"
                                @change="updateLocationPricing('cabinet_level', $event.target.value)"
                                class="flex-1 text-sm px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Inherit from room</option>
                                <template x-for="(label, key) in pricingTiers" :key="key">
                                    <option :value="key" x-text="label"></option>
                                </template>
                            </select>
                            <template x-if="!selectedLocation.cabinet_level">
                                <span class="text-xs flex items-center gap-1 text-amber-600 dark:text-amber-400" title="Inherited from room">
                                    <x-heroicon-m-link class="w-3.5 h-3.5" />
                                    <span>L<span x-text="selectedRoom?.cabinet_level || '3'"></span></span>
                                </span>
                            </template>
                        </div>

                        {{-- Material Category Select --}}
                        <div class="flex items-center gap-3">
                            <label class="text-sm w-20 flex-shrink-0 font-medium text-gray-600 dark:text-gray-300">Material</label>
                            <select
                                x-model="selectedLocation.material_category"
                                @change="updateLocationPricing('material_category', $event.target.value)"
                                class="flex-1 text-sm px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Inherit from room</option>
                                <template x-for="(label, key) in materialOptions" :key="key">
                                    <option :value="key" x-text="label"></option>
                                </template>
                            </select>
                            <template x-if="!selectedLocation.material_category">
                                <span class="text-xs flex items-center gap-1 capitalize text-amber-600 dark:text-amber-400" title="Inherited from room">
                                    <x-heroicon-m-link class="w-3.5 h-3.5" />
                                    <span x-text="(selectedRoom?.material_category || 'stain_grade').replace('_', ' ')"></span>
                                </span>
                            </template>
                        </div>

                        {{-- Finish Option Select --}}
                        <div class="flex items-center gap-3">
                            <label class="text-sm w-20 flex-shrink-0 font-medium text-gray-600 dark:text-gray-300">Finish</label>
                            <select
                                x-model="selectedLocation.finish_option"
                                @change="updateLocationPricing('finish_option', $event.target.value)"
                                class="flex-1 text-sm px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Inherit from room</option>
                                <template x-for="(label, key) in finishOptions" :key="key">
                                    <option :value="key" x-text="label"></option>
                                </template>
                            </select>
                            <template x-if="!selectedLocation.finish_option">
                                <span class="text-xs flex items-center gap-1 capitalize text-amber-600 dark:text-amber-400" title="Inherited from room">
                                    <x-heroicon-m-link class="w-3.5 h-3.5" />
                                    <span x-text="(selectedRoom?.finish_option || 'unfinished').replace('_', ' ')"></span>
                                </span>
                            </template>
                        </div>

                        <p class="text-xs pt-2 border-t border-gray-200 dark:border-gray-600 text-gray-400 dark:text-gray-500">
                            <x-heroicon-m-information-circle class="w-3.5 h-3.5 inline mr-1" />
                            Override room defaults for this location. Leave blank to inherit.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Runs List --}}
            <div class="rounded-lg border overflow-hidden bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Cabinet Runs</h4>
                    <button
                        @click="$wire.mountAction('createRun', { locationPath: selectedRoomIndex + '.children.' + selectedLocationIndex })"
                        class="text-xs hover:text-primary-700 font-medium flex items-center gap-1 text-primary-600 dark:text-primary-400"
                    >
                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                        Add Run
                    </button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-if="!(selectedLocation.children || []).length">
                        <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-400" />
                            </div>
                            No cabinet runs yet.
                            <button
                                @click="$wire.mountAction('createRun', { locationPath: selectedRoomIndex + '.children.' + selectedLocationIndex })"
                                class="text-primary-600 hover:text-primary-700 font-medium ml-1"
                            >Add your first run</button>
                        </div>
                    </template>

                    <template x-for="(run, runIdx) in (selectedLocation.children || [])" :key="run.id || runIdx">
                        <div
                            @click="selectRun(selectedRoomIndex, selectedLocationIndex, runIdx)"
                            class="flex items-center justify-between px-4 py-3 cursor-pointer group hover:bg-gray-50 dark:hover:bg-gray-700/30"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-1.5 rounded bg-purple-100 dark:bg-purple-900/50">
                                    <x-heroicon-s-squares-2x2 class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100" x-text="run.name || 'Untitled Run'"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <span x-text="(run.children || []).length"></span> cabinets
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium tabular-nums text-blue-600 dark:text-blue-400" x-text="(run.linear_feet || 0).toFixed(2) + ' LF'"></span>
                                <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- WHEN A ROOM IS SELECTED (but no location): Show room + locations list --}}
    {{-- ================================================================== --}}
    <template x-if="selectedRoom && !selectedLocation">
        <div class="space-y-4">
            {{-- Room Edit Form --}}
            <div class="rounded-lg border p-4 bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                        <div class="p-1.5 rounded bg-blue-100 dark:bg-blue-900/50">
                            <x-heroicon-s-home class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <span x-text="selectedRoom.name || 'Room'"></span>
                    </h3>
                    <div class="flex items-center gap-1">
                        <button
                            @click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex.toString() })"
                            class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30"
                            title="Edit Room"
                        >
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            @click="$wire.mountAction('duplicateNode', { nodePath: selectedRoomIndex.toString(), nodeType: 'room' })"
                            class="p-1.5 rounded-lg text-gray-500 hover:text-purple-600 transition-colors hover:bg-purple-50 dark:hover:bg-purple-900/30"
                            title="Duplicate Room"
                        >
                            <x-heroicon-m-document-duplicate class="w-4 h-4" />
                        </button>
                        <div class="w-px h-4 mx-0.5 bg-gray-200 dark:bg-gray-600"></div>
                        <button
                            @click="$wire.mountAction('deleteNode', { nodePath: selectedRoomIndex.toString(), nodeType: 'room' })"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 transition-colors hover:bg-red-50 dark:hover:bg-red-900/30"
                            title="Delete Room"
                        >
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Room Details --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm mb-3">
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-300">Type</span>
                        <strong class="capitalize text-gray-900 dark:text-gray-100" x-text="selectedRoom.room_type || 'Other'"></strong>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-300">Locations</span>
                        <strong class="text-gray-900 dark:text-gray-100" x-text="(selectedRoom.children || []).length"></strong>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-300">Linear Feet</span>
                        <strong class="tabular-nums text-blue-600 dark:text-blue-400" x-text="(selectedRoom.linear_feet || 0).toFixed(2) + ' LF'"></strong>
                    </div>
                </div>

                {{-- Room Default Pricing - Sets defaults for all children --}}
                <div class="rounded-lg border p-3 text-sm bg-gray-100 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-currency-dollar class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                            <span class="font-medium text-xs uppercase tracking-wide text-gray-700 dark:text-gray-200">Default Pricing</span>
                        </div>
                        <div class="font-bold text-green-600 dark:text-green-400">
                            $<span x-text="getPricePerLF(
                                selectedRoom.cabinet_level || '3',
                                selectedRoom.material_category || 'stain_grade',
                                selectedRoom.finish_option || 'unfinished'
                            ).toFixed(2)"></span>/LF
                        </div>
                    </div>
                    <div class="space-y-2">
                        {{-- Cabinet Level Select --}}
                        <div class="flex items-center gap-2">
                            <label class="text-xs w-16 flex-shrink-0 text-gray-500 dark:text-gray-400">Level</label>
                            <select
                                x-model="selectedRoom.cabinet_level"
                                @change="updateRoomPricing('cabinet_level', $event.target.value)"
                                class="flex-1 text-xs px-2 py-1.5 border rounded focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                            >
                                <template x-for="(label, key) in pricingTiers" :key="key">
                                    <option :value="key" x-text="label" :selected="key === (selectedRoom.cabinet_level || '3')"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Material Category Select --}}
                        <div class="flex items-center gap-2">
                            <label class="text-xs w-16 flex-shrink-0 text-gray-500 dark:text-gray-400">Material</label>
                            <select
                                x-model="selectedRoom.material_category"
                                @change="updateRoomPricing('material_category', $event.target.value)"
                                class="flex-1 text-xs px-2 py-1.5 border rounded focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                            >
                                <template x-for="(label, key) in materialOptions" :key="key">
                                    <option :value="key" x-text="label" :selected="key === (selectedRoom.material_category || 'stain_grade')"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Finish Option Select --}}
                        <div class="flex items-center gap-2">
                            <label class="text-xs w-16 flex-shrink-0 text-gray-500 dark:text-gray-400">Finish</label>
                            <select
                                x-model="selectedRoom.finish_option"
                                @change="updateRoomPricing('finish_option', $event.target.value)"
                                class="flex-1 text-xs px-2 py-1.5 border rounded focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-gray-100"
                            >
                                <template x-for="(label, key) in finishOptions" :key="key">
                                    <option :value="key" x-text="label" :selected="key === (selectedRoom.finish_option || 'unfinished')"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <p class="text-[10px] mt-2 italic text-gray-400 dark:text-gray-500">
                        These defaults apply to all locations in this room unless overridden.
                    </p>
                </div>
            </div>

            {{-- Locations List --}}
            <div class="rounded-lg border overflow-hidden bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Wall Locations</h4>
                    <button
                        @click="$wire.mountAction('createLocation', { roomPath: selectedRoomIndex.toString() })"
                        class="text-xs hover:text-primary-700 font-medium flex items-center gap-1 text-primary-600 dark:text-primary-400"
                    >
                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                        Add Location
                    </button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-if="!(selectedRoom.children || []).length">
                        <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-400" />
                            </div>
                            No wall locations yet.
                            <button
                                @click="$wire.mountAction('createLocation', { roomPath: selectedRoomIndex.toString() })"
                                class="text-primary-600 hover:text-primary-700 font-medium ml-1"
                            >Add your first location</button>
                        </div>
                    </template>

                    <template x-for="(location, locIdx) in (selectedRoom.children || [])" :key="location.id || locIdx">
                        <div
                            @click="selectLocation(selectedRoomIndex, locIdx)"
                            class="flex items-center justify-between px-4 py-3 cursor-pointer group hover:bg-gray-50 dark:hover:bg-gray-700/30"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-1.5 rounded bg-green-100 dark:bg-green-900/50">
                                    <x-heroicon-s-map-pin class="w-4 h-4 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100" x-text="location.name || 'Untitled'"></div>
                                    <div class="text-xs flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                        <span>L<span x-text="location.cabinet_level || selectedRoom.cabinet_level || '3'"></span></span>
                                        <span :class="!location.cabinet_level ? 'text-gray-400 dark:text-gray-600' : ''" class="text-[10px]" x-show="!location.cabinet_level">→</span>
                                        <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                        <span x-text="(location.children || []).length"></span> runs
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium tabular-nums text-blue-600 dark:text-blue-400" x-text="(location.linear_feet || 0).toFixed(2) + ' LF'"></span>
                                <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- EMPTY STATE: No selection --}}
    {{-- ================================================================== --}}
    <template x-if="!selectedRoom">
        <div class="flex flex-col items-center justify-center h-full py-16 text-center">
            <div class="w-16 h-16 mb-4 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                <x-heroicon-o-cursor-arrow-rays class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-base font-medium mb-1 text-gray-900 dark:text-gray-100">Select a room to begin</h3>
            <p class="text-sm max-w-xs text-gray-500 dark:text-gray-400">
                Click on a room in the sidebar to view its locations, runs, and cabinets.
            </p>
        </div>
    </template>
</div>
