{{-- Left Sidebar (Project Tree) - Fixed with internal scroll --}}
<div class="tree-sidebar w-64 border-r border-gray-200 dark:border-gray-700 overflow-y-auto bg-white dark:bg-gray-800 p-4 flex-none">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Project Structure</h3>

        <div class="flex items-center gap-1">
            <!-- View Mode Toggle - Compact -->
            <div class="flex gap-0.5 p-0.5 bg-gray-100 dark:bg-gray-700 rounded">
                <button
                    @click="treeViewMode = 'room'"
                    :class="treeViewMode === 'room' ? 'bg-white dark:bg-gray-600 shadow-sm tree-sidebar__view-toggle--active' : 'tree-sidebar__view-toggle--inactive'"
                    class="p-1 rounded transition-all"
                    title="Group by Room"
                >
                    <x-filament::icon icon="heroicon-o-home" class="h-3.5 w-3.5" />
                </button>
                <button
                    @click="treeViewMode = 'page'"
                    :class="treeViewMode === 'page' ? 'bg-white dark:bg-gray-600 shadow-sm tree-sidebar__view-toggle--active' : 'tree-sidebar__view-toggle--inactive'"
                    class="p-1 rounded transition-all"
                    title="Group by Page"
                >
                    <x-filament::icon icon="heroicon-o-document-text" class="h-3.5 w-3.5" />
                </button>
            </div>

            <button
                @click="refreshTree()"
                class="tree-sidebar__refresh-button p-1 hover:opacity-80 transition-opacity"
                title="Refresh tree"
            >
                <x-filament::icon icon="heroicon-o-arrow-path" class="h-3.5 w-3.5" />
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-4">
        <span class="text-sm text-gray-500">Loading...</span>
    </div>

    <!-- Error State -->
    <div x-show="error" class="text-center py-4">
        <span class="text-sm text-red-600" x-text="error"></span>
    </div>

    <!-- Tree Content Views -->
    @include('webkul-project::components.pdf.tree.room-view')
    @include('webkul-project::components.pdf.tree.page-view')
</div>
