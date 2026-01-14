<div class="cabinet-spec-builder"
     wire:key="cabinet-spec-builder"
     x-data="specAccordionBuilder(
         @entangle('specData'),
         @entangle('expanded'),
         @entangle('isAddingCabinet'),
         @entangle('newCabinetData'),
         @js($pricingTiers),
         @js($materialOptions),
         @js($finishOptions),
         @js(measurement_settings())
     )"
     x-init="init()"
     role="application"
     aria-label="Cabinet Specification Builder">

    {{-- Skip Links for Keyboard Navigation --}}
    <nav aria-label="Skip links" class="sr-only focus-within:not-sr-only focus-within:absolute focus-within:z-50 focus-within:top-0 focus-within:left-0 focus-within:right-0 focus-within:bg-white dark:focus-within:bg-gray-900 focus-within:p-2 focus-within:shadow-lg">
        <ul class="flex gap-4 justify-center">
            <li>
                <a 
                    href="#cabinet-summary-header" 
                    class="px-4 py-2 bg-primary-600 text-white rounded-md font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    Skip to summary
                </a>
            </li>
            <li>
                <a 
                    href="#room-navigation" 
                    class="px-4 py-2 bg-primary-600 text-white rounded-md font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    Skip to room navigation
                </a>
            </li>
            <li>
                <a 
                    href="#cabinet-inspector" 
                    class="px-4 py-2 bg-primary-600 text-white rounded-md font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    Skip to cabinet editor
                </a>
            </li>
        </ul>
    </nav>

    {{-- Global Screen Reader Announcements --}}
    <div 
        id="global-announcements" 
        aria-live="polite" 
        aria-atomic="true" 
        class="sr-only"
        x-ref="globalAnnouncements"
    ></div>

    {{-- Header with Totals --}}
    <header 
        id="cabinet-summary-header"
        class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 p-4 rounded-xl border bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
        role="banner"
        aria-labelledby="cabinet-spec-title"
    >
        <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-lg shadow-sm bg-white dark:bg-gray-800" aria-hidden="true">
                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            </div>
            <div>
                <h1 id="cabinet-spec-title" class="font-semibold text-gray-900 dark:text-white">Cabinet Specifications</h1>
                @if(count($specData) > 0)
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400" aria-label="{{ count($specData) }} room{{ count($specData) !== 1 ? 's' : '' }}">
                        {{ count($specData) }} room{{ count($specData) !== 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:gap-4" role="group" aria-label="Project totals">
            @if($totalLinearFeet > 0)
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-300">Total:</span>
                        <strong class="tabular-nums text-blue-600 dark:text-blue-400" aria-label="{{ format_linear_feet($totalLinearFeet) }} linear feet">{{ format_linear_feet($totalLinearFeet) }}</strong>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-300">Est:</span>
                        <strong class="tabular-nums text-green-600 dark:text-green-400" aria-label="Estimated price ${{ number_format($totalPrice, 0) }}">${{ number_format($totalPrice, 0) }}</strong>
                    </div>
                </div>
            @endif
        </div>
    </header>

    {{-- Breadcrumb Navigation --}}
    <nav aria-label="Breadcrumb" class="mb-4">
        @include('webkul-project::livewire.partials.spec-breadcrumb')
    </nav>

    {{-- Empty State --}}
    @if(empty($specData))
        <main 
            id="cabinet-inspector"
            role="main"
            aria-labelledby="empty-state-heading"
            class="text-center py-16 px-6 border-2 border-dashed rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-800/50"
        >
            <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700" aria-hidden="true">
                <x-heroicon-o-home class="w-8 h-8 text-gray-400" />
            </div>
            <h2 id="empty-state-heading" class="text-base font-medium mb-1 text-gray-900 dark:text-white">No rooms added yet</h2>
            <p class="text-sm mb-5 max-w-sm mx-auto text-gray-500 dark:text-gray-400">
                Start building your cabinet specification by adding your first room
            </p>
            {{-- Using Filament Action for Add Room --}}
            {{ $this->createRoomAction }}
        </main>
    @else
        {{-- Main Layout: Sidebar (40%) + Inspector (60%) --}}
        <div class="flex min-h-[550px] border rounded-xl overflow-hidden border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">

            {{-- Navigation Sidebar - 200px min, 280px max when expanded --}}
            <aside
                id="room-navigation"
                :style="sidebarCollapsed ? 'width: 56px; flex: 0 0 56px;' : 'flex: 0 0 280px; min-width: 200px; max-width: 280px;'"
                class="border-r transition-all duration-200 flex flex-col overflow-hidden border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
                role="navigation"
                aria-label="Room and location navigation"
            >
                {{-- Sidebar Header with Collapse Toggle --}}
                <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <h2 
                        x-show="!sidebarCollapsed" 
                        class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300"
                        id="rooms-heading"
                    >
                        Rooms
                    </h2>
                    <button
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        type="button"
                        class="p-1.5 rounded-lg text-gray-500 transition-colors hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        :aria-label="sidebarCollapsed ? 'Expand room navigation sidebar' : 'Collapse room navigation sidebar'"
                        :aria-expanded="!sidebarCollapsed"
                        aria-controls="sidebar-content"
                    >
                        <x-heroicon-o-chevron-left x-show="!sidebarCollapsed" class="w-4 h-4" aria-hidden="true" />
                        <x-heroicon-o-chevron-right x-show="sidebarCollapsed" class="w-4 h-4" aria-hidden="true" />
                    </button>
                </div>

                {{-- Accordion Tree (shown when sidebar expanded) --}}
                <div 
                    id="sidebar-content"
                    x-show="!sidebarCollapsed" 
                    x-cloak 
                    class="flex-1 overflow-y-auto p-2"
                    role="tree"
                    aria-labelledby="rooms-heading"
                >
                    @include('webkul-project::livewire.partials.spec-tree-accordion')
                </div>

                {{-- Icon Strip (shown when sidebar collapsed) --}}
                <div 
                    x-show="sidebarCollapsed" 
                    x-cloak 
                    class="flex-1 flex flex-col items-center py-3 gap-1.5 overflow-y-auto"
                    role="list"
                    aria-label="Room quick access"
                >
                    <template x-for="(room, roomIdx) in specData" :key="room.id || roomIdx">
                        <button
                            @click="selectRoom(roomIdx); sidebarCollapsed = false"
                            type="button"
                            :class="selectedRoomIndex === roomIdx
                                ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500'
                                : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="w-10 h-10 rounded-lg flex items-center justify-center text-xs font-bold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                            :aria-label="'Select room: ' + room.name"
                            :aria-current="selectedRoomIndex === roomIdx ? 'true' : 'false'"
                            role="listitem"
                        >
                            <span x-text="(room.name || 'R').charAt(0).toUpperCase()" aria-hidden="true"></span>
                        </button>
                    </template>
                    {{-- Filament Action: Add Room --}}
                    <button
                        wire:click="mountAction('createRoom')"
                        type="button"
                        class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        aria-label="Add new room"
                        role="listitem"
                    >
                        <x-heroicon-m-plus class="w-5 h-5" aria-hidden="true" />
                    </button>
                </div>
            </aside>

            {{-- Inspector Panel - fills remaining space --}}
            <main
                id="cabinet-inspector"
                style="flex: 1 1 auto; min-width: 0;"
                class="flex flex-col overflow-hidden"
                role="main"
                aria-label="Cabinet specification editor"
            >
                {{-- Inspector Content --}}
                <div class="flex-1 overflow-y-auto p-4">
                    @include('webkul-project::livewire.partials.spec-inspector')
                </div>
            </main>
        </div>

        {{-- Summary Footer --}}
        @if($totalLinearFeet > 0)
            <footer 
                class="mt-4 p-4 bg-gradient-to-r rounded-xl border shadow-sm from-blue-50 dark:from-blue-900/20 to-green-50 dark:to-green-900/20 border-blue-200/50 dark:border-blue-800/50"
                role="contentinfo"
                aria-labelledby="project-summary-heading"
            >
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <h2 id="project-summary-heading" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Project Summary</h2>
                    <div class="flex items-center gap-6" role="group" aria-label="Project totals summary">
                        <div class="text-right">
                            <div class="text-[10px] uppercase tracking-wider font-medium text-gray-500 dark:text-gray-300" id="total-lf-label">Total Linear Feet</div>
                            <div class="text-xl font-bold tabular-nums text-blue-600 dark:text-blue-400" aria-labelledby="total-lf-label">{{ format_linear_feet($totalLinearFeet) }}</div>
                        </div>
                        <div class="w-px h-10 hidden sm:block bg-gray-200 dark:bg-gray-700" aria-hidden="true"></div>
                        <div class="text-right">
                            <div class="text-[10px] uppercase tracking-wider font-medium text-gray-500 dark:text-gray-300" id="est-price-label">Estimated Price</div>
                            <div class="text-xl font-bold tabular-nums text-green-600 dark:text-green-400" aria-labelledby="est-price-label">${{ number_format($totalPrice, 0) }}</div>
                        </div>
                    </div>
                </div>
            </footer>
        @endif
    @endif

    {{-- Context Menu --}}
    <div
        x-show="contextMenu.show"
        x-cloak
        :style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px;`"
        class="fixed z-50 min-w-[180px] py-1 rounded-lg shadow-lg border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
        @click.away="closeContextMenu()"
        @keydown.escape="closeContextMenu()"
        role="menu"
        aria-label="Context menu options"
        x-trap.noscroll="contextMenu.show"
    >
        {{-- Edit --}}
        <button
            @click="contextMenuAction('edit')"
            type="button"
            role="menuitem"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700"
        >
            <x-heroicon-m-pencil-square class="w-4 h-4 text-blue-500" aria-hidden="true" />
            Edit
        </button>

        {{-- Duplicate --}}
        <button
            @click="contextMenuAction('duplicate')"
            type="button"
            role="menuitem"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700"
        >
            <x-heroicon-m-document-duplicate class="w-4 h-4 text-purple-500" aria-hidden="true" />
            Duplicate
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700" role="separator"></div>

        {{-- Move Up --}}
        <button
            @click="contextMenuAction('moveUp')"
            type="button"
            role="menuitem"
            :disabled="contextMenu.isFirst"
            :aria-disabled="contextMenu.isFirst"
            :class="contextMenu.isFirst ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700"
        >
            <x-heroicon-m-arrow-up class="w-4 h-4 text-gray-500" aria-hidden="true" />
            Move Up
        </button>

        {{-- Move Down --}}
        <button
            @click="contextMenuAction('moveDown')"
            type="button"
            role="menuitem"
            :disabled="contextMenu.isLast"
            :aria-disabled="contextMenu.isLast"
            :class="contextMenu.isLast ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700"
        >
            <x-heroicon-m-arrow-down class="w-4 h-4 text-gray-500" aria-hidden="true" />
            Move Down
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700" role="separator"></div>

        {{-- Add Child --}}
        <button
            @click="contextMenuAction('addChild')"
            type="button"
            role="menuitem"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700"
        >
            <x-heroicon-m-plus class="w-4 h-4 text-green-500" aria-hidden="true" />
            <span>Add <span x-text="contextMenu.childType"></span></span>
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700" role="separator"></div>

        {{-- Delete --}}
        <button
            @click="contextMenuAction('delete')"
            type="button"
            role="menuitem"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:bg-red-50 dark:focus:bg-red-900/20"
        >
            <x-heroicon-m-trash class="w-4 h-4" aria-hidden="true" />
            Delete
        </button>
    </div>

    {{-- Keyboard Shortcuts Help Dialog (triggered by Ctrl/Cmd+?) --}}
    <template x-teleport="body">
        <div
            x-show="showKeyboardHelp"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50"
            @keydown.escape.window="showKeyboardHelp = false"
            role="dialog"
            aria-modal="true"
            aria-labelledby="keyboard-help-title"
        >
            <div 
                class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full mx-4 max-h-[80vh] overflow-y-auto"
                @click.away="showKeyboardHelp = false"
                x-trap.noscroll="showKeyboardHelp"
            >
                <div class="flex justify-between items-center mb-4">
                    <h2 id="keyboard-help-title" class="text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h2>
                    <button 
                        @click="showKeyboardHelp = false"
                        type="button"
                        class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        aria-label="Close keyboard shortcuts help"
                    >
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                        <dt class="text-gray-600 dark:text-gray-300">Edit cell</dt>
                        <dd class="flex gap-1">
                            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">F2</kbd>
                            <span class="text-gray-400">or</span>
                            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Click</kbd>
                        </dd>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                        <dt class="text-gray-600 dark:text-gray-300">Save & move to next</dt>
                        <dd><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Tab</kbd> or <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Enter</kbd></dd>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                        <dt class="text-gray-600 dark:text-gray-300">Save & add another</dt>
                        <dd><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Shift</kbd> + <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Enter</kbd></dd>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                        <dt class="text-gray-600 dark:text-gray-300">Cancel edit</dt>
                        <dd><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Escape</kbd></dd>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                        <dt class="text-gray-600 dark:text-gray-300">Navigate cells</dt>
                        <dd><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">←</kbd> <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">→</kbd> <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">↑</kbd> <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">↓</kbd></dd>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                        <dt class="text-gray-600 dark:text-gray-300">First/Last cell</dt>
                        <dd><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Home</kbd> / <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">End</kbd></dd>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <dt class="text-gray-600 dark:text-gray-300">Show this help</dt>
                        <dd><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Ctrl</kbd> + <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">?</kbd></dd>
                    </div>
                </dl>
            </div>
        </div>
    </template>

    {{-- Filament Action Modals --}}
    <x-filament-actions::modals />
</div>

{{-- MeasurementFormatter is now initialized in the Alpine component's init() method --}}
