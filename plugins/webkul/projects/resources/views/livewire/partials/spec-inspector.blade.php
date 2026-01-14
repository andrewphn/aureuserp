{{-- Dynamic Inspector Panel --}}
{{-- Content changes based on selection: Room → Location → Run → Cabinets --}}
{{-- Enhanced with WCAG 2.1 AA accessibility, proper heading hierarchy, and ARIA attributes --}}

<div 
    class="space-y-4"
    role="region"
    aria-label="Cabinet specification details"
    aria-live="polite"
>

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
            {{-- Run Header Section --}}
            <section aria-labelledby="run-heading">
                <x-filament::section
                    icon="heroicon-s-squares-2x2"
                    icon-color="purple"
                    compact
                >
                    <x-slot name="heading">
                        <h2 id="run-heading" class="text-base font-semibold" x-text="selectedRun.name || 'Cabinet Run'"></h2>
                    </x-slot>

                    <x-slot name="headerEnd">
                        <div class="flex items-center gap-1" role="group" aria-label="Run actions">
                            <x-filament::icon-button
                                icon="heroicon-m-pencil-square"
                                color="gray"
                                size="sm"
                                tooltip="Edit Run"
                                x-on:click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex })"
                                aria-label="Edit run details"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-document-duplicate"
                                color="gray"
                                size="sm"
                                tooltip="Duplicate Run"
                                x-on:click="$wire.mountAction('duplicateNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex, nodeType: 'cabinet_run' })"
                                aria-label="Duplicate this run"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-trash"
                                color="danger"
                                size="sm"
                                tooltip="Delete Run"
                                x-on:click="$wire.mountAction('deleteNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex, nodeType: 'cabinet_run' })"
                                aria-label="Delete this run"
                            />
                        </div>
                    </x-slot>

                    {{-- Run Stats --}}
                    <div class="flex items-center gap-6" role="group" aria-label="Run statistics">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" id="run-cabinet-count-label">Cabinets</div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white" x-text="(selectedRun.children || []).length" aria-labelledby="run-cabinet-count-label"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" id="run-lf-label">Linear Feet</div>
                            <div class="text-sm font-semibold tabular-nums text-primary-600 dark:text-primary-400" x-text="formatLinearFeet(selectedRun.linear_feet || 0)" aria-labelledby="run-lf-label"></div>
                        </div>
                    </div>
                </x-filament::section>
            </section>

            {{-- Pricing Configuration - Collapsible --}}
            <section aria-labelledby="run-pricing-heading">
                <x-filament::section
                    icon="heroicon-m-currency-dollar"
                    icon-color="warning"
                    collapsible
                    collapsed
                    compact
                >
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <h3 id="run-pricing-heading" class="text-sm font-medium">Pricing</h3>
                            <x-filament::badge color="warning" size="sm" aria-label="Current price per linear foot">
                                $<span x-text="getPricePerLF(
                                    selectedRun.cabinet_level || selectedLocation?.cabinet_level || selectedRoom?.cabinet_level || '3',
                                    selectedRun.material_category || selectedLocation?.material_category || selectedRoom?.material_category || 'stain_grade',
                                    selectedRun.finish_option || selectedLocation?.finish_option || selectedRoom?.finish_option || 'unfinished'
                                ).toFixed(2)"></span>/LF
                            </x-filament::badge>
                            <template x-if="!selectedRun.cabinet_level">
                                <x-filament::badge color="gray" size="sm" icon="heroicon-m-link">
                                    inherited
                                </x-filament::badge>
                            </template>
                        </div>
                    </x-slot>

                    <div class="space-y-3" role="group" aria-label="Pricing configuration">
                        {{-- Cabinet Level --}}
                        <div class="grid grid-cols-3 items-center gap-3">
                            <label for="run-cabinet-level" class="text-sm font-medium text-gray-700 dark:text-gray-300">Level</label>
                            <div class="col-span-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select
                                        id="run-cabinet-level"
                                        x-model="selectedRun.cabinet_level"
                                        x-on:change="updateRunPricing('cabinet_level', $event.target.value)"
                                        aria-describedby="run-level-help"
                                    >
                                        <option value="">Inherit from location</option>
                                        <template x-for="(label, key) in pricingTiers" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        {{-- Material Category --}}
                        <div class="grid grid-cols-3 items-center gap-3">
                            <label for="run-material" class="text-sm font-medium text-gray-700 dark:text-gray-300">Material</label>
                            <div class="col-span-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select
                                        id="run-material"
                                        x-model="selectedRun.material_category"
                                        x-on:change="updateRunPricing('material_category', $event.target.value)"
                                    >
                                        <option value="">Inherit from location</option>
                                        <template x-for="(label, key) in materialOptions" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        {{-- Finish Option --}}
                        <div class="grid grid-cols-3 items-center gap-3">
                            <label for="run-finish" class="text-sm font-medium text-gray-700 dark:text-gray-300">Finish</label>
                            <div class="col-span-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select
                                        id="run-finish"
                                        x-model="selectedRun.finish_option"
                                        x-on:change="updateRunPricing('finish_option', $event.target.value)"
                                    >
                                        <option value="">Inherit from location</option>
                                        <template x-for="(label, key) in finishOptions" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        <p id="run-level-help" class="text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                            Override location defaults for this run. Leave blank to inherit.
                        </p>
                    </div>
                </x-filament::section>
            </section>

            {{-- Cabinets Table Section --}}
            <section aria-labelledby="cabinets-heading">
                <x-filament::section compact>
                    <x-slot name="heading">
                        <h3 id="cabinets-heading" class="text-sm font-medium">Cabinets</h3>
                    </x-slot>
                    <x-slot name="headerEnd">
                        <div class="flex items-center gap-1" role="group" aria-label="Cabinet actions">
                            {{-- Primary: Inline Add Button --}}
                            <x-filament::button
                                size="xs"
                                color="primary"
                                icon="heroicon-m-plus"
                                x-on:click="const runPath = selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex; $wire.startAddCabinet(runPath)"
                                aria-label="Add cabinet inline with Excel-like editing"
                                aria-keyshortcuts="Alt+A"
                            >
                                Add Cabinet
                            </x-filament::button>
                            {{-- Fallback: Modal Add (via context menu or secondary action) --}}
                            <x-filament::icon-button
                                icon="heroicon-m-ellipsis-vertical"
                                color="gray"
                                size="xs"
                                tooltip="More options (open modal form)"
                                x-on:click="$wire.mountAction('createCabinet', { runPath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex })"
                                aria-label="Open cabinet creation modal for advanced options"
                            />
                        </div>
                    </x-slot>

                    <div class="-mx-4 -mb-4">
                        @include('webkul-project::livewire.partials.spec-cabinet-table')

                        {{-- Quick Add Input (Molecule Component) --}}
                        @include('webkul-project::livewire.partials.molecules.cabinet-quick-add')
                    </div>
                </x-filament::section>
            </section>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- WHEN A LOCATION IS SELECTED (but no run): Show location + runs list --}}
    {{-- ================================================================== --}}
    <template x-if="selectedLocation && !selectedRun">
        <div class="space-y-4">
            {{-- Location Header Section --}}
            <section aria-labelledby="location-heading">
                <x-filament::section
                    icon="heroicon-s-map-pin"
                    icon-color="success"
                    compact
                >
                    <x-slot name="heading">
                        <h2 id="location-heading" class="text-base font-semibold" x-text="selectedLocation.name || 'Location'"></h2>
                    </x-slot>

                    <x-slot name="headerEnd">
                        <div class="flex items-center gap-1" role="group" aria-label="Location actions">
                            <x-filament::icon-button
                                icon="heroicon-m-pencil-square"
                                color="gray"
                                size="sm"
                                tooltip="Edit Location"
                                x-on:click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex })"
                                aria-label="Edit location details"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-document-duplicate"
                                color="gray"
                                size="sm"
                                tooltip="Duplicate Location"
                                x-on:click="$wire.mountAction('duplicateNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex, nodeType: 'room_location' })"
                                aria-label="Duplicate this location"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-trash"
                                color="danger"
                                size="sm"
                                tooltip="Delete Location"
                                x-on:click="$wire.mountAction('deleteNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex, nodeType: 'room_location' })"
                                aria-label="Delete this location"
                            />
                        </div>
                    </x-slot>

                    {{-- Location Stats --}}
                    <div class="grid grid-cols-3 gap-4" role="group" aria-label="Location statistics">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" id="loc-runs-label">Runs</div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white" x-text="(selectedLocation.children || []).length" aria-labelledby="loc-runs-label"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" id="loc-lf-label">Linear Feet</div>
                            <div class="text-sm font-semibold tabular-nums text-primary-600 dark:text-primary-400" x-text="formatLinearFeet(selectedLocation.linear_feet || 0)" aria-labelledby="loc-lf-label"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" id="loc-price-label">Estimated</div>
                            <div class="text-sm font-semibold tabular-nums text-success-600 dark:text-success-400" aria-labelledby="loc-price-label">$<span x-text="(selectedLocation.estimated_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span></div>
                        </div>
                    </div>
                </x-filament::section>
            </section>

            {{-- Pricing Configuration - Collapsible --}}
            <section aria-labelledby="location-pricing-heading">
                <x-filament::section
                    icon="heroicon-m-currency-dollar"
                    icon-color="warning"
                    collapsible
                    collapsed
                    compact
                >
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <h3 id="location-pricing-heading" class="text-sm font-medium">Pricing</h3>
                            <x-filament::badge color="warning" size="sm" aria-label="Current price per linear foot">
                                $<span x-text="getPricePerLF(
                                    selectedLocation.cabinet_level || selectedRoom?.cabinet_level || '3',
                                    selectedLocation.material_category || selectedRoom?.material_category || 'stain_grade',
                                    selectedLocation.finish_option || selectedRoom?.finish_option || 'unfinished'
                                ).toFixed(2)"></span>/LF
                            </x-filament::badge>
                            <template x-if="!selectedLocation.cabinet_level">
                                <x-filament::badge color="gray" size="sm" icon="heroicon-m-link">
                                    inherited
                                </x-filament::badge>
                            </template>
                        </div>
                    </x-slot>

                    <div class="space-y-3" role="group" aria-label="Location pricing configuration">
                        {{-- Cabinet Level --}}
                        <div class="grid grid-cols-3 items-center gap-3">
                            <label for="location-cabinet-level" class="text-sm font-medium text-gray-700 dark:text-gray-300">Level</label>
                            <div class="col-span-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select
                                        id="location-cabinet-level"
                                        x-model="selectedLocation.cabinet_level"
                                        x-on:change="updateLocationPricing('cabinet_level', $event.target.value)"
                                        aria-describedby="location-level-help"
                                    >
                                        <option value="">Inherit from room</option>
                                        <template x-for="(label, key) in pricingTiers" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        {{-- Material Category --}}
                        <div class="grid grid-cols-3 items-center gap-3">
                            <label for="location-material" class="text-sm font-medium text-gray-700 dark:text-gray-300">Material</label>
                            <div class="col-span-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select
                                        id="location-material"
                                        x-model="selectedLocation.material_category"
                                        x-on:change="updateLocationPricing('material_category', $event.target.value)"
                                    >
                                        <option value="">Inherit from room</option>
                                        <template x-for="(label, key) in materialOptions" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        {{-- Finish Option --}}
                        <div class="grid grid-cols-3 items-center gap-3">
                            <label for="location-finish" class="text-sm font-medium text-gray-700 dark:text-gray-300">Finish</label>
                            <div class="col-span-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select
                                        id="location-finish"
                                        x-model="selectedLocation.finish_option"
                                        x-on:change="updateLocationPricing('finish_option', $event.target.value)"
                                    >
                                        <option value="">Inherit from room</option>
                                        <template x-for="(label, key) in finishOptions" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        <p id="location-level-help" class="text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                            Override room defaults for this location. Leave blank to inherit.
                        </p>
                    </div>
                </x-filament::section>
            </section>

            {{-- Runs List Section --}}
            <section aria-labelledby="runs-list-heading">
                <x-filament::section compact>
                    <x-slot name="heading">
                        <h3 id="runs-list-heading" class="text-sm font-medium">Cabinet Runs</h3>
                    </x-slot>
                    <x-slot name="headerEnd">
                        <x-filament::button
                            size="xs"
                            color="gray"
                            icon="heroicon-m-plus"
                            x-on:click="$wire.mountAction('createRun', { locationPath: selectedRoomIndex + '.children.' + selectedLocationIndex })"
                            aria-label="Add new cabinet run"
                        >
                            Add Run
                        </x-filament::button>
                    </x-slot>

                    <div class="-mx-4 -mb-4 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-if="!(selectedLocation.children || []).length">
                            <div class="px-4 py-8 text-center" role="status" aria-label="No cabinet runs">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-800" aria-hidden="true">
                                    <x-heroicon-o-squares-2x2 class="w-6 h-6 text-gray-400" />
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">No cabinet runs yet.</p>
                                <x-filament::link
                                    color="primary"
                                    x-on:click="$wire.mountAction('createRun', { locationPath: selectedRoomIndex + '.children.' + selectedLocationIndex })"
                                >
                                    Add your first run
                                </x-filament::link>
                            </div>
                        </template>

                        <template x-for="(run, runIdx) in (selectedLocation.children || [])" :key="run.id || runIdx">
                            <div
                                x-on:click="selectRun(selectedRoomIndex, selectedLocationIndex, runIdx)"
                                role="button"
                                tabindex="0"
                                :aria-label="(run.name || 'Untitled Run') + ', ' + (run.children || []).length + ' cabinets, ' + formatLinearFeet(run.linear_feet || 0)"
                                @keydown.enter="selectRun(selectedRoomIndex, selectedLocationIndex, runIdx)"
                                @keydown.space.prevent="selectRun(selectedRoomIndex, selectedLocationIndex, runIdx)"
                                class="flex items-center justify-between px-4 py-3 cursor-pointer group transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-800/50 focus:ring-2 focus:ring-inset focus:ring-primary-500"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-purple-50 dark:bg-purple-900/20" aria-hidden="true">
                                        <x-heroicon-s-squares-2x2 class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="run.name || 'Untitled Run'"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="(run.children || []).length"></span> cabinets
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold tabular-nums text-primary-600 dark:text-primary-400" x-text="formatLinearFeet(run.linear_feet || 0)"></span>
                                    <x-heroicon-m-chevron-right class="w-5 h-5 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden="true" />
                                </div>
                            </div>
                        </template>
                    </div>
                </x-filament::section>
            </section>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- WHEN A ROOM IS SELECTED (but no location): Show room + locations list --}}
    {{-- ================================================================== --}}
    <template x-if="selectedRoom && !selectedLocation">
        <div class="space-y-3">
            {{-- Compact Two-Column Header: Room Info + Pricing --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Left: Room Info --}}
                <section 
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3"
                    aria-labelledby="room-info-heading"
                >
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-md bg-primary-50 dark:bg-primary-900/30" aria-hidden="true">
                                <x-heroicon-s-home class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <h2 id="room-info-heading" class="font-semibold text-gray-900 dark:text-white" x-text="selectedRoom.name || 'Room'"></h2>
                        </div>
                        <div class="flex items-center gap-0.5" role="group" aria-label="Room actions">
                            <x-filament::icon-button
                                icon="heroicon-m-pencil-square"
                                color="gray"
                                size="xs"
                                tooltip="Edit"
                                x-on:click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex.toString() })"
                                aria-label="Edit room details"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-document-duplicate"
                                color="gray"
                                size="xs"
                                tooltip="Duplicate"
                                x-on:click="$wire.mountAction('duplicateNode', { nodePath: selectedRoomIndex.toString(), nodeType: 'room' })"
                                aria-label="Duplicate this room"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-trash"
                                color="danger"
                                size="xs"
                                tooltip="Delete"
                                x-on:click="$wire.mountAction('deleteNode', { nodePath: selectedRoomIndex.toString(), nodeType: 'room' })"
                                aria-label="Delete this room"
                            />
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs" role="group" aria-label="Room statistics">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Type:</span>
                            <span class="font-medium capitalize text-gray-700 dark:text-gray-300" x-text="selectedRoom.room_type || 'Other'"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Locations:</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300" x-text="(selectedRoom.children || []).length"></span>
                        </div>
                        <div>
                            <span class="font-semibold tabular-nums text-primary-600 dark:text-primary-400" x-text="formatLinearFeet(selectedRoom.linear_feet || 0)"></span>
                        </div>
                    </div>
                </section>

                {{-- Right: Pricing Summary --}}
                <section 
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3"
                    aria-labelledby="room-pricing-heading"
                >
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-md bg-warning-50 dark:bg-warning-900/30" aria-hidden="true">
                                <x-heroicon-s-currency-dollar class="w-4 h-4 text-warning-600 dark:text-warning-400" />
                            </div>
                            <h3 id="room-pricing-heading" class="text-sm font-medium text-gray-700 dark:text-gray-300">Pricing</h3>
                        </div>
                        <x-filament::badge color="success" size="sm" aria-label="Price per linear foot">
                            $<span x-text="getPricePerLF(
                                selectedRoom.cabinet_level || '3',
                                selectedRoom.material_category || 'stain_grade',
                                selectedRoom.finish_option || 'unfinished'
                            ).toFixed(2)"></span>/LF
                        </x-filament::badge>
                    </div>
                    <div class="grid grid-cols-3 gap-2" role="group" aria-label="Room pricing options">
                        <div>
                            <label for="room-level-select" class="sr-only">Cabinet Level</label>
                            <select
                                id="room-level-select"
                                x-model="selectedRoom.cabinet_level"
                                x-on:change="updateRoomPricing('cabinet_level', $event.target.value)"
                                class="text-xs py-1.5 px-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-primary-500 dark:focus:ring-primary-400 focus:border-primary-500 dark:focus:border-primary-400 w-full"
                            >
                                <template x-for="(label, key) in pricingTiers" :key="key">
                                    <option :value="key" x-text="'L' + key" :selected="key === (selectedRoom.cabinet_level || '3')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label for="room-material-select" class="sr-only">Material Category</label>
                            <select
                                id="room-material-select"
                                x-model="selectedRoom.material_category"
                                x-on:change="updateRoomPricing('material_category', $event.target.value)"
                                class="text-xs py-1.5 px-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-primary-500 dark:focus:ring-primary-400 focus:border-primary-500 dark:focus:border-primary-400 w-full"
                            >
                                <template x-for="(label, key) in materialOptions" :key="key">
                                    <option :value="key" x-text="label.split(' ')[0]" :selected="key === (selectedRoom.material_category || 'stain_grade')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label for="room-finish-select" class="sr-only">Finish Option</label>
                            <select
                                id="room-finish-select"
                                x-model="selectedRoom.finish_option"
                                x-on:change="updateRoomPricing('finish_option', $event.target.value)"
                                class="text-xs py-1.5 px-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-primary-500 dark:focus:ring-primary-400 focus:border-primary-500 dark:focus:border-primary-400 w-full"
                            >
                                <template x-for="(label, key) in finishOptions" :key="key">
                                    <option :value="key" x-text="label.split(' ')[0]" :selected="key === (selectedRoom.finish_option || 'unfinished')"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Locations List Section --}}
            <section aria-labelledby="locations-list-heading">
                <x-filament::section compact>
                    <x-slot name="heading">
                        <h3 id="locations-list-heading" class="text-sm font-medium">Wall Locations</h3>
                    </x-slot>
                    <x-slot name="headerEnd">
                        <x-filament::button
                            size="xs"
                            color="gray"
                            icon="heroicon-m-plus"
                            x-on:click="$wire.mountAction('createLocation', { roomPath: selectedRoomIndex.toString() })"
                            aria-label="Add new wall location"
                        >
                            Add Location
                        </x-filament::button>
                    </x-slot>

                    <div class="-mx-4 -mb-4 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-if="!(selectedRoom.children || []).length">
                            <div class="px-4 py-8 text-center" role="status" aria-label="No wall locations">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-800" aria-hidden="true">
                                    <x-heroicon-o-map-pin class="w-6 h-6 text-gray-400" />
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">No wall locations yet.</p>
                                <x-filament::link
                                    color="primary"
                                    x-on:click="$wire.mountAction('createLocation', { roomPath: selectedRoomIndex.toString() })"
                                >
                                    Add your first location
                                </x-filament::link>
                            </div>
                        </template>

                        <template x-for="(location, locIdx) in (selectedRoom.children || [])" :key="location.id || locIdx">
                            <div
                                x-on:click="selectLocation(selectedRoomIndex, locIdx)"
                                role="button"
                                tabindex="0"
                                :aria-label="(location.name || 'Untitled location') + ', ' + (location.children || []).length + ' runs, ' + formatLinearFeet(location.linear_feet || 0)"
                                @keydown.enter="selectLocation(selectedRoomIndex, locIdx)"
                                @keydown.space.prevent="selectLocation(selectedRoomIndex, locIdx)"
                                class="flex items-center justify-between px-4 py-3 cursor-pointer group transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-800/50 focus:ring-2 focus:ring-inset focus:ring-primary-500"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-green-50 dark:bg-green-900/20" aria-hidden="true">
                                        <x-heroicon-s-map-pin class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="location.name || 'Untitled'"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                            <span>L<span x-text="location.cabinet_level || selectedRoom.cabinet_level || '3'"></span></span>
                                            <template x-if="!location.cabinet_level">
                                                <x-filament::badge color="gray" size="xs">inherited</x-filament::badge>
                                            </template>
                                            <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">&bull;</span>
                                            <span x-text="(location.children || []).length"></span> runs
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold tabular-nums text-primary-600 dark:text-primary-400" x-text="formatLinearFeet(location.linear_feet || 0)"></span>
                                    <x-heroicon-m-chevron-right class="w-5 h-5 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden="true" />
                                </div>
                            </div>
                        </template>
                    </div>
                </x-filament::section>
            </section>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- EMPTY STATE: No selection --}}
    {{-- ================================================================== --}}
    <template x-if="!selectedRoom">
        <div 
            class="flex flex-col items-center justify-center h-full py-16 text-center"
            role="status"
            aria-label="No room selected"
        >
            <div class="w-16 h-16 mb-4 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-800" aria-hidden="true">
                <x-heroicon-o-cursor-arrow-rays class="w-8 h-8 text-gray-400" />
            </div>
            <h2 class="text-base font-semibold mb-1 text-gray-900 dark:text-white">Select a room to begin</h2>
            <p class="text-sm max-w-xs text-gray-500 dark:text-gray-400">
                Click on a room in the sidebar to view its locations, runs, and cabinets.
            </p>
        </div>
    </template>
</div>
