{{-- Cabinet Table with Inline Editing --}}
{{-- Used inside spec-inspector.blade.php when a run is selected --}}

<table class="w-full text-sm">
    <thead class="bg-gray-50 dark:bg-gray-700/50">
        <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
            <th class="px-3 py-2.5">Code/Name</th>
            <th class="px-3 py-2.5 text-center">Width</th>
            <th class="px-3 py-2.5 text-center">Height</th>
            <th class="px-3 py-2.5 text-center">Depth</th>
            <th class="px-3 py-2.5 text-center">Qty</th>
            <th class="px-3 py-2.5 text-right">LF</th>
            <th class="px-3 py-2.5 w-24"></th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        {{-- Empty state --}}
        <template x-if="!(selectedRun.children || []).length">
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                        <x-heroicon-o-cube class="w-6 h-6 text-gray-400" />
                    </div>
                    <div class="text-sm font-medium mb-1">No cabinets in this run</div>
                    <div class="text-xs">Use the quick add below or click "Add Cabinet"</div>
                </td>
            </tr>
        </template>

        {{-- Cabinet Rows --}}
        <template x-for="(cabinet, cabIdx) in (selectedRun.children || [])" :key="cabinet.id || cabIdx">
            <tr
                @dblclick="selectedCabinetIndex = cabIdx"
                :class="[
                    editingRow === cabIdx
                        ? 'bg-primary-50 dark:bg-primary-900/20 ring-1 ring-inset ring-primary-200 dark:ring-primary-500/30'
                        : 'hover:bg-gray-50 dark:hover:bg-gray-700/30',
                    (cabinet.children || []).length > 0 ? 'cursor-pointer' : ''
                ]"
                class="group transition-colors"
                :title="(cabinet.children || []).length > 0 ? 'Double-click to view sections' : 'Double-click to add sections'"
            >
                {{-- Code/Name --}}
                <td class="px-1 py-1.5">
                    <template x-if="editingRow === cabIdx && editingField === 'name'">
                        <input
                            type="text"
                            x-model="cabinet.name"
                            @blur="saveCabinetField(cabIdx, 'name', $event.target.value)"
                            @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'name', $event.target.value); moveToNextCell(cabIdx, 'name', $event)"
                            @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'name', $event.target.value); moveToNextCell(cabIdx, 'name', $event)"
                            @keydown.escape="cancelEdit()"
                            x-init="$nextTick(() => $el.focus())"
                            class="w-full px-2.5 py-2 text-sm border-2 border-primary-500 rounded-md focus:ring-2 focus:ring-primary-500 focus:outline-none shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'name')">
                        <button
                            @click="startEdit(cabIdx, 'name')"
                            class="w-full text-left px-2.5 py-2 rounded-md truncate transition-all border border-transparent hover:border-dashed hover:bg-gray-100 dark:hover:bg-gray-600 border-gray-300 dark:border-gray-600"
                            title="Click to edit"
                        >
                            <span class="font-medium text-gray-800 dark:text-gray-100" x-text="cabinet.name || '-'"></span>
                        </button>
                    </template>
                </td>

                {{-- Width --}}
                <td class="px-1 py-1.5 text-center">
                    <template x-if="editingRow === cabIdx && editingField === 'length_inches'">
                        <input
                            type="number"
                            step="0.125"
                            x-model="cabinet.length_inches"
                            @blur="saveCabinetField(cabIdx, 'length_inches', $event.target.value)"
                            @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'length_inches', $event.target.value); moveToNextCell(cabIdx, 'length_inches', $event)"
                            @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'length_inches', $event.target.value); moveToNextCell(cabIdx, 'length_inches', $event)"
                            @keydown.escape="cancelEdit()"
                            x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                            class="w-16 px-2 py-2 text-sm text-center border-2 border-primary-500 rounded-md focus:ring-2 focus:ring-primary-500 focus:outline-none shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'length_inches')">
                        <button
                            @click="startEdit(cabIdx, 'length_inches')"
                            class="w-16 text-center px-2 py-2 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:bg-gray-100 dark:hover:bg-gray-600 border-gray-300 dark:border-gray-600"
                            title="Click to edit"
                        >
                            <span class="text-gray-800 dark:text-gray-100" x-text="cabinet.length_inches ? cabinet.length_inches + '\"' : '-'"></span>
                        </button>
                    </template>
                </td>

                {{-- Height --}}
                <td class="px-1 py-1.5 text-center">
                    <template x-if="editingRow === cabIdx && editingField === 'height_inches'">
                        <input
                            type="number"
                            step="0.125"
                            x-model="cabinet.height_inches"
                            @blur="saveCabinetField(cabIdx, 'height_inches', $event.target.value)"
                            @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'height_inches', $event.target.value); moveToNextCell(cabIdx, 'height_inches', $event)"
                            @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'height_inches', $event.target.value); moveToNextCell(cabIdx, 'height_inches', $event)"
                            @keydown.escape="cancelEdit()"
                            x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                            class="w-16 px-2 py-2 text-sm text-center border-2 border-primary-500 rounded-md focus:ring-2 focus:ring-primary-500 focus:outline-none shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'height_inches')">
                        <button
                            @click="startEdit(cabIdx, 'height_inches')"
                            class="w-16 text-center px-2 py-2 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:bg-gray-100 dark:hover:bg-gray-600 border-gray-300 dark:border-gray-600"
                            title="Click to edit"
                        >
                            <span class="text-gray-800 dark:text-gray-100" x-text="cabinet.height_inches ? cabinet.height_inches + '\"' : '-'"></span>
                        </button>
                    </template>
                </td>

                {{-- Depth --}}
                <td class="px-1 py-1.5 text-center">
                    <template x-if="editingRow === cabIdx && editingField === 'depth_inches'">
                        <input
                            type="number"
                            step="0.125"
                            x-model="cabinet.depth_inches"
                            @blur="saveCabinetField(cabIdx, 'depth_inches', $event.target.value)"
                            @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'depth_inches', $event.target.value); moveToNextCell(cabIdx, 'depth_inches', $event)"
                            @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'depth_inches', $event.target.value); moveToNextCell(cabIdx, 'depth_inches', $event)"
                            @keydown.escape="cancelEdit()"
                            x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                            class="w-16 px-2 py-2 text-sm text-center border-2 border-primary-500 rounded-md focus:ring-2 focus:ring-primary-500 focus:outline-none shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'depth_inches')">
                        <button
                            @click="startEdit(cabIdx, 'depth_inches')"
                            class="w-16 text-center px-2 py-2 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:bg-gray-100 dark:hover:bg-gray-600 border-gray-300 dark:border-gray-600"
                            title="Click to edit"
                        >
                            <span class="text-gray-800 dark:text-gray-100" x-text="cabinet.depth_inches ? cabinet.depth_inches + '\"' : '-'"></span>
                        </button>
                    </template>
                </td>

                {{-- Quantity --}}
                <td class="px-1 py-1.5 text-center">
                    <template x-if="editingRow === cabIdx && editingField === 'quantity'">
                        <input
                            type="number"
                            min="1"
                            x-model="cabinet.quantity"
                            @blur="saveCabinetField(cabIdx, 'quantity', $event.target.value)"
                            @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'quantity', $event.target.value); moveToNextRow(cabIdx, $event)"
                            @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'quantity', $event.target.value); moveToNextRow(cabIdx, $event)"
                            @keydown.escape="cancelEdit()"
                            x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                            class="w-14 px-2 py-2 text-sm text-center border-2 border-primary-500 rounded-md focus:ring-2 focus:ring-primary-500 focus:outline-none shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'quantity')">
                        <button
                            @click="startEdit(cabIdx, 'quantity')"
                            class="w-14 text-center px-2 py-2 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:bg-gray-100 dark:hover:bg-gray-600 border-gray-300 dark:border-gray-600"
                            title="Click to edit"
                        >
                            <span class="text-gray-800 dark:text-gray-100" x-text="cabinet.quantity || 1"></span>
                        </button>
                    </template>
                </td>

                {{-- Linear Feet (calculated) --}}
                <td class="px-3 py-2 text-right">
                    <span
                        class="font-semibold tabular-nums text-blue-600 dark:text-blue-400"
                        x-text="((cabinet.length_inches / 12) * (cabinet.quantity || 1)).toFixed(2)"
                    ></span>
                </td>

                {{-- Actions - Larger touch targets --}}
                <td class="px-2 py-1.5">
                    <div class="flex items-center justify-end gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                        {{-- Sections indicator --}}
                        <button
                            @click="selectedCabinetIndex = cabIdx"
                            :class="[
                                (cabinet.children || []).length > 0
                                    ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 ring-1 ring-indigo-200 dark:ring-indigo-500/30'
                                    : 'text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                            ]"
                            class="p-2 rounded-lg transition-colors flex items-center gap-1 min-w-[36px] min-h-[36px] justify-center"
                            :title="(cabinet.children || []).length > 0 ? (cabinet.children.length + ' section(s) - click to view') : 'Add sections'"
                        >
                            <x-heroicon-m-square-2-stack class="w-4 h-4" />
                            <span x-show="(cabinet.children || []).length > 0" class="text-xs font-bold" x-text="(cabinet.children || []).length"></span>
                        </button>
                        <button
                            @click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + cabIdx })"
                            class="p-2 rounded-lg text-gray-400 transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-gray-200"
                            title="Edit Cabinet Details"
                        >
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            @click="if(confirm('Delete this cabinet?')) deleteCabinet(cabIdx)"
                            class="p-2 rounded-lg text-gray-400 transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center hover:bg-red-100 dark:hover:bg-red-900/40 hover:text-red-600 dark:hover:text-red-400"
                            title="Delete Cabinet"
                        >
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                    </div>
                </td>
            </tr>
        </template>
    </tbody>

    {{-- Footer Totals --}}
    <tfoot class="border-t-2 bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
        <tr class="text-sm font-medium">
            <td colspan="4" class="px-3 py-3 text-gray-600 dark:text-gray-300">
                <span class="font-semibold">Run Total</span>
                <span class="text-xs font-normal ml-2">(<span x-text="(selectedRun.children || []).length"></span> cabinets)</span>
            </td>
            <td class="px-3 py-3 text-center tabular-nums font-semibold text-gray-900 dark:text-gray-100">
                <span x-text="(selectedRun.children || []).reduce((sum, c) => sum + (c.quantity || 1), 0)"></span>
            </td>
            <td class="px-3 py-3 text-right font-bold tabular-nums text-base text-blue-600 dark:text-blue-400">
                <span x-text="((selectedRun.children || []).reduce((sum, c) => sum + ((c.length_inches / 12) * (c.quantity || 1)), 0)).toFixed(2)"></span> LF
            </td>
            <td></td>
        </tr>
    </tfoot>
</table>
