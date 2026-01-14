{{-- Cabinet Table with Inline Editing --}}
{{-- Used inside spec-inspector.blade.php when a run is selected --}}
{{-- Refactored with atomic design system, comprehensive dark mode, and WCAG 2.1 AA accessibility --}}

{{-- Screen reader announcements (aria-live region) --}}
<div 
    id="cabinet-table-announcements" 
    aria-live="polite" 
    aria-atomic="true" 
    class="sr-only"
    x-ref="announcements"
></div>

{{-- Help text for screen readers --}}
<div id="table-help" class="sr-only">
    Use Tab to navigate between cells. Press Enter to save and move to the next cell. Press Escape to cancel editing. Press F2 to start editing the selected cell.
</div>

<table 
    role="grid"
    aria-label="Cabinet specifications table"
    aria-describedby="table-help"
    :aria-rowcount="(selectedRun.children || []).length + 2"
    class="w-full text-sm border-collapse"
>
    <thead role="rowgroup" class="bg-gray-50 dark:bg-gray-800/80 border-b-2 border-gray-200 dark:border-gray-700">
        <tr role="row" aria-rowindex="1" class="text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
            <th role="columnheader" scope="col" aria-colindex="1" id="col-name" class="px-3 py-3">
                Code/Name
            </th>
            <th role="columnheader" scope="col" aria-colindex="2" id="col-width" class="px-3 py-3 text-center">
                Width
                <span class="sr-only">(inches)</span>
            </th>
            <th role="columnheader" scope="col" aria-colindex="3" id="col-height" class="px-3 py-3 text-center">
                Height
                <span class="sr-only">(inches)</span>
            </th>
            <th role="columnheader" scope="col" aria-colindex="4" id="col-depth" class="px-3 py-3 text-center">
                Depth
                <span class="sr-only">(inches)</span>
            </th>
            <th role="columnheader" scope="col" aria-colindex="5" id="col-qty" class="px-3 py-3 text-center">
                Qty
                <span class="sr-only">(quantity)</span>
            </th>
            <th role="columnheader" scope="col" aria-colindex="6" id="col-lf" class="px-3 py-3 text-right">
                LF
                <span class="sr-only">(linear feet)</span>
            </th>
            <th role="columnheader" scope="col" aria-colindex="7" id="col-actions" class="px-3 py-3 w-24">
                <span class="sr-only">Actions</span>
            </th>
        </tr>
    </thead>
    
    <tbody role="rowgroup" class="divide-y divide-gray-200 dark:divide-gray-700/50 bg-white dark:bg-gray-900">
        {{-- Inline Add Row (Excel-like) - Appears at TOP when adding (before existing rows) --}}
        <tr 
            x-show="isAddingCabinet"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            role="row" 
            aria-rowindex="2"
            aria-label="New cabinet entry row"
            class="bg-primary-50 dark:bg-primary-900/20 ring-2 ring-inset ring-primary-300 dark:ring-primary-600"
        >
                {{-- Code/Name --}}
                <td role="gridcell" aria-colindex="1" class="px-1 py-1.5">
                    <label for="new-cabinet-name" class="sr-only">Cabinet code or name</label>
                    <input
                        id="new-cabinet-name"
                        type="text"
                        x-model="newCabinetData.name"
                        @input="$wire.updateNewCabinetField('name', $event.target.value)"
                        @keydown.enter.prevent.stop="saveInlineCabinet(false)"
                        @keydown.tab.prevent.stop="focusNextField('length_inches')"
                        @keydown.escape="cancelInlineAdd()"
                        x-init="$nextTick(() => $el.focus())"
                        placeholder="B24, W30, SB36..."
                        aria-describedby="help-name"
                        aria-required="false"
                        autocomplete="off"
                        class="w-full px-2.5 py-1.5 text-sm border-2 border-primary-500 dark:border-primary-400 rounded-md focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                    />
                    <span id="help-name" class="sr-only">Enter a cabinet code like B24 for base 24 inches, or W30 for wall 30 inches</span>
                </td>
                
                {{-- Width --}}
                <td role="gridcell" aria-colindex="2" class="px-1 py-1.5 text-center">
                    <label for="new-cabinet-width" class="sr-only">Width in inches</label>
                    <input
                        id="new-cabinet-width"
                        type="number"
                        step="0.125"
                        x-model="newCabinetData.length_inches"
                        @input="$wire.updateNewCabinetField('length_inches', $event.target.value)"
                        @keydown.enter.prevent.stop="saveInlineCabinet(false)"
                        @keydown.tab.prevent.stop="focusNextField('height_inches')"
                        @keydown.escape="cancelInlineAdd()"
                        placeholder="24"
                        :aria-invalid="validationErrors.length_inches ? 'true' : 'false'"
                        :aria-describedby="validationErrors.length_inches ? 'error-width' : 'help-width'"
                        inputmode="decimal"
                        :class="validationErrors.length_inches 
                            ? 'border-red-500 dark:border-red-400 focus:ring-red-500 dark:focus:ring-red-400' 
                            : 'border-primary-500 dark:border-primary-400 focus:ring-primary-500 dark:focus:ring-primary-400'"
                        class="w-16 px-2 py-1.5 text-sm text-center border-2 rounded-md focus:ring-2 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                    />
                    <span id="help-width" class="sr-only">Cabinet width in inches. Supports fractions like 24.5</span>
                    <span 
                        id="error-width" 
                        x-show="validationErrors.length_inches" 
                        x-text="validationErrors.length_inches"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        role="alert"
                        aria-live="assertive"
                        class="block text-xs text-red-600 dark:text-red-400 mt-0.5"
                    ></span>
                </td>
                
                {{-- Height --}}
                <td role="gridcell" aria-colindex="3" class="px-1 py-1.5 text-center">
                    <label for="new-cabinet-height" class="sr-only">Height in inches</label>
                    <input
                        id="new-cabinet-height"
                        type="number"
                        step="0.125"
                        x-model="newCabinetData.height_inches"
                        @input="$wire.updateNewCabinetField('height_inches', $event.target.value)"
                        @keydown.enter.prevent.stop="saveInlineCabinet(false)"
                        @keydown.tab.prevent.stop="focusNextField('depth_inches')"
                        @keydown.escape="cancelInlineAdd()"
                        placeholder="34.5"
                        :aria-invalid="validationErrors.height_inches ? 'true' : 'false'"
                        :aria-describedby="validationErrors.height_inches ? 'error-height' : 'help-height'"
                        inputmode="decimal"
                        :class="validationErrors.height_inches 
                            ? 'border-red-500 dark:border-red-400 focus:ring-red-500 dark:focus:ring-red-400' 
                            : 'border-primary-500 dark:border-primary-400 focus:ring-primary-500 dark:focus:ring-primary-400'"
                        class="w-16 px-2 py-1.5 text-sm text-center border-2 rounded-md focus:ring-2 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                    />
                    <span id="help-height" class="sr-only">Cabinet height in inches. Standard base cabinet height is 34.5 inches</span>
                    <span 
                        id="error-height" 
                        x-show="validationErrors.height_inches" 
                        x-text="validationErrors.height_inches"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        role="alert"
                        aria-live="assertive"
                        class="block text-xs text-red-600 dark:text-red-400 mt-0.5"
                    ></span>
                </td>
                
                {{-- Depth --}}
                <td role="gridcell" aria-colindex="4" class="px-1 py-1.5 text-center">
                    <label for="new-cabinet-depth" class="sr-only">Depth in inches</label>
                    <input
                        id="new-cabinet-depth"
                        type="number"
                        step="0.125"
                        x-model="newCabinetData.depth_inches"
                        @input="$wire.updateNewCabinetField('depth_inches', $event.target.value)"
                        @keydown.enter.prevent.stop="saveInlineCabinet(false)"
                        @keydown.tab.prevent.stop="focusNextField('quantity')"
                        @keydown.escape="cancelInlineAdd()"
                        placeholder="24"
                        :aria-invalid="validationErrors.depth_inches ? 'true' : 'false'"
                        :aria-describedby="validationErrors.depth_inches ? 'error-depth' : 'help-depth'"
                        inputmode="decimal"
                        :class="validationErrors.depth_inches 
                            ? 'border-red-500 dark:border-red-400 focus:ring-red-500 dark:focus:ring-red-400' 
                            : 'border-primary-500 dark:border-primary-400 focus:ring-primary-500 dark:focus:ring-primary-400'"
                        class="w-16 px-2 py-1.5 text-sm text-center border-2 rounded-md focus:ring-2 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                    />
                    <span id="help-depth" class="sr-only">Cabinet depth in inches. Standard base cabinet depth is 24 inches</span>
                    <span 
                        id="error-depth" 
                        x-show="validationErrors.depth_inches" 
                        x-text="validationErrors.depth_inches"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        role="alert"
                        aria-live="assertive"
                        class="block text-xs text-red-600 dark:text-red-400 mt-0.5"
                    ></span>
                </td>
                
                {{-- Quantity --}}
                <td role="gridcell" aria-colindex="5" class="px-1 py-1.5 text-center">
                    <label for="new-cabinet-qty" class="sr-only">Quantity</label>
                    <input
                        id="new-cabinet-qty"
                        type="number"
                        min="1"
                        x-model="newCabinetData.quantity"
                        @input="$wire.updateNewCabinetField('quantity', $event.target.value)"
                        @keydown.enter.prevent.stop="saveInlineCabinet(false)"
                        @keydown.shift.enter.prevent.stop="saveInlineCabinet(true)"
                        @keydown.tab.prevent.stop="saveInlineCabinet(false)"
                        @keydown.escape="cancelInlineAdd()"
                        placeholder="1"
                        :aria-invalid="validationErrors.quantity ? 'true' : 'false'"
                        :aria-describedby="validationErrors.quantity ? 'error-qty' : 'help-qty'"
                        inputmode="numeric"
                        :class="validationErrors.quantity 
                            ? 'border-red-500 dark:border-red-400 focus:ring-red-500 dark:focus:ring-red-400' 
                            : 'border-primary-500 dark:border-primary-400 focus:ring-primary-500 dark:focus:ring-primary-400'"
                        class="w-14 px-2 py-1.5 text-sm text-center border-2 rounded-md focus:ring-2 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                    />
                    <span id="help-qty" class="sr-only">Number of identical cabinets. Press Shift+Enter to save and add another cabinet</span>
                    <span 
                        id="error-qty" 
                        x-show="validationErrors.quantity" 
                        x-text="validationErrors.quantity"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        role="alert"
                        aria-live="assertive"
                        class="block text-xs text-red-600 dark:text-red-400 mt-0.5"
                    ></span>
                </td>
                
                {{-- Linear Feet (calculated, read-only) --}}
                <td role="gridcell" aria-colindex="6" class="px-3 py-2 text-right">
                    <span
                        aria-label="Calculated linear feet"
                        class="font-semibold tabular-nums text-blue-600 dark:text-blue-400"
                        x-text="((newCabinetData.length_inches || 0) / 12 * (newCabinetData.quantity || 1)).toFixed(2)"
                    ></span>
                    <span class="sr-only">linear feet</span>
                </td>
                
                {{-- Actions --}}
                <td role="gridcell" aria-colindex="7" class="px-2 py-1.5">
                    <div class="flex items-center justify-end gap-1" role="group" aria-label="New cabinet actions">
                        {{-- Save Button with Loading State --}}
                        <button
                            @click="saveInlineCabinet(false)"
                            type="button"
                            :disabled="isLoading"
                            :aria-busy="isLoading"
                            :aria-label="isLoading ? 'Saving cabinet...' : 'Save cabinet (Enter)'"
                            aria-keyshortcuts="Enter"
                            :class="isLoading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-50 dark:hover:bg-green-900/20'"
                            class="p-1.5 rounded-md text-green-600 dark:text-green-400 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1"
                        >
                            {{-- Loading Spinner --}}
                            <svg 
                                x-show="isLoading" 
                                class="w-4 h-4 animate-spin" 
                                xmlns="http://www.w3.org/2000/svg" 
                                fill="none" 
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{-- Check Icon --}}
                            <x-heroicon-m-check x-show="!isLoading" class="w-4 h-4" aria-hidden="true" />
                        </button>
                        <button
                            @click="cancelInlineAdd()"
                            type="button"
                            :disabled="isLoading"
                            aria-label="Cancel adding cabinet (Escape)"
                            aria-keyshortcuts="Escape"
                            :class="isLoading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="p-1.5 rounded-md text-gray-600 dark:text-gray-400 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-1"
                        >
                            <x-heroicon-m-x-mark class="w-4 h-4" aria-hidden="true" />
                        </button>
                    </div>
                </td>
            </tr>

        {{-- Empty state (only show if no cabinets AND not adding) --}}
        <tr
            x-show="!(selectedRun.children || []).length && !isAddingCabinet"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            role="row"
        >
            <td colspan="7" role="gridcell" class="px-4 py-12 text-center">
                <div 
                    class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-700"
                    aria-hidden="true"
                >
                    <x-heroicon-o-cube class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                </div>
                <div class="text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">No cabinets in this run</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Click "Add Cabinet" to start adding cabinets inline</div>
            </td>
        </tr>

        {{-- Cabinet Rows --}}
        <template x-for="(cabinet, cabIdx) in (selectedRun.children || [])" :key="cabinet.id || cabIdx">
            <tr
                role="row"
                :aria-rowindex="cabIdx + (isAddingCabinet ? 3 : 2)"
                :aria-label="'Cabinet ' + (cabinet.name || 'unnamed') + ', ' + (cabinet.length_inches || 0) + ' inches wide'"
                :aria-selected="editingRow === cabIdx"
                @dblclick="selectedCabinetIndex = cabIdx"
                :class="[
                    editingRow === cabIdx
                        ? 'bg-primary-50 dark:bg-primary-900/30 ring-2 ring-inset ring-primary-300 dark:ring-primary-600/50'
                        : 'hover:bg-gray-50 dark:hover:bg-gray-800/50',
                    (cabinet.children || []).length > 0 ? 'cursor-pointer' : ''
                ]"
                class="group transition-all duration-150"
            >
                {{-- Code/Name --}}
                <td role="gridcell" aria-colindex="1" :aria-describedby="'cabinet-name-' + cabIdx" class="px-2 py-2">
                    <template x-if="editingRow === cabIdx && editingField === 'name'">
                        <div>
                            <label :for="'edit-name-' + cabIdx" class="sr-only">Edit cabinet name</label>
                            <input
                                :id="'edit-name-' + cabIdx"
                                type="text"
                                x-model="cabinet.name"
                                @blur="saveCabinetField(cabIdx, 'name', $event.target.value)"
                                @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'name', $event.target.value); moveToNextCell(cabIdx, 'name', $event)"
                                @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'name', $event.target.value); moveToNextCell(cabIdx, 'name', $event)"
                                @keydown.escape="cancelEdit()"
                                x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                aria-describedby="table-help"
                                autocomplete="off"
                                class="w-full px-2.5 py-1.5 text-sm border-2 border-primary-500 dark:border-primary-400 rounded-md focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                            />
                        </div>
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'name')">
                        <button
                            type="button"
                            @click="startEdit(cabIdx, 'name')"
                            :aria-label="'Edit cabinet name: ' + (cabinet.name || 'unnamed') + '. Press F2 or click to edit'"
                            class="w-full text-left px-2.5 py-1.5 rounded-md truncate transition-all border border-transparent hover:border-dashed hover:border-primary-300 dark:hover:border-primary-600 hover:bg-gray-100 dark:hover:bg-gray-800/50 border-gray-300 dark:border-gray-600 group focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        >
                            <span :id="'cabinet-name-' + cabIdx" class="font-medium text-gray-800 dark:text-gray-200" x-text="cabinet.name || '-'"></span>
                            <x-heroicon-m-pencil-square class="w-3 h-3 ml-1 inline opacity-0 group-hover:opacity-50 dark:group-hover:opacity-40 transition-opacity" aria-hidden="true" />
                        </button>
                    </template>
                </td>

                {{-- Width --}}
                <td role="gridcell" aria-colindex="2" class="px-1 py-2 text-center">
                    <template x-if="editingRow === cabIdx && editingField === 'length_inches'">
                        <div>
                            <label :for="'edit-width-' + cabIdx" class="sr-only">Edit width in inches</label>
                            <input
                                :id="'edit-width-' + cabIdx"
                                type="number"
                                step="0.125"
                                x-model="cabinet.length_inches"
                                @blur="saveCabinetField(cabIdx, 'length_inches', $event.target.value)"
                                @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'length_inches', $event.target.value); moveToNextCell(cabIdx, 'length_inches', $event)"
                                @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'length_inches', $event.target.value); moveToNextCell(cabIdx, 'length_inches', $event)"
                                @keydown.escape="cancelEdit()"
                                x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                aria-describedby="table-help"
                                inputmode="decimal"
                                class="w-16 px-2 py-1.5 text-sm text-center border-2 border-primary-500 dark:border-primary-400 rounded-md focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                            />
                        </div>
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'length_inches')">
                        <button
                            type="button"
                            @click="startEdit(cabIdx, 'length_inches')"
                            :aria-label="'Width: ' + formatDimension(cabinet.length_inches) + ' inches. Click to edit'"
                            class="w-16 text-center px-2 py-1.5 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:bg-gray-100 dark:hover:bg-gray-800 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        >
                            <span class="text-gray-800 dark:text-gray-200" x-text="formatDimension(cabinet.length_inches)"></span>
                        </button>
                    </template>
                </td>

                {{-- Height --}}
                <td role="gridcell" aria-colindex="3" class="px-1 py-1.5 text-center">
                    <template x-if="editingRow === cabIdx && editingField === 'height_inches'">
                        <div>
                            <label :for="'edit-height-' + cabIdx" class="sr-only">Edit height in inches</label>
                            <input
                                :id="'edit-height-' + cabIdx"
                                type="number"
                                step="0.125"
                                x-model="cabinet.height_inches"
                                @blur="saveCabinetField(cabIdx, 'height_inches', $event.target.value)"
                                @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'height_inches', $event.target.value); moveToNextCell(cabIdx, 'height_inches', $event)"
                                @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'height_inches', $event.target.value); moveToNextCell(cabIdx, 'height_inches', $event)"
                                @keydown.escape="cancelEdit()"
                                x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                aria-describedby="table-help"
                                inputmode="decimal"
                                class="w-16 px-2 py-1.5 text-sm text-center border-2 border-primary-500 dark:border-primary-400 rounded-md focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                            />
                        </div>
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'height_inches')">
                        <button
                            type="button"
                            @click="startEdit(cabIdx, 'height_inches')"
                            :aria-label="'Height: ' + formatDimension(cabinet.height_inches) + ' inches. Click to edit'"
                            class="w-16 text-center px-2 py-1.5 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:border-primary-300 dark:hover:border-primary-600 hover:bg-gray-100 dark:hover:bg-gray-800/50 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        >
                            <span class="text-gray-800 dark:text-gray-200" x-text="formatDimension(cabinet.height_inches)"></span>
                        </button>
                    </template>
                </td>

                {{-- Depth --}}
                <td role="gridcell" aria-colindex="4" class="px-1 py-1.5 text-center">
                    <template x-if="editingRow === cabIdx && editingField === 'depth_inches'">
                        <div>
                            <label :for="'edit-depth-' + cabIdx" class="sr-only">Edit depth in inches</label>
                            <input
                                :id="'edit-depth-' + cabIdx"
                                type="number"
                                step="0.125"
                                x-model="cabinet.depth_inches"
                                @blur="saveCabinetField(cabIdx, 'depth_inches', $event.target.value)"
                                @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'depth_inches', $event.target.value); moveToNextCell(cabIdx, 'depth_inches', $event)"
                                @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'depth_inches', $event.target.value); moveToNextCell(cabIdx, 'depth_inches', $event)"
                                @keydown.escape="cancelEdit()"
                                x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                aria-describedby="table-help"
                                inputmode="decimal"
                                class="w-16 px-2 py-1.5 text-sm text-center border-2 border-primary-500 dark:border-primary-400 rounded-md focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                            />
                        </div>
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'depth_inches')">
                        <button
                            type="button"
                            @click="startEdit(cabIdx, 'depth_inches')"
                            :aria-label="'Depth: ' + formatDimension(cabinet.depth_inches) + ' inches. Click to edit'"
                            class="w-16 text-center px-2 py-1.5 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:border-primary-300 dark:hover:border-primary-600 hover:bg-gray-100 dark:hover:bg-gray-800/50 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        >
                            <span class="text-gray-800 dark:text-gray-200" x-text="formatDimension(cabinet.depth_inches)"></span>
                        </button>
                    </template>
                </td>

                {{-- Quantity --}}
                <td role="gridcell" aria-colindex="5" class="px-1 py-1.5 text-center">
                    <template x-if="editingRow === cabIdx && editingField === 'quantity'">
                        <div>
                            <label :for="'edit-qty-' + cabIdx" class="sr-only">Edit quantity</label>
                            <input
                                :id="'edit-qty-' + cabIdx"
                                type="number"
                                min="1"
                                x-model="cabinet.quantity"
                                @blur="saveCabinetField(cabIdx, 'quantity', $event.target.value)"
                                @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'quantity', $event.target.value); moveToNextRow(cabIdx, $event)"
                                @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'quantity', $event.target.value); moveToNextRow(cabIdx, $event)"
                                @keydown.escape="cancelEdit()"
                                x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                aria-describedby="table-help"
                                inputmode="numeric"
                                class="w-14 px-2 py-1.5 text-sm text-center border-2 border-primary-500 dark:border-primary-400 rounded-md focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                            />
                        </div>
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'quantity')">
                        <button
                            type="button"
                            @click="startEdit(cabIdx, 'quantity')"
                            :aria-label="'Quantity: ' + (cabinet.quantity || 1) + '. Click to edit'"
                            class="w-14 text-center px-2 py-1.5 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:border-primary-300 dark:hover:border-primary-600 hover:bg-gray-100 dark:hover:bg-gray-800/50 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        >
                            <span class="text-gray-800 dark:text-gray-200" x-text="cabinet.quantity || 1"></span>
                        </button>
                    </template>
                </td>

                {{-- Linear Feet (calculated) --}}
                <td role="gridcell" aria-colindex="6" class="px-3 py-2 text-right">
                    <span
                        :aria-label="((cabinet.length_inches / 12) * (cabinet.quantity || 1)).toFixed(2) + ' linear feet'"
                        class="font-semibold tabular-nums text-blue-600 dark:text-blue-400"
                        x-text="((cabinet.length_inches / 12) * (cabinet.quantity || 1)).toFixed(2)"
                    ></span>
                </td>

                {{-- Actions - Larger touch targets --}}
                <td role="gridcell" aria-colindex="7" class="px-2 py-1.5">
                    <div 
                        class="flex items-center justify-end gap-1 opacity-60 group-hover:opacity-100 transition-opacity"
                        role="group"
                        :aria-label="'Actions for cabinet ' + (cabinet.name || 'unnamed')"
                    >
                        {{-- Sections indicator --}}
                        <button
                            type="button"
                            @click="selectedCabinetIndex = cabIdx"
                            :class="[
                                (cabinet.children || []).length > 0
                                    ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 ring-1 ring-indigo-200 dark:ring-indigo-500/30'
                                    : 'text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                            ]"
                            class="p-2 rounded-lg transition-colors flex items-center gap-1 min-w-[36px] min-h-[36px] justify-center focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                            :aria-label="(cabinet.children || []).length > 0 ? (cabinet.children.length + ' sections. Click to view sections') : 'No sections. Click to add sections'"
                            :aria-expanded="selectedCabinetIndex === cabIdx"
                        >
                            <x-heroicon-m-square-2-stack class="w-4 h-4" aria-hidden="true" />
                            <span 
                                x-show="(cabinet.children || []).length > 0" 
                                class="text-xs font-bold" 
                                x-text="(cabinet.children || []).length"
                                aria-hidden="true"
                            ></span>
                        </button>
                        <x-filament::icon-button
                            icon="heroicon-m-pencil-square"
                            color="gray"
                            size="sm"
                            tooltip="Edit Cabinet Details"
                            x-on:click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + cabIdx })"
                            ::aria-label="'Open full editor for cabinet ' + (cabinet.name || 'unnamed')"
                        />
                        <x-filament::icon-button
                            icon="heroicon-m-trash"
                            color="danger"
                            size="sm"
                            tooltip="Delete Cabinet"
                            x-on:click="if(confirm('Delete this cabinet?')) deleteCabinet(cabIdx)"
                            ::aria-label="'Delete cabinet ' + (cabinet.name || 'unnamed')"
                        />
                    </div>
                </td>
            </tr>
        </template>
    </tbody>

    {{-- Footer Totals --}}
    <tfoot role="rowgroup" class="border-t-2 bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
        <tr role="row" class="text-sm font-medium">
            <td role="gridcell" colspan="4" class="px-3 py-3 text-gray-600 dark:text-gray-300">
                <span class="font-semibold">Run Total</span>
                <span class="text-xs font-normal ml-2" aria-label="Number of cabinets">
                    (<span x-text="(selectedRun.children || []).length"></span> cabinets)
                </span>
            </td>
            <td role="gridcell" class="px-3 py-3 text-center tabular-nums font-semibold text-gray-900 dark:text-gray-100">
                <span 
                    x-text="(selectedRun.children || []).reduce((sum, c) => sum + (c.quantity || 1), 0)"
                    :aria-label="(selectedRun.children || []).reduce((sum, c) => sum + (c.quantity || 1), 0) + ' total quantity'"
                ></span>
            </td>
            <td role="gridcell" class="px-3 py-3 text-right font-bold tabular-nums text-base text-blue-600 dark:text-blue-400">
                <span 
                    x-text="((selectedRun.children || []).reduce((sum, c) => sum + ((c.length_inches / 12) * (c.quantity || 1)), 0)).toFixed(2)"
                    :aria-label="((selectedRun.children || []).reduce((sum, c) => sum + ((c.length_inches / 12) * (c.quantity || 1)), 0)).toFixed(2) + ' total linear feet'"
                ></span> LF
            </td>
            <td role="gridcell"></td>
        </tr>
    </tfoot>
</table>
