{{-- Breadcrumb Navigation --}}
<nav
    class="flex items-center gap-1 mb-3 px-1 text-sm"
    x-show="selectedRoom"
    x-cloak
>
    {{-- Spec Builder root --}}
    <button
        @click="selectedRoomIndex = null; selectedRoom = null; selectedLocationIndex = null; selectedLocation = null; selectedRunIndex = null; selectedRun = null;"
        class="font-medium text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
    >
        Spec Builder
    </button>

    {{-- Room --}}
    <template x-if="selectedRoom">
        <span class="flex items-center gap-1">
            <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400" />
            <button
                @click="clearToRoom()"
                :class="!selectedLocation ? 'font-medium text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400'"
                class="transition-colors"
                x-text="selectedRoom?.name || 'Untitled'"
            ></button>
        </span>
    </template>

    {{-- Location --}}
    <template x-if="selectedLocation">
        <span class="flex items-center gap-1">
            <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400" />
            <button
                @click="clearToLocation()"
                :class="!selectedRun ? 'font-medium text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400'"
                class="transition-colors"
                x-text="selectedLocation?.name || 'Untitled'"
            ></button>
        </span>
    </template>

    {{-- Run --}}
    <template x-if="selectedRun">
        <span class="flex items-center gap-1">
            <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400" />
            <span class="font-medium text-gray-900 dark:text-gray-100" x-text="selectedRun?.name || 'Untitled'"></span>
        </span>
    </template>
</nav>
