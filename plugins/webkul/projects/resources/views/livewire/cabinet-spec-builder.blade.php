<div class="cabinet-spec-builder"
     wire:key="cabinet-spec-builder"
     x-data="specAccordionBuilder(
         @entangle('specData'),
         @entangle('expanded'),
         @js($pricingTiers),
         @js($materialOptions),
         @js($finishOptions)
     )"
     x-init="init()">

    {{-- Header with Totals --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 p-4 rounded-xl border bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-lg shadow-sm bg-white dark:bg-gray-700">
                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            </div>
            <div>
                <span class="font-semibold text-gray-900 dark:text-white">Cabinet Specifications</span>
                @if(count($specData) > 0)
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ count($specData) }} room{{ count($specData) !== 1 ? 's' : '' }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:gap-4">
            @if($totalLinearFeet > 0)
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-300">Total:</span>
                        <strong class="tabular-nums text-blue-600 dark:text-blue-400">{{ format_linear_feet($totalLinearFeet) }}</strong>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-300">Est:</span>
                        <strong class="tabular-nums text-green-600 dark:text-green-400">${{ number_format($totalPrice, 0) }}</strong>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Breadcrumb Navigation --}}
    @include('webkul-project::livewire.partials.spec-breadcrumb')

    {{-- Empty State --}}
    @if(empty($specData))
        <div class="text-center py-16 px-6 border-2 border-dashed rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-800/50">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                <x-heroicon-o-home class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-base font-medium mb-1 text-gray-900 dark:text-white">No rooms added yet</h3>
            <p class="text-sm mb-5 max-w-sm mx-auto text-gray-500 dark:text-gray-400">
                Start building your cabinet specification by adding your first room
            </p>
            {{-- Using Filament Action for Add Room --}}
            {{ $this->createRoomAction }}
        </div>
    @else
        {{-- Main Layout: Sidebar (40%) + Inspector (60%) --}}
        <div class="flex min-h-[550px] border rounded-xl overflow-hidden border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">

            {{-- Navigation Sidebar - 200px min, 280px max when expanded --}}
            <div
                :style="sidebarCollapsed ? 'width: 56px; flex: 0 0 56px;' : 'flex: 0 0 280px; min-width: 200px; max-width: 280px;'"
                class="border-r transition-all duration-200 flex flex-col overflow-hidden border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
            >
                {{-- Sidebar Header with Collapse Toggle --}}
                <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <span x-show="!sidebarCollapsed" class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Rooms</span>
                    <button
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="p-1.5 rounded-lg text-gray-500 transition-colors hover:bg-gray-200 dark:hover:bg-gray-700"
                        :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    >
                        <x-heroicon-o-chevron-left x-show="!sidebarCollapsed" class="w-4 h-4" />
                        <x-heroicon-o-chevron-right x-show="sidebarCollapsed" class="w-4 h-4" />
                    </button>
                </div>

                {{-- Accordion Tree (shown when sidebar expanded) --}}
                <div x-show="!sidebarCollapsed" x-cloak class="flex-1 overflow-y-auto p-2">
                    @include('webkul-project::livewire.partials.spec-tree-accordion')
                </div>

                {{-- Icon Strip (shown when sidebar collapsed) --}}
                <div x-show="sidebarCollapsed" x-cloak class="flex-1 flex flex-col items-center py-3 gap-1.5 overflow-y-auto">
                    <template x-for="(room, roomIdx) in specData" :key="room.id || roomIdx">
                        <button
                            @click="selectRoom(roomIdx); sidebarCollapsed = false"
                            :class="selectedRoomIndex === roomIdx
                                ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500'
                                : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="w-10 h-10 rounded-lg flex items-center justify-center text-xs font-bold shadow-sm transition-colors"
                            :title="room.name"
                        >
                            <span x-text="(room.name || 'R').charAt(0).toUpperCase()"></span>
                        </button>
                    </template>
                    {{-- Filament Action: Add Room --}}
                    <button
                        wire:click="mountAction('createRoom')"
                        class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:text-primary-600 dark:hover:text-primary-400"
                        title="Add Room"
                    >
                        <x-heroicon-m-plus class="w-5 h-5" />
                    </button>
                </div>
            </div>

            {{-- Inspector Panel - fills remaining space --}}
            <div
                style="flex: 1 1 auto; min-width: 0;"
                class="flex flex-col overflow-hidden"
            >
                {{-- Inspector Content --}}
                <div class="flex-1 overflow-y-auto p-4">
                    @include('webkul-project::livewire.partials.spec-inspector')
                </div>
            </div>
        </div>

        {{-- Summary Footer --}}
        @if($totalLinearFeet > 0)
            <div class="mt-4 p-4 bg-gradient-to-r rounded-xl border shadow-sm from-blue-50 dark:from-blue-900/20 to-green-50 dark:to-green-900/20 border-blue-200/50 dark:border-blue-800/50">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Project Summary</span>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <div class="text-[10px] uppercase tracking-wider font-medium text-gray-500 dark:text-gray-300">Total Linear Feet</div>
                            <div class="text-xl font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ format_linear_feet($totalLinearFeet) }}</div>
                        </div>
                        <div class="w-px h-10 hidden sm:block bg-gray-200 dark:bg-gray-700"></div>
                        <div class="text-right">
                            <div class="text-[10px] uppercase tracking-wider font-medium text-gray-500 dark:text-gray-300">Estimated Price</div>
                            <div class="text-xl font-bold tabular-nums text-green-600 dark:text-green-400">${{ number_format($totalPrice, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Context Menu --}}
    <div
        x-show="contextMenu.show"
        x-cloak
        :style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px;`"
        class="fixed z-50 min-w-[180px] py-1 rounded-lg shadow-lg border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
        @click.away="closeContextMenu()"
    >
        {{-- Edit --}}
        <button
            @click="contextMenuAction('edit')"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            <x-heroicon-m-pencil-square class="w-4 h-4 text-blue-500" />
            Edit
        </button>

        {{-- Duplicate --}}
        <button
            @click="contextMenuAction('duplicate')"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            <x-heroicon-m-document-duplicate class="w-4 h-4 text-purple-500" />
            Duplicate
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Move Up --}}
        <button
            @click="contextMenuAction('moveUp')"
            :disabled="contextMenu.isFirst"
            :class="contextMenu.isFirst ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-arrow-up class="w-4 h-4 text-gray-500" />
            Move Up
        </button>

        {{-- Move Down --}}
        <button
            @click="contextMenuAction('moveDown')"
            :disabled="contextMenu.isLast"
            :class="contextMenu.isLast ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-arrow-down class="w-4 h-4 text-gray-500" />
            Move Down
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Add Child --}}
        <button
            @click="contextMenuAction('addChild')"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            <x-heroicon-m-plus class="w-4 h-4 text-green-500" />
            <span>Add <span x-text="contextMenu.childType"></span></span>
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Delete --}}
        <button
            @click="contextMenuAction('delete')"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
        >
            <x-heroicon-m-trash class="w-4 h-4" />
            Delete
        </button>
    </div>

    {{-- Filament Action Modals --}}
    <x-filament-actions::modals />
</div>

@script
<script>
// Initialize MeasurementFormatter with settings from PHP
if (window.MeasurementFormatter) {
    window.MeasurementFormatter.init(@js(measurement_settings()));
}
</script>
@endscript
