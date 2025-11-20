{{-- View Type Toggle --}}
<div class="flex items-center gap-2 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3">
    <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">View:</span>

    <!-- Plan View -->
    <button
        @click="setViewType('plan')"
        :class="activeViewType === 'plan' ? 'ring-2 ring-primary-500 shadow-md transform scale-105' : ''"
        :style="activeViewType === 'plan' ? 'background-color: var(--primary-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
        class="px-3 py-2 rounded-lg text-sm font-semibold hover:scale-105 hover:shadow-sm transition-all dark:bg-gray-700 dark:text-white"
        title="Plan View (Top-Down)"
    >
        Plan
    </button>

    <!-- Elevation View -->
    <button
        @click="setViewType('elevation', 'front')"
        :class="activeViewType === 'elevation' ? 'ring-2 ring-warning-500 shadow-md transform scale-105' : ''"
        :style="activeViewType === 'elevation' ? 'background-color: var(--warning-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
        class="px-3 py-2 rounded-lg text-sm font-semibold hover:scale-105 hover:shadow-sm transition-all dark:bg-gray-700 dark:text-white"
        title="Elevation View (Side)"
    >
        Elevation
    </button>

    <!-- Section View -->
    <button
        @click="setViewType('section', 'A-A')"
        :class="activeViewType === 'section' ? 'ring-2 ring-info-500 shadow-md transform scale-105' : ''"
        :style="activeViewType === 'section' ? 'background-color: var(--info-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
        class="px-3 py-2 rounded-lg text-sm font-semibold hover:scale-105 hover:shadow-sm transition-all dark:bg-gray-700 dark:text-white"
        title="Section View (Cut-Through)"
    >
        Section
    </button>

    <!-- Detail View -->
    <button
        @click="setViewType('detail')"
        :class="activeViewType === 'detail' ? 'ring-2 ring-success-500 shadow-md transform scale-105' : ''"
        :style="activeViewType === 'detail' ? 'background-color: var(--success-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
        class="px-3 py-2 rounded-lg text-sm font-semibold hover:scale-105 hover:shadow-sm transition-all dark:bg-gray-700 dark:text-white"
        title="Detail View (Zoomed)"
    >
        Detail
    </button>

    <!-- Orientation Selector (for Elevation/Section) -->
    <template x-if="activeViewType === 'elevation' || activeViewType === 'section'">
        <select
            x-model="activeOrientation"
            @change="setOrientation(activeOrientation)"
            class="px-3 py-2 rounded-lg text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-primary-600"
        >
            <template x-if="activeViewType === 'elevation'">
                <optgroup label="Elevation">
                    <option value="front">Front</option>
                    <option value="back">Back</option>
                    <option value="left">Left</option>
                    <option value="right">Right</option>
                </optgroup>
            </template>
            <template x-if="activeViewType === 'section'">
                <optgroup label="Section">
                    <option value="A-A">A-A</option>
                    <option value="B-B">B-B</option>
                    <option value="C-C">C-C</option>
                </optgroup>
            </template>
        </select>
    </template>
</div>
