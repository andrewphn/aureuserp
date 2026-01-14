{{-- Molecule: Cabinet Row Actions --}}
{{-- Action buttons group for each cabinet row with hover opacity transition --}}

@props([
    'rowIndex' => null,
    'cabinet' => null,
])

<td class="px-2 py-1.5">
    <div class="flex items-center justify-end gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
        {{-- Sections indicator --}}
        <button
            @click="selectedCabinetIndex = {{ $rowIndex }}"
            :class="[
                ({{ $cabinet }}.children || []).length > 0
                    ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 ring-1 ring-indigo-200 dark:ring-indigo-500/30'
                    : 'text-gray-400 dark:text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700'
            ]"
            class="p-2 rounded-lg transition-colors flex items-center gap-1 min-w-[36px] min-h-[36px] justify-center"
            :title="({{ $cabinet }}.children || []).length > 0 ? ({{ $cabinet }}.children.length + ' section(s) - click to view') : 'Add sections'"
        >
            <x-heroicon-m-square-2-stack class="w-4 h-4" />
            <span x-show="({{ $cabinet }}.children || []).length > 0" class="text-xs font-bold" x-text="({{ $cabinet }}.children || []).length"></span>
        </button>
        
        {{-- Edit button (modal fallback) --}}
        <x-filament::icon-button
            icon="heroicon-m-pencil-square"
            color="gray"
            size="sm"
            tooltip="Edit Cabinet Details (Modal)"
            x-on:click="$wire.mountAction('editNode', { nodePath: selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex + '.children.' + {{ $rowIndex }} })"
        />
        
        {{-- Delete button --}}
        <x-filament::icon-button
            icon="heroicon-m-trash"
            color="danger"
            size="sm"
            tooltip="Delete Cabinet"
            x-on:click="if(confirm('Delete this cabinet?')) deleteCabinet({{ $rowIndex }})"
        />
    </div>
</td>
