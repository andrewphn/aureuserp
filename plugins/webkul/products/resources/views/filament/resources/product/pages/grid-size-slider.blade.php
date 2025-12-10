<div
    x-data="{
        sizes: ['small', 'medium', 'large'],
        currentIndex: {{ array_search($gridSize, ['small', 'medium', 'large']) }},
        setSize(index) {
            this.currentIndex = parseInt(index);
            $wire.setGridSize(this.sizes[this.currentIndex]);
        }
    }"
    class="flex items-center gap-3 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 rounded-lg"
>
    {{-- Small icon - more cards --}}
    <button
        type="button"
        @click="setSize(0)"
        :class="currentIndex === 0 ? 'text-primary-500' : 'text-gray-400 hover:text-gray-600'"
        title="Small cards (more per row)"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="3" y="3" width="7" height="7" rx="1" stroke-width="1.5"/>
            <rect x="14" y="3" width="7" height="7" rx="1" stroke-width="1.5"/>
            <rect x="3" y="14" width="7" height="7" rx="1" stroke-width="1.5"/>
            <rect x="14" y="14" width="7" height="7" rx="1" stroke-width="1.5"/>
        </svg>
    </button>

    {{-- Range slider --}}
    <input
        type="range"
        min="0"
        max="2"
        step="1"
        x-model="currentIndex"
        @change="setSize($event.target.value)"
        class="w-20 h-2 bg-gray-300 rounded-lg appearance-none cursor-pointer accent-primary-500"
        style="accent-color: rgb(var(--primary-500));"
    />

    {{-- Large icon - fewer cards --}}
    <button
        type="button"
        @click="setSize(2)"
        :class="currentIndex === 2 ? 'text-primary-500' : 'text-gray-400 hover:text-gray-600'"
        title="Large cards (fewer per row)"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/>
        </svg>
    </button>
</div>
