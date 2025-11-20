{{-- Context Bar (Top - Fixed) --}}
<div class="context-bar flex-none z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-md">
    <div class="p-3 md:p-4 flex items-center gap-4 md:gap-6 flex-wrap">

        <!-- GROUP 1: Context Selection (Room + Location) -->
        <div class="flex items-center gap-3 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3 flex-1 min-w-fit">
            @include('webkul-project::components.pdf.context-bar.room-selector')
            @include('webkul-project::components.pdf.context-bar.location-selector')
        </div>

        <!-- GROUP 2: Navigation + Zoom -->
        @include('webkul-project::components.pdf.context-bar.navigation-controls')

        <!-- GROUP 3: Drawing Tools -->
        @include('webkul-project::components.pdf.context-bar.drawing-tools')

        <!-- GROUP 4: View Type Toggle -->
        @include('webkul-project::components.pdf.context-bar.view-type-toggle')

        <!-- GROUP 5: Actions (Filter + Status + Buttons) -->
        <div class="flex items-center gap-3 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3 ml-auto">
            @include('webkul-project::components.pdf.context-bar.filter-panel')
            @include('webkul-project::components.pdf.context-bar.actions')
        </div>
    </div>

    <!-- Context Hint -->
    <div x-show="!canDrawLocation()" class="mt-3 flex items-center gap-2 text-sm font-medium px-3 py-2 rounded-lg border" style="background-color: var(--warning-50); border-color: var(--warning-200); color: var(--warning-700);">
        <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4" />
        <span>Select a Room to draw Locations, or Room + Location to draw Cabinet Runs/Cabinets</span>
    </div>

    <!-- PDF Loading Status -->
    <div x-show="!pdfReady" class="mt-3 flex items-center gap-2 text-sm font-medium px-3 py-2 rounded-lg border" style="background-color: var(--info-50); border-color: var(--info-200); color: var(--info-700);">
        <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 animate-spin" />
        <span>Loading PDF dimensions...</span>
    </div>
</div>
