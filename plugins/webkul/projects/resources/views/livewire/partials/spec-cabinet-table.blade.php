{{-- Cabinet Table with Inline Editing --}}
{{-- Used inside spec-inspector.blade.php when a run is selected --}}

<table class="w-full text-sm">
    <thead class="bg-gray-50 dark:bg-gray-800/50">
        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            <th class="px-3 py-2.5">Code/Name</th>
            <th class="px-3 py-2.5 text-center">Width</th>
            <th class="px-3 py-2.5 text-center">Height</th>
            <th class="px-3 py-2.5 text-center">Depth</th>
            <th class="px-3 py-2.5 text-center">Qty</th>
            <th class="px-3 py-2.5 text-right">LF</th>
            <th class="px-3 py-2.5 w-16"></th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        {{-- Empty state --}}
        <template x-if="!(selectedRun.children || []).length">
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    <div class="text-sm">No cabinets in this run.</div>
                    <div class="text-xs mt-1">Use the quick add below or click "Add Cabinet"</div>
                </td>
            </tr>
        </template>

        {{-- Cabinet Rows --}}
        <template x-for="(cabinet, cabIdx) in (selectedRun.children || [])" :key="cabinet.id || cabIdx">
            <tr class="hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors">
                {{-- Code/Name --}}
                <td class="px-1 py-1">
                    <template x-if="editingRow === cabIdx && editingField === 'name'">
                        <input
                            type="text"
                            x-model="cabinet.name"
                            @blur="saveCabinetField(cabIdx, 'name', $event.target.value)"
                            @keydown.enter.prevent.stop="saveCabinetField(cabIdx, 'name', $event.target.value); moveToNextCell(cabIdx, 'name', $event)"
                            @keydown.tab.prevent.stop="saveCabinetField(cabIdx, 'name', $event.target.value); moveToNextCell(cabIdx, 'name', $event)"
                            @keydown.escape="cancelEdit()"
                            x-init="$nextTick(() => $el.focus())"
                            class="w-full px-2 py-1.5 text-sm border border-primary-400 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'name')">
                        <button
                            @dblclick="startEdit(cabIdx, 'name')"
                            @click="startEdit(cabIdx, 'name')"
                            class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-900 dark:text-white truncate"
                            x-text="cabinet.name || '-'"
                        ></button>
                    </template>
                </td>

                {{-- Width --}}
                <td class="px-1 py-1 text-center">
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
                            class="w-16 px-2 py-1.5 text-sm text-center border border-primary-400 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'length_inches')">
                        <button
                            @dblclick="startEdit(cabIdx, 'length_inches')"
                            @click="startEdit(cabIdx, 'length_inches')"
                            class="w-16 text-center px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 tabular-nums"
                            x-text="cabinet.length_inches ? cabinet.length_inches + '&quot;' : '-'"
                        ></button>
                    </template>
                </td>

                {{-- Height --}}
                <td class="px-1 py-1 text-center">
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
                            class="w-16 px-2 py-1.5 text-sm text-center border border-primary-400 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'height_inches')">
                        <button
                            @dblclick="startEdit(cabIdx, 'height_inches')"
                            @click="startEdit(cabIdx, 'height_inches')"
                            class="w-16 text-center px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 tabular-nums"
                            x-text="cabinet.height_inches ? cabinet.height_inches + '&quot;' : '-'"
                        ></button>
                    </template>
                </td>

                {{-- Depth --}}
                <td class="px-1 py-1 text-center">
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
                            class="w-16 px-2 py-1.5 text-sm text-center border border-primary-400 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'depth_inches')">
                        <button
                            @dblclick="startEdit(cabIdx, 'depth_inches')"
                            @click="startEdit(cabIdx, 'depth_inches')"
                            class="w-16 text-center px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 tabular-nums"
                            x-text="cabinet.depth_inches ? cabinet.depth_inches + '&quot;' : '-'"
                        ></button>
                    </template>
                </td>

                {{-- Quantity --}}
                <td class="px-1 py-1 text-center">
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
                            class="w-12 px-2 py-1.5 text-sm text-center border border-primary-400 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        />
                    </template>
                    <template x-if="!(editingRow === cabIdx && editingField === 'quantity')">
                        <button
                            @dblclick="startEdit(cabIdx, 'quantity')"
                            @click="startEdit(cabIdx, 'quantity')"
                            class="w-12 text-center px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 tabular-nums"
                            x-text="cabinet.quantity || 1"
                        ></button>
                    </template>
                </td>

                {{-- Linear Feet (calculated) --}}
                <td class="px-3 py-1.5 text-right">
                    <span
                        class="font-medium text-blue-600 dark:text-blue-400 tabular-nums"
                        x-text="((cabinet.length_inches / 12) * (cabinet.quantity || 1)).toFixed(2)"
                    ></span>
                </td>

                {{-- Actions --}}
                <td class="px-2 py-1 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <button
                            @click="$wire.openEdit('cabinet', selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + cabIdx)"
                            class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-400 hover:text-gray-600 transition-colors"
                            title="Edit Cabinet"
                        >
                            <x-heroicon-m-pencil class="w-3.5 h-3.5" />
                        </button>
                        <button
                            @click="if(confirm('Delete this cabinet?')) deleteCabinet(cabIdx)"
                            class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-400 hover:text-red-600 transition-colors"
                            title="Delete Cabinet"
                        >
                            <x-heroicon-m-trash class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </td>
            </tr>
        </template>
    </tbody>

    {{-- Footer Totals --}}
    <tfoot class="bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
        <tr class="text-sm font-medium">
            <td colspan="4" class="px-3 py-2.5 text-gray-600 dark:text-gray-400">
                Run Total
            </td>
            <td class="px-3 py-2.5 text-center text-gray-900 dark:text-white tabular-nums">
                <span x-text="(selectedRun.children || []).reduce((sum, c) => sum + (c.quantity || 1), 0)"></span>
            </td>
            <td class="px-3 py-2.5 text-right text-blue-600 dark:text-blue-400 font-semibold tabular-nums">
                <span x-text="((selectedRun.children || []).reduce((sum, c) => sum + ((c.length_inches / 12) * (c.quantity || 1)), 0)).toFixed(2)"></span> LF
            </td>
            <td></td>
        </tr>
    </tfoot>
</table>
