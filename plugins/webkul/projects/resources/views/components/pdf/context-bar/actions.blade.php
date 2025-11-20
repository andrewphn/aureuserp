{{-- Actions Section: Filter Status + Clear/Save/Close Buttons --}}

<!-- Filter Status Indicator -->
<div
    x-show="activeFiltersCount > 0"
    class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium"
    style="background-color: var(--primary-50); color: var(--primary-700);"
>
    <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4" />
    <span>
        Showing <strong x-text="filteredAnnotations.length"></strong> of <strong x-text="annotations.length"></strong>
    </span>
</div>

<!-- Clear Context Button -->
<button
    @click="clearContext()"
    class="px-3 py-2 rounded-lg text-white hover:scale-105 hover:shadow-md transition-all text-sm font-semibold flex items-center gap-2"
    style="background-color: var(--danger-600);"
    onmouseover="this.style.backgroundColor='var(--danger-700)'"
    onmouseout="this.style.backgroundColor='var(--danger-600)'"
    title="Clear Context"
>
    <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
    Clear
</button>

<!-- Save Annotations Button -->
<button
    @click="saveAnnotations()"
    class="px-4 py-2.5 rounded-lg text-white hover:scale-105 hover:shadow-lg transition-all text-sm font-bold shadow-md flex items-center gap-2 ring-2 ring-success-500/50"
    style="background-color: var(--success-600);"
    onmouseover="this.style.backgroundColor='var(--success-700)'"
    onmouseout="this.style.backgroundColor='var(--success-600)'"
    title="Save All Annotations"
>
    <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5" />
    Save
</button>

<!-- Close Viewer Button -->
<button
    @click="$dispatch('close-v3-modal')"
    class="px-2.5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 hover:scale-105 transition-all text-sm font-semibold flex items-center justify-center"
    title="Close Viewer"
>
    <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5" />
</button>
