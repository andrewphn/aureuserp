{{-- Navigation + Zoom Controls --}}
<div class="flex items-center gap-3 flex-wrap">
    <!-- Pagination Controls -->
    <div class="flex items-center gap-2 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3">
        <button
            @click="previousPage()"
            :disabled="currentPage <= 1"
            class="px-3 py-2 rounded-lg text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105 hover:shadow-md"
            style="background-color: var(--primary-600);"
            onmouseover="this.style.backgroundColor='var(--primary-700)'"
            onmouseout="this.style.backgroundColor='var(--primary-600)'"
            title="Previous Page"
        >
            <x-filament::icon icon="heroicon-o-chevron-left" class="h-4 w-4" />
        </button>

        <!-- Page number and type selector stacked vertically -->
        <div class="flex flex-col gap-1.5 min-w-[8rem]">
            <span class="text-sm text-gray-700 dark:text-white font-semibold text-center bg-gray-100 dark:bg-gray-700 px-4 py-2 rounded-lg" x-text="`Page ${currentPage} of ${totalPages}`"></span>

            <!-- Page Type Selector -->
            <div class="relative">
                <select
                    x-model="pageType"
                    @change="savePageType()"
                    class="w-full h-9 pl-3 pr-8 text-xs rounded-lg border-2 font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-primary-600"
                    :class="{
                        'border-blue-300 bg-blue-50 text-blue-900 dark:bg-blue-900/20 dark:text-blue-100 dark:border-blue-600': pageType === 'cover',
                        'border-green-300 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100 dark:border-green-600': pageType === 'floor_plan',
                        'border-purple-300 bg-purple-50 text-purple-900 dark:bg-purple-900/20 dark:text-purple-100 dark:border-purple-600': pageType === 'elevation',
                        'border-orange-300 bg-orange-50 text-orange-900 dark:bg-orange-900/20 dark:text-orange-100 dark:border-orange-600': pageType === 'detail',
                        'border-gray-300 bg-gray-50 text-gray-900 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600': pageType === 'other',
                        'border-gray-300 bg-white text-gray-500 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-600': !pageType
                    }"
                    title="Set page type for current page"
                >
                    <option value="">Type...</option>
                    <option value="cover">📋 Cover</option>
                    <option value="floor_plan">🏗️ Floor</option>
                    <option value="elevation">📐 Elev</option>
                    <option value="detail">🔍 Detail</option>
                    <option value="other">📄 Other</option>
                </select>

                <!-- Page Type Badge -->
                <div x-show="pageType" class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs font-bold rounded-full shadow-sm pointer-events-none" :class="{
                    'bg-blue-500 text-white': pageType === 'cover',
                    'bg-green-500 text-white': pageType === 'floor_plan',
                    'bg-purple-500 text-white': pageType === 'elevation',
                    'bg-orange-500 text-white': pageType === 'detail',
                    'bg-gray-500 text-white': pageType === 'other'
                }">
                    <span x-text="pageType === 'cover' ? 'C' : pageType === 'floor_plan' ? 'F' : pageType === 'elevation' ? 'E' : pageType === 'detail' ? 'D' : 'O'"></span>
                </div>
            </div>
        </div>

        <button
            @click="nextPage()"
            :disabled="currentPage >= totalPages"
            class="px-3 py-2 rounded-lg text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105 hover:shadow-md"
            style="background-color: var(--primary-600);"
            onmouseover="this.style.backgroundColor='var(--primary-700)'"
            onmouseout="this.style.backgroundColor='var(--primary-600)'"
            title="Next Page"
        >
            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4" />
        </button>
    </div>

    <!-- Zoom Controls -->
    <div class="flex items-center gap-2 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3">
        <button
            @click="zoomOut()"
            :disabled="zoomLevel <= zoomMin"
            class="px-3 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105 hover:shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
            title="Zoom Out"
        >
            <x-filament::icon icon="heroicon-o-minus" class="h-4 w-4" />
        </button>

        <span class="text-sm text-gray-700 dark:text-white font-semibold text-center bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded-lg min-w-[4rem]" x-text="`${getZoomPercentage()}%`"></span>

        <button
            @click="zoomIn()"
            :disabled="zoomLevel >= zoomMax"
            class="px-3 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105 hover:shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
            title="Zoom In"
        >
            <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
        </button>

        <button
            @click="resetZoom()"
            class="px-3 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-white text-xs font-semibold transition-all hover:scale-105 hover:shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
            title="Reset Zoom (100%)"
        >
            Reset
        </button>
    </div>
</div>
