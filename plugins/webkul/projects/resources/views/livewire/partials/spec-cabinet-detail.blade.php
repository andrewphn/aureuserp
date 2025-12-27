{{-- Cabinet Detail View with Sections, Contents, and Hardware --}}
{{-- Used inside spec-inspector.blade.php when a cabinet is selected --}}

<div class="space-y-4">
    {{-- Back Button - Prominent and Touch-Friendly --}}
    <button
        @click="selectedCabinetIndex = null"
        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg border transition-colors min-h-[48px] group bg-white dark:bg-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-600 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300"
        title="Back to cabinet list (Esc)"
    >
        <x-heroicon-m-arrow-left class="w-5 h-5 transition-colors text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200" />
        <span class="text-sm font-medium">Back to Cabinet List</span>
        <kbd class="ml-auto text-xs px-1.5 py-0.5 rounded hidden sm:inline bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400">Esc</kbd>
    </button>

    {{-- Cabinet Header --}}
    <div class="rounded-lg border p-4 bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                <div class="p-1.5 rounded bg-amber-100 dark:bg-amber-900/50">
                    <x-heroicon-s-cube class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                </div>
                <span x-text="(selectedRun.children || [])[selectedCabinetIndex]?.name || 'Cabinet'"></span>
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400" x-text="(selectedRun.children || [])[selectedCabinetIndex]?.code || ''"></span>
            </h3>
            <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-500 dark:text-gray-400">
                    <span x-text="(selectedRun.children || [])[selectedCabinetIndex]?.length_inches || 0"></span>"W ×
                    <span x-text="(selectedRun.children || [])[selectedCabinetIndex]?.height_inches || 0"></span>"H ×
                    <span x-text="(selectedRun.children || [])[selectedCabinetIndex]?.depth_inches || 0"></span>"D
                </span>
            </div>
        </div>
    </div>

    {{-- Sections List --}}
    <div class="rounded-lg border overflow-hidden bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 border-b flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Sections / Openings</h4>
            <button
                @click="$wire.addSection(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex)"
                class="text-xs hover:text-primary-700 font-medium flex items-center gap-1 text-primary-600 dark:text-primary-400"
            >
                <x-heroicon-m-plus class="w-3.5 h-3.5" />
                Add Section
            </button>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            {{-- Empty state --}}
            <template x-if="!((selectedRun.children || [])[selectedCabinetIndex]?.children || []).length">
                <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="w-10 h-10 mx-auto mb-2 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                        <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-400" />
                    </div>
                    No sections defined yet.
                    <button
                        @click="$wire.addSection(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex)"
                        class="text-primary-600 hover:text-primary-700 font-medium ml-1"
                    >Add your first section</button>
                </div>
            </template>

            {{-- Section Rows --}}
            <template x-for="(section, secIdx) in ((selectedRun.children || [])[selectedCabinetIndex]?.children || [])" :key="section.id || secIdx">
                <div class="p-4 bg-white dark:bg-gray-800">
                    {{-- Section Header --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <button
                                @click="toggleAccordion(section.id)"
                                class="p-0.5 rounded text-gray-400 transition-colors hover:bg-gray-200 dark:hover:bg-gray-600"
                            >
                                <x-heroicon-m-chevron-down x-show="isExpanded(section.id)" class="w-4 h-4" />
                                <x-heroicon-m-chevron-right x-show="!isExpanded(section.id)" class="w-4 h-4" />
                            </button>
                            <div class="p-1 rounded bg-indigo-100 dark:bg-indigo-900/50">
                                <x-heroicon-s-square-2-stack class="w-3 h-3 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <span class="font-medium text-sm text-gray-800 dark:text-gray-200" x-text="section.name || 'Unnamed Section'"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            {{-- Section Dimensions (inline editable) --}}
                            <div class="flex items-center gap-1 text-xs">
                                <input
                                    type="number"
                                    step="0.125"
                                    :value="section.width_inches"
                                    @blur="$wire.updateSectionField(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx, 'width_inches', $event.target.value)"
                                    class="w-12 px-1.5 py-1 text-center text-xs border-0 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100"
                                    placeholder="W"
                                />
                                <span class="text-gray-400 dark:text-gray-500">×</span>
                                <input
                                    type="number"
                                    step="0.125"
                                    :value="section.height_inches"
                                    @blur="$wire.updateSectionField(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx, 'height_inches', $event.target.value)"
                                    class="w-12 px-1.5 py-1 text-center text-xs border-0 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100"
                                    placeholder="H"
                                />
                                <span class="text-gray-400 dark:text-gray-500">×</span>
                                <input
                                    type="number"
                                    step="0.125"
                                    :value="section.depth_inches"
                                    @blur="$wire.updateSectionField(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx, 'depth_inches', $event.target.value)"
                                    class="w-12 px-1.5 py-1 text-center text-xs border-0 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100"
                                    placeholder="D"
                                />
                            </div>
                            <button
                                @click="$wire.delete(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx)"
                                class="p-1 rounded text-gray-400 hover:text-red-600 transition-colors hover:bg-red-100 dark:hover:bg-red-900/30"
                                title="Delete Section"
                            >
                                <x-heroicon-m-trash class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    {{-- Section Contents (expanded) --}}
                    <div x-show="isExpanded(section.id)" x-collapse class="ml-6 mt-2 space-y-2">
                        {{-- Add Content Buttons --}}
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Add:</span>
                            <template x-for="(label, type) in { drawer: 'Drawer', door: 'Door', shelf: 'Shelf', pullout: 'Pull-out' }">
                                <button
                                    @click="$wire.addContent(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx, type)"
                                    class="px-2 py-1 text-xs rounded-md transition-colors bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300"
                                    x-text="label"
                                ></button>
                            </template>
                        </div>

                        {{-- Contents List --}}
                        <template x-for="(content, contIdx) in (section.children || [])" :key="content.id || contIdx">
                            <div class="rounded-lg border p-3 bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600">
                                {{-- Content Header --}}
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <template x-if="content.content_type === 'drawer'">
                                            <div class="p-1 rounded bg-orange-100 dark:bg-orange-900/50">
                                                <x-heroicon-s-inbox class="w-3 h-3 text-orange-600 dark:text-orange-400" />
                                            </div>
                                        </template>
                                        <template x-if="content.content_type === 'door'">
                                            <div class="p-1 rounded bg-cyan-100 dark:bg-cyan-900/50">
                                                <x-heroicon-s-rectangle-group class="w-3 h-3 text-cyan-600 dark:text-cyan-400" />
                                            </div>
                                        </template>
                                        <template x-if="!['drawer', 'door'].includes(content.content_type)">
                                            <div class="p-1 rounded bg-gray-200 dark:bg-gray-600">
                                                <x-heroicon-s-square-2-stack class="w-3 h-3 text-gray-500" />
                                            </div>
                                        </template>
                                        <span class="font-medium text-sm capitalize text-gray-700 dark:text-gray-200" x-text="content.content_type || content.name || 'Content'"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="content.name"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        {{-- Content Dimensions --}}
                                        <div class="flex items-center gap-1 text-xs">
                                            <input
                                                type="number"
                                                step="0.0625"
                                                :value="content.width_inches"
                                                @blur="$wire.updateContentField(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx, 'width_inches', $event.target.value)"
                                                class="w-14 px-1.5 py-1 text-center text-xs border border-gray-300 dark:border-gray-500 rounded bg-white dark:bg-gray-600 text-gray-800 dark:text-gray-100"
                                                placeholder="W"
                                                title="Width (auto-calculated from slide)"
                                            />
                                            <span class="text-gray-400 dark:text-gray-500">×</span>
                                            <input
                                                type="number"
                                                step="0.0625"
                                                :value="content.height_inches"
                                                @blur="$wire.updateContentField(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx, 'height_inches', $event.target.value)"
                                                class="w-12 px-1.5 py-1 text-center text-xs border border-gray-300 dark:border-gray-500 rounded bg-white dark:bg-gray-600 text-gray-800 dark:text-gray-100"
                                                placeholder="H"
                                            />
                                            <span class="text-gray-400 dark:text-gray-500">×</span>
                                            <input
                                                type="number"
                                                step="0.0625"
                                                :value="content.depth_inches"
                                                @blur="$wire.updateContentField(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx, 'depth_inches', $event.target.value)"
                                                class="w-14 px-1.5 py-1 text-center text-xs border border-gray-300 dark:border-gray-500 rounded bg-white dark:bg-gray-600 text-gray-800 dark:text-gray-100"
                                                placeholder="D (from slide)"
                                                title="Depth (auto-calculated from slide)"
                                            />
                                        </div>
                                        <button
                                            @click="$wire.delete(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx)"
                                            class="p-1 rounded text-gray-400 hover:text-red-600 transition-colors hover:bg-red-100 dark:hover:bg-red-900/30"
                                            title="Delete Content"
                                        >
                                            <x-heroicon-m-trash class="w-3 h-3" />
                                        </button>
                                    </div>
                                </div>

                                {{-- Hardware for this content --}}
                                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Hardware</span>
                                        <div class="flex items-center gap-1">
                                            <template x-if="content.content_type === 'drawer'">
                                                <button
                                                    @click="$wire.addHardware(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx, 'slide')"
                                                    class="text-xs hover:underline text-primary-600 dark:text-primary-400"
                                                >+ Slide</button>
                                            </template>
                                            <template x-if="content.content_type === 'door'">
                                                <button
                                                    @click="$wire.addHardware(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx, 'hinge')"
                                                    class="text-xs hover:underline text-primary-600 dark:text-primary-400"
                                                >+ Hinge</button>
                                            </template>
                                            <button
                                                @click="$wire.addHardware(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx, 'handle')"
                                                class="text-xs hover:underline ml-2 text-primary-600 dark:text-primary-400"
                                            >+ Handle</button>
                                        </div>
                                    </div>

                                    {{-- Hardware List --}}
                                    <div class="space-y-1">
                                        <template x-for="(hw, hwIdx) in (content.children || [])" :key="hw.id || hwIdx">
                                            <div class="flex items-center justify-between px-2 py-1.5 rounded text-xs bg-white dark:bg-gray-800/50">
                                                <div class="flex items-center gap-2">
                                                    <span class="capitalize text-gray-600 dark:text-gray-300" x-text="hw.component_type?.replace('_', ' ') || 'Hardware'"></span>
                                                    <template x-if="hw.product_id">
                                                        <span class="text-primary-600 dark:text-primary-400" x-text="hw.name"></span>
                                                    </template>
                                                    <template x-if="!hw.product_id">
                                                        <select
                                                            x-data="{ products: [] }"
                                                            x-init="products = await $wire.searchProducts('', hw.component_type)"
                                                            @change="$wire.updateHardwareProduct(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx + '.children.' + hwIdx, parseInt($event.target.value))"
                                                            class="px-2 py-1 text-xs border rounded w-40 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border-gray-300 dark:border-gray-600"
                                                        >
                                                            <option value="">Select product...</option>
                                                            <template x-for="(label, id) in products" :key="id">
                                                                <option :value="id" x-text="label"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <template x-if="hw.sku">
                                                        <span class="text-gray-400 dark:text-gray-500" x-text="'[' + hw.sku + ']'"></span>
                                                    </template>
                                                    <template x-if="hw.unit_cost">
                                                        <span class="text-green-600 dark:text-green-400" x-text="'$' + parseFloat(hw.unit_cost).toFixed(2)"></span>
                                                    </template>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        :value="hw.quantity || 1"
                                                        @blur="$wire.updateHardwareField(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx + '.children.' + hwIdx, 'quantity', $event.target.value)"
                                                        class="w-10 px-1 py-0.5 text-center text-xs border-0 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100"
                                                    />
                                                    <button
                                                        @click="$wire.delete(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx + '.children.' + hwIdx)"
                                                        class="p-0.5 rounded text-gray-400 hover:text-red-600 transition-colors hover:bg-red-100 dark:hover:bg-red-900/30"
                                                    >
                                                        <x-heroicon-m-x-mark class="w-3 h-3" />
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Empty contents state --}}
                        <template x-if="!(section.children || []).length">
                            <div class="text-xs text-center py-2 text-gray-400 dark:text-gray-500">
                                Add drawers, doors, or shelves to this section
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
