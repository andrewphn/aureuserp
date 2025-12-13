@php
    $isAddingHere = $this->isAddingCabinet && $this->addingToRunPath === $runPath;
    $hasCabinets = !empty($cabinets);
    $totalLF = collect($cabinets)->sum(fn($c) => ($c['linear_feet'] ?? 0));
@endphp

<div
    class="mt-2 mb-3 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
    x-data="{
        fields: ['name', 'width', 'depth', 'height', 'qty'],
        refs: {
            name: 'nameInput',
            width: 'widthInput',
            depth: 'depthInput',
            height: 'heightInput',
            qty: 'qtyInput'
        },
        focusNext(current) {
            const idx = this.fields.indexOf(current);
            if (idx < this.fields.length - 1) {
                const nextField = this.fields[idx + 1];
                this.$refs[this.refs[nextField]]?.focus();
            } else {
                this.$wire.saveCabinet(false);
            }
        },
        focusPrev(current) {
            const idx = this.fields.indexOf(current);
            if (idx > 0) {
                const prevField = this.fields[idx - 1];
                this.$refs[this.refs[prevField]]?.focus();
            }
        }
    }"
>
    {{-- Summary Header --}}
    <div class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
            {{ count($cabinets) }} cabinet{{ count($cabinets) !== 1 ? 's' : '' }}
        </span>
        @if($totalLF > 0)
            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                {{ number_format($totalLF, 1) }} LF Total
            </span>
        @endif
    </div>

    {{-- Table Container with horizontal scroll on small screens --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[500px]">
            {{-- Header Row --}}
            <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide bg-gray-50/50 dark:bg-gray-800/50">
                <tr class="border-b border-gray-100 dark:border-gray-700">
                    <th class="px-3 py-2.5 text-left font-medium" style="min-width: 120px;">Cabinet</th>
                    <th class="px-2 py-2.5 text-left font-medium" style="width: 70px;">Width</th>
                    <th class="px-2 py-2.5 text-left font-medium" style="width: 70px;">Depth</th>
                    <th class="px-2 py-2.5 text-left font-medium" style="width: 70px;">Height</th>
                    <th class="px-2 py-2.5 text-center font-medium" style="width: 50px;">Qty</th>
                    <th class="px-2 py-2.5 text-right font-medium" style="width: 60px;">LF</th>
                    <th class="px-2 py-2.5" style="width: 70px;"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                {{-- Existing Cabinets --}}
                @foreach($cabinets as $index => $cabinet)
                    @php
                        $cabinetPath = $runPath . '.children.' . $index;
                        $cabinetLF = $cabinet['linear_feet'] ?? (($cabinet['length_inches'] ?? 0) / 12 * ($cabinet['quantity'] ?? 1));
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 group transition-colors duration-100" wire:key="cabinet-row-{{ $cabinetPath }}">
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1.5">
                                {{-- Sequential name (B1, W1, T1) --}}
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $cabinet['name'] ?: '-' }}
                                </span>
                                {{-- Cabinet code (DB18, SB36) if available --}}
                                @if(!empty($cabinet['code']))
                                    <span class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded font-mono">
                                        {{ $cabinet['code'] }}
                                    </span>
                                @elseif(!empty($cabinet['cabinet_type']))
                                    <span class="text-xs text-gray-400 dark:text-gray-500 hidden sm:inline">{{ ucfirst($cabinet['cabinet_type']) }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-2 py-2 text-gray-600 dark:text-gray-300 tabular-nums">
                            {{ $cabinet['length_inches'] ?? '-' }}"
                        </td>
                        <td class="px-2 py-2 text-gray-600 dark:text-gray-300 tabular-nums">
                            {{ $cabinet['depth_inches'] ?? '-' }}"
                        </td>
                        <td class="px-2 py-2 text-gray-600 dark:text-gray-300 tabular-nums">
                            {{ $cabinet['height_inches'] ?? '-' }}"
                        </td>
                        <td class="px-2 py-2 text-center text-gray-600 dark:text-gray-300 tabular-nums">
                            {{ $cabinet['quantity'] ?? 1 }}
                        </td>
                        <td class="px-2 py-2 text-right font-semibold text-blue-600 dark:text-blue-400 tabular-nums">
                            {{ number_format($cabinetLF, 1) }}
                        </td>
                        <td class="px-2 py-2">
                            <div class="opacity-0 group-hover:opacity-100 flex items-center gap-0.5 justify-end transition-opacity duration-100">
                                <button
                                    wire:click="openEdit('{{ $cabinetPath }}')"
                                    type="button"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                    title="Edit details"
                                >
                                    <x-heroicon-m-pencil class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    wire:click="delete('{{ $cabinetPath }}')"
                                    wire:confirm="Delete this cabinet?"
                                    type="button"
                                    class="p-1.5 text-gray-400 hover:text-danger-600 dark:hover:text-danger-400 rounded-md hover:bg-danger-50 dark:hover:bg-danger-900/20 transition-colors"
                                    title="Delete"
                                >
                                    <x-heroicon-m-trash class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                {{-- New Cabinet Entry Row --}}
                @if($isAddingHere)
                    <tr class="bg-primary-50/60 dark:bg-primary-900/30 border-y-2 border-primary-200 dark:border-primary-700">
                        <td class="px-3 py-2">
                            <input
                                type="text"
                                wire:model.live.debounce.150ms="newCabinetData.name"
                                x-ref="nameInput"
                                x-init="$nextTick(() => $el.focus())"
                                @keydown.tab.prevent="focusNext('name')"
                                @keydown.enter.prevent="$wire.saveCabinet(false)"
                                @keydown.shift.enter.prevent="$wire.saveCabinet(true)"
                                @keydown.escape="$wire.cancelAdd()"
                                class="w-full h-8 px-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow font-mono"
                                placeholder="DB18, SB36..."
                            />
                        </td>
                        <td class="px-2 py-2">
                            <input
                                type="text"
                                wire:model.live.debounce.150ms="newCabinetData.length_inches"
                                x-ref="widthInput"
                                @keydown.tab.prevent="focusNext('width')"
                                @keydown.shift.tab.prevent="focusPrev('width')"
                                @keydown.enter.prevent="$wire.saveCabinet(false)"
                                @keydown.shift.enter.prevent="$wire.saveCabinet(true)"
                                @keydown.escape="$wire.cancelAdd()"
                                class="w-full h-8 px-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow tabular-nums"
                                placeholder="24"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <input
                                type="text"
                                wire:model.blur="newCabinetData.depth_inches"
                                x-ref="depthInput"
                                @keydown.tab.prevent="focusNext('depth')"
                                @keydown.shift.tab.prevent="focusPrev('depth')"
                                @keydown.enter.prevent="$wire.saveCabinet(false)"
                                @keydown.shift.enter.prevent="$wire.saveCabinet(true)"
                                @keydown.escape="$wire.cancelAdd()"
                                class="w-full h-8 px-2 text-sm border border-gray-200 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700/70 text-gray-500 dark:text-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:bg-white dark:focus:bg-gray-700 transition-all tabular-nums"
                                placeholder="24"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <input
                                type="text"
                                wire:model.blur="newCabinetData.height_inches"
                                x-ref="heightInput"
                                @keydown.tab.prevent="focusNext('height')"
                                @keydown.shift.tab.prevent="focusPrev('height')"
                                @keydown.enter.prevent="$wire.saveCabinet(false)"
                                @keydown.shift.enter.prevent="$wire.saveCabinet(true)"
                                @keydown.escape="$wire.cancelAdd()"
                                class="w-full h-8 px-2 text-sm border border-gray-200 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700/70 text-gray-500 dark:text-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:bg-white dark:focus:bg-gray-700 transition-all tabular-nums"
                                placeholder="34.5"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <input
                                type="number"
                                wire:model.live="newCabinetData.quantity"
                                x-ref="qtyInput"
                                @keydown.tab.prevent="focusNext('qty')"
                                @keydown.shift.tab.prevent="focusPrev('qty')"
                                @keydown.enter.prevent="$wire.saveCabinet(false)"
                                @keydown.shift.enter.prevent="$wire.saveCabinet(true)"
                                @keydown.escape="$wire.cancelAdd()"
                                class="w-full h-8 px-2 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow tabular-nums"
                                min="1"
                                placeholder="1"
                            />
                        </td>
                        <td class="px-2 py-2 text-right">
                            <span class="font-semibold text-primary-600 dark:text-primary-400 tabular-nums">
                                {{ number_format($this->getNewCabinetLF(), 1) }}
                            </span>
                        </td>
                        <td class="px-2 py-2">
                            <div class="flex items-center gap-0.5 justify-end">
                                <button
                                    wire:click="saveCabinet(false)"
                                    type="button"
                                    class="p-1.5 text-green-600 hover:text-green-700 dark:text-green-400 rounded-md hover:bg-green-50 dark:hover:bg-green-900/30 transition-colors"
                                    title="Save (Enter)"
                                >
                                    <x-heroicon-m-check class="w-4 h-4" />
                                </button>
                                <button
                                    wire:click="cancelAdd"
                                    type="button"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                    title="Cancel (Esc)"
                                >
                                    <x-heroicon-m-x-mark class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Footer with Add Button and Keyboard Hints --}}
    <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
        @if($isAddingHere)
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-[10px] font-mono">Tab</kbd>
                    <span class="hidden sm:inline">Next</span>
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-[10px] font-mono">Enter</kbd>
                    <span class="hidden sm:inline">Save</span>
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-[10px] font-mono">⇧+Enter</kbd>
                    <span class="hidden sm:inline">Save & Add</span>
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-[10px] font-mono">Esc</kbd>
                    <span class="hidden sm:inline">Cancel</span>
                </span>
            </div>
        @else
            <button
                wire:click="startAddCabinet('{{ $runPath }}')"
                type="button"
                class="inline-flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium px-2.5 py-1.5 rounded-md hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-colors"
            >
                <x-heroicon-m-plus class="w-3.5 h-3.5" />
                Add Cabinet
            </button>
        @endif

        @if(!$isAddingHere && $hasCabinets)
            <span class="text-xs text-gray-400 dark:text-gray-500">Hover row to edit</span>
        @endif
    </div>
</div>
