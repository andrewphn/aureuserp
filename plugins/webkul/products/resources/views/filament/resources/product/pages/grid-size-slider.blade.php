<div
    x-data="{
        sizes: ['small', 'medium', 'large'],
        currentIndex: {{ array_search($gridSize, ['small', 'medium', 'large']) }},
        labels: { small: 'S', medium: 'M', large: 'L' },
        setSize(index) {
            this.currentIndex = index;
            $wire.setGridSize(this.sizes[index]);
        }
    }"
    class="flex items-center gap-2"
>
    {{-- Small icon --}}
    <x-filament::icon
        icon="heroicon-o-squares-2x2"
        class="w-4 h-4 text-gray-400"
    />

    {{-- Slider track with clickable segments --}}
    <div class="flex items-center gap-1">
        <template x-for="(size, index) in sizes" :key="size">
            <button
                type="button"
                @click="setSize(index)"
                :class="{
                    'w-8 h-2 rounded-full transition-all duration-200 cursor-pointer': true,
                    'bg-primary-500': currentIndex >= index,
                    'bg-gray-200 hover:bg-gray-300': currentIndex < index
                }"
                :title="size.charAt(0).toUpperCase() + size.slice(1) + ' cards'"
            ></button>
        </template>
    </div>

    {{-- Large icon --}}
    <x-filament::icon
        icon="heroicon-o-rectangle-stack"
        class="w-4 h-4 text-gray-400"
    />
</div>
