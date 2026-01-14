{{-- Molecule: Cabinet Table Cell --}}
{{-- Editable cell component that switches between display and edit modes with hover effects --}}

@props([
    'rowIndex' => null,
    'field' => '',
    'type' => 'text',
    'step' => null,
    'min' => null,
    'displayFormatter' => null, // Function name for formatting display value
    'class' => '',
])

<td class="px-1 py-1.5 {{ $class }}">
    {{-- Edit Mode --}}
    <template x-if="editingRow === {{ $rowIndex }} && editingField === '{{ $field }}'">
        <input
            type="{{ $type }}"
            @if($step) step="{{ $step }}" @endif
            @if($min) min="{{ $min }}" @endif
            x-model="cabinet.{{ $field }}"
            @blur="saveCabinetField({{ $rowIndex }}, '{{ $field }}', $event.target.value)"
            @keydown.enter.prevent.stop="saveCabinetField({{ $rowIndex }}, '{{ $field }}', $event.target.value); moveToNextCell({{ $rowIndex }}, '{{ $field }}', $event)"
            @keydown.tab.prevent.stop="saveCabinetField({{ $rowIndex }}, '{{ $field }}', $event.target.value); moveToNextCell({{ $rowIndex }}, '{{ $field }}', $event)"
            @keydown.escape="cancelEdit()"
            x-init="$nextTick(() => { $el.focus(); if ($el.type === 'number') $el.select(); })"
            class="w-full px-2.5 py-1.5 text-sm text-center border-2 border-primary-500 dark:border-primary-400 rounded-md focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:outline-none shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
        />
    </template>
    
    {{-- Display Mode with Hover Effect --}}
    <template x-if="!(editingRow === {{ $rowIndex }} && editingField === '{{ $field }}')">
        <button
            @click="startEdit({{ $rowIndex }}, '{{ $field }}')"
            class="w-full text-center px-2.5 py-1.5 rounded-md tabular-nums transition-all border border-transparent hover:border-dashed hover:border-primary-300 dark:hover:border-primary-600 hover:bg-gray-100 dark:hover:bg-gray-800/50 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 group"
            title="Click to edit (or press F2)"
        >
            <span x-text="{{ $displayFormatter ? $displayFormatter . '(cabinet.' . $field . ')' : "cabinet.$field || '-'" }}"></span>
            <x-heroicon-m-pencil-square class="w-3 h-3 ml-1 inline opacity-0 group-hover:opacity-50 dark:group-hover:opacity-40 transition-opacity" />
        </button>
    </template>
</td>
