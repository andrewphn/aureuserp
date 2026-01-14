{{-- Cabinet Detail View with Sections, Contents, and Hardware --}}
{{-- Used inside spec-inspector.blade.php when a cabinet is selected --}}

<div class="space-y-4">
    {{-- Back Button - Prominent and Touch-Friendly --}}
    <x-filament::button
        color="gray"
        icon="heroicon-m-arrow-left"
        class="w-full justify-start"
        x-on:click="selectedCabinetIndex = null"
    >
        Back to Cabinet List
        <x-slot name="badge">
            <kbd class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400">Esc</kbd>
        </x-slot>
    </x-filament::button>

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
                <span class="text-gray-500 dark:text-gray-400" x-text="formatDimensions(
                    (selectedRun.children || [])[selectedCabinetIndex]?.length_inches || 0,
                    (selectedRun.children || [])[selectedCabinetIndex]?.height_inches || 0,
                    (selectedRun.children || [])[selectedCabinetIndex]?.depth_inches || 0
                )"></span>
            </div>
        </div>
    </div>

    {{-- Sections List --}}
    <div class="rounded-lg border overflow-hidden bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 border-b flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Sections / Openings</h4>
            <x-filament::button
                size="xs"
                color="gray"
                icon="heroicon-m-plus"
                x-on:click="$wire.addSection(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex)"
            >
                Add Section
            </x-filament::button>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            {{-- Empty state --}}
            <template x-if="!((selectedRun.children || [])[selectedCabinetIndex]?.children || []).length">
                <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-800">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                            </div>
                    No sections defined yet.
                    <button
                        @click="$wire.addSection(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex)"
                        class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium ml-1"
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
                            <x-filament::icon-button
                                icon="heroicon-m-trash"
                                color="danger"
                                size="xs"
                                tooltip="Delete Section"
                                x-on:click="$wire.delete(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx)"
                            />
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
                                            <div class="p-1 rounded bg-gray-200 dark:bg-gray-700">
                                                <x-heroicon-s-square-2-stack class="w-3 h-3 text-gray-500 dark:text-gray-400" />
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
                                        <x-filament::icon-button
                                            icon="heroicon-m-trash"
                                            color="danger"
                                            size="xs"
                                            tooltip="Delete Content"
                                            x-on:click="$wire.delete(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx)"
                                        />
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
                                            <div class="flex items-center justify-between px-2 py-1.5 rounded text-xs bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                                    <span class="capitalize text-gray-600 dark:text-gray-300 whitespace-nowrap" x-text="hw.component_type?.replace('_', ' ') || 'Hardware'"></span>

                                                    {{-- Searchable Product Input with Hover Details --}}
                                                    <div
                                                        x-data="{
                                                            open: false,
                                                            search: '',
                                                            results: [],
                                                            loading: false,
                                                            hoveredProduct: null,
                                                            selectedName: hw.name && hw.product_id ? hw.name : '',
                                                            hwPath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx + '.children.' + hwIdx,
                                                            async doSearch() {
                                                                this.loading = true;
                                                                this.results = await $wire.searchProductsDetailed(this.search || '', hw.component_type);
                                                                this.loading = false;
                                                            },
                                                            selectProduct(product) {
                                                                this.selectedName = product.name;
                                                                this.search = '';
                                                                this.open = false;
                                                                this.hoveredProduct = null;
                                                                $wire.updateHardwareProduct(this.hwPath, parseInt(product.id));
                                                            }
                                                        }"
                                                        x-init="doSearch()"
                                                        @click.away="open = false; hoveredProduct = null"
                                                        class="fi-dropdown relative flex-1 max-w-[200px]"
                                                    >
                                                        {{-- Show selected product name or search input --}}
                                                        <template x-if="selectedName && !open">
                                                            <button
                                                                type="button"
                                                                @click="open = true; $nextTick(() => $refs.searchInput?.focus())"
                                                                class="fi-btn fi-btn-size-xs fi-color-custom fi-color-primary w-full text-left truncate"
                                                                x-text="selectedName"
                                                            ></button>
                                                        </template>

                                                        <template x-if="!selectedName || open">
                                                            <div>
                                                                <x-filament::input.wrapper class="fi-size-xs">
                                                                    <input
                                                                        x-ref="searchInput"
                                                                        type="text"
                                                                        x-model="search"
                                                                        @input.debounce.300ms="doSearch()"
                                                                        @focus="open = true; if (!results.length) doSearch()"
                                                                        @keydown.escape="open = false; hoveredProduct = null"
                                                                        @keydown.enter.prevent="if (results.length === 1) selectProduct(results[0])"
                                                                        placeholder="Search products..."
                                                                        class="fi-input block w-full border-none bg-transparent py-1.5 text-xs text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)]"
                                                                    />
                                                                </x-filament::input.wrapper>

                                                                {{-- Dropdown Results with Hover Preview --}}
                                                                <div
                                                                    x-show="open"
                                                                    x-transition:enter="fi-transition-enter"
                                                                    x-transition:enter-start="fi-opacity-0"
                                                                    x-transition:leave="fi-transition-leave"
                                                                    x-transition:leave-end="fi-opacity-0"
                                                                    class="absolute z-50 mt-1 flex"
                                                                >
                                                                    {{-- Results List --}}
                                                                    <div class="fi-dropdown-panel w-64 max-h-48 overflow-y-auto rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                                                                        <template x-if="loading">
                                                                            <div class="fi-dropdown-list p-1">
                                                                                <div class="fi-dropdown-list-item flex items-center gap-2 px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                                                                    <x-filament::loading-indicator class="h-4 w-4" />
                                                                                    <span>Searching...</span>
                                                                                </div>
                                                                            </div>
                                                                        </template>
                                                                        <template x-if="!loading && results.length === 0">
                                                                            <div class="fi-dropdown-list p-1">
                                                                                <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">No products found</div>
                                                                            </div>
                                                                        </template>
                                                                        <div class="fi-dropdown-list p-1" x-show="!loading && results.length > 0">
                                                                            <template x-for="product in results" :key="product.id">
                                                                                <button
                                                                                    type="button"
                                                                                    @click="selectProduct(product)"
                                                                                    @mouseenter="hoveredProduct = product"
                                                                                    @mouseleave="hoveredProduct = null"
                                                                                    class="fi-dropdown-list-item flex w-full items-start gap-2 whitespace-nowrap rounded-md p-2 text-sm transition-colors duration-75 outline-none hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                                                                                >
                                                                                    <div class="flex-1 min-w-0 text-left">
                                                                                        <div class="font-medium text-gray-950 dark:text-white truncate" x-text="product.name"></div>
                                                                                        <div class="flex items-center gap-2 mt-0.5">
                                                                                            <span x-show="product.sku" class="text-xs text-gray-500 dark:text-gray-400" x-text="product.sku"></span>
                                                                                            <span x-show="product.cost" class="fi-badge fi-badge-size-xs fi-color-success gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-1 py-0.5 bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30" x-text="product.cost"></span>
                                                                                        </div>
                                                                                    </div>
                                                                                </button>
                                                                            </template>
                                                                        </div>
                                                                    </div>

                                                                    {{-- Hover Preview Card (Filament Section Style) --}}
                                                                    <div
                                                                        x-show="hoveredProduct"
                                                                        x-transition:enter="fi-transition-enter"
                                                                        x-transition:enter-start="fi-opacity-0"
                                                                        x-transition:leave="fi-transition-leave"
                                                                        x-transition:leave-end="fi-opacity-0"
                                                                        class="fi-section ml-2 w-64 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                                                                    >
                                                                        {{-- Product Image --}}
                                                                        <template x-if="hoveredProduct?.image">
                                                                            <div class="rounded-t-xl overflow-hidden bg-gray-50 dark:bg-gray-800">
                                                                                <img
                                                                                    :src="hoveredProduct.image"
                                                                                    :alt="hoveredProduct.name"
                                                                                    class="w-full h-28 object-contain"
                                                                                    onerror="this.parentElement.style.display='none'"
                                                                                />
                                                                            </div>
                                                                        </template>

                                                                        <div class="fi-section-content p-4">
                                                                            {{-- Product Name --}}
                                                                            <h3 class="fi-section-header-heading text-sm font-semibold leading-6 text-gray-950 dark:text-white" x-text="hoveredProduct?.name"></h3>

                                                                            {{-- Info List (Filament Infolist Style) --}}
                                                                            <dl class="fi-infolist mt-3 space-y-2">
                                                                                {{-- SKU --}}
                                                                                <template x-if="hoveredProduct?.sku">
                                                                                    <div class="fi-in-entry flex gap-x-3 text-sm">
                                                                                        <dt class="fi-in-entry-label text-gray-500 dark:text-gray-400 min-w-[60px]">SKU</dt>
                                                                                        <dd class="fi-in-entry-content text-gray-950 dark:text-white" x-text="hoveredProduct.sku"></dd>
                                                                                    </div>
                                                                                </template>

                                                                                {{-- Supplier SKU --}}
                                                                                <template x-if="hoveredProduct?.supplier_sku">
                                                                                    <div class="fi-in-entry flex gap-x-3 text-sm">
                                                                                        <dt class="fi-in-entry-label text-gray-500 dark:text-gray-400 min-w-[60px]">Supplier</dt>
                                                                                        <dd class="fi-in-entry-content text-gray-950 dark:text-white" x-text="hoveredProduct.supplier_sku"></dd>
                                                                                    </div>
                                                                                </template>

                                                                                {{-- Weight --}}
                                                                                <template x-if="hoveredProduct?.weight">
                                                                                    <div class="fi-in-entry flex gap-x-3 text-sm">
                                                                                        <dt class="fi-in-entry-label text-gray-500 dark:text-gray-400 min-w-[60px]">Weight</dt>
                                                                                        <dd class="fi-in-entry-content text-gray-950 dark:text-white" x-text="hoveredProduct.weight + ' lbs'"></dd>
                                                                                    </div>
                                                                                </template>
                                                                            </dl>

                                                                            {{-- Pricing Badges --}}
                                                                            <div class="mt-3 flex flex-wrap gap-2">
                                                                                <template x-if="hoveredProduct?.cost">
                                                                                    <span class="fi-badge fi-badge-size-sm fi-color-success gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-1.5 py-0.5 bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                                                                        <span class="opacity-75">Cost:</span>
                                                                                        <span x-text="hoveredProduct.cost"></span>
                                                                                    </span>
                                                                                </template>
                                                                                <template x-if="hoveredProduct?.price">
                                                                                    <span class="fi-badge fi-badge-size-sm fi-color-primary gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-1.5 py-0.5 bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">
                                                                                        <span class="opacity-75">Price:</span>
                                                                                        <span x-text="hoveredProduct.price"></span>
                                                                                    </span>
                                                                                </template>
                                                                            </div>

                                                                            {{-- Description --}}
                                                                            <template x-if="hoveredProduct?.description">
                                                                                <p class="fi-section-header-description mt-3 text-sm text-gray-500 dark:text-gray-400 line-clamp-3 border-t border-gray-200 dark:border-white/10 pt-3" x-text="hoveredProduct.description"></p>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
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
                                                    <x-filament::icon-button
                                                        icon="heroicon-m-x-mark"
                                                        color="danger"
                                                        size="xs"
                                                        x-on:click="$wire.delete(selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + selectedCabinetIndex + '.children.' + secIdx + '.children.' + contIdx + '.children.' + hwIdx)"
                                                    />
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
