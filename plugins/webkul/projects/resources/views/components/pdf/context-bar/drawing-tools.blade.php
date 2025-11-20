{{-- Drawing Tools --}}
<div class="flex items-center gap-2 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3">
    <!-- Draw Room Boundary (no pre-selection required) -->
    <button
        @click="setDrawMode('room')"
        :class="drawMode === 'room' ? 'ring-2 ring-warning-500 shadow-lg transform scale-105' : ''"
        :style="drawMode === 'room' ? 'background-color: var(--warning-600); color: white; border-color: var(--warning-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
        class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all flex items-center justify-center border dark:bg-gray-700 dark:text-white"
        title="Draw Room Boundary - Create new room"
    >
        <x-filament::icon icon="heroicon-o-home" class="h-5 w-5" />
    </button>

    <!-- Draw Room Location (only requires Room) -->
    <button
        @click="setDrawMode('location')"
        :class="drawMode === 'location' ? 'ring-2 ring-info-500 shadow-lg transform scale-105' : ''"
        :style="drawMode === 'location' ? 'background-color: var(--info-600); color: white; border-color: var(--info-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
        :disabled="!canDrawLocation()"
        class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
        title="Draw Room Location (Room required)"
    >
        <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5" />
    </button>

    <!-- Draw Cabinet Run (requires Room + Location) -->
    <button
        @click="setDrawMode('cabinet_run')"
        :class="drawMode === 'cabinet_run' ? 'ring-2 ring-primary-500 shadow-lg transform scale-105' : ''"
        :style="drawMode === 'cabinet_run' ? 'background-color: var(--primary-600); color: white; border-color: var(--primary-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
        :disabled="!canDraw()"
        class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
        title="Draw Cabinet Run (Room + Location required)"
    >
        <x-filament::icon icon="heroicon-o-rectangle-group" class="h-5 w-5" />
    </button>

    <!-- Draw Cabinet (requires Room + Location) -->
    <button
        @click="setDrawMode('cabinet')"
        :class="drawMode === 'cabinet' ? 'ring-2 ring-success-500 shadow-lg transform scale-105' : ''"
        :style="drawMode === 'cabinet' ? 'background-color: var(--success-600); color: white; border-color: var(--success-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
        :disabled="!canDraw()"
        class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
        title="Draw Cabinet (Room + Location required)"
    >
        <x-filament::icon icon="heroicon-o-cube" class="h-5 w-5" />
    </button>
</div>
