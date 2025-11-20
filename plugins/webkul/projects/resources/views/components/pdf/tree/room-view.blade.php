{{-- Tree Content - Room View --}}
<div x-show="!loading && !error && filteredTree && treeViewMode === 'room'">
    <template x-for="room in filteredTree" :key="room.id">
        <div class="tree-node mb-2">
            <!-- Room Level -->
            <div
                @click="handleNodeClick({ type: 'room', id: room.id, label: room.name, roomId: room.id })"
                @dblclick.prevent.stop="handleNodeDoubleClick('room', room.id)"
                @contextmenu.prevent.stop="showContextMenu($event, room.id, 'room', room.name)"
                :class="selectedPath.includes(room.id) ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
            >
                <button
                    @click.stop="toggleNode(room.id)"
                    class="w-4 h-4 flex items-center justify-center"
                >
                    <span x-show="isExpanded(room.id)">▼</span>
                    <span x-show="!isExpanded(room.id)">▶</span>
                </button>
                <span class="text-lg">🏠</span>
                <span class="text-sm font-medium flex-1" x-text="room.name"></span>
                <span
                    x-show="room.annotation_count > 0"
                    class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                    x-text="room.annotation_count"
                ></span>
                <!-- Visibility Toggle -->
                <button
                    @click.stop="window.PdfViewerManagers.VisibilityToggleManager.toggleRoomVisibility(room.id, $data)"
                    class="w-5 h-5 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                    :title="window.PdfViewerManagers.VisibilityToggleManager.isRoomVisible(room.id, $data) ? 'Hide room annotations' : 'Show room annotations'"
                >
                    <span x-show="window.PdfViewerManagers.VisibilityToggleManager.isRoomVisible(room.id, $data)" class="text-sm">👁️</span>
                    <span x-show="!window.PdfViewerManagers.VisibilityToggleManager.isRoomVisible(room.id, $data)" class="text-sm" style="text-decoration: line-through;">👁️</span>
                </button>
            </div>

            <!-- Locations (Children) -->
            <div x-show="isExpanded(room.id)" class="tree-hierarchy-indent">
                <template x-for="location in room.children" :key="location.id">
                    <div class="tree-node mb-1">
                        <!-- Location Level -->
                        <div
                            @click="handleNodeClick({ type: 'location', id: location.id, label: location.name, roomId: room.id, roomLocationId: location.id })"
                            @dblclick.prevent.stop="handleNodeDoubleClick('room_location', location.id, room.id)"
                            @contextmenu.prevent.stop="showContextMenu($event, location.id, 'room_location', location.name, room.id)"
                            :class="selectedPath.includes(location.id) ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-900 dark:text-indigo-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                        >
                            <button
                                @click.stop="toggleNode(location.id)"
                                class="w-4 h-4 flex items-center justify-center"
                            >
                                <span x-show="isExpanded(location.id)">▼</span>
                                <span x-show="!isExpanded(location.id)">▶</span>
                            </button>
                            <span class="text-lg">📍</span>
                            <span class="text-sm flex-1" x-text="location.name"></span>
                            <span
                                x-show="location.annotation_count > 0"
                                class="badge bg-indigo-600 text-white px-2 py-0.5 rounded-full text-xs"
                                x-text="location.annotation_count"
                            ></span>
                            <!-- Visibility Toggle -->
                            <button
                                @click.stop="window.PdfViewerManagers.VisibilityToggleManager.toggleLocationVisibility(location.id, $data)"
                                class="w-5 h-5 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                :title="window.PdfViewerManagers.VisibilityToggleManager.isLocationVisible(location.id, $data) ? 'Hide location annotations' : 'Show location annotations'"
                            >
                                <span x-show="window.PdfViewerManagers.VisibilityToggleManager.isLocationVisible(location.id, $data)" class="text-sm">👁️</span>
                                <span x-show="!window.PdfViewerManagers.VisibilityToggleManager.isLocationVisible(location.id, $data)" class="text-sm" style="text-decoration: line-through;">👁️</span>
                            </button>
                        </div>

                        <!-- Cabinet Runs (Children) -->
                        <div x-show="isExpanded(location.id)" class="tree-hierarchy-indent">
                            <template x-for="run in location.children" :key="run.id">
                                <div class="tree-node mb-1">
                                    <!-- Cabinet Run Level -->
                                    <div
                                        @click="handleNodeClick({ type: 'cabinet_run', id: run.id, label: run.name, locationId: location.id, roomId: room.id, cabinetRunId: run.id })"
                                        @dblclick.prevent.stop="handleTreeNodeDoubleClick('cabinet_run', run.id, run.name, room.id, room.name, location.id, location.name, run.id)"
                                        @contextmenu.prevent.stop="showContextMenu($event, run.id, 'cabinet_run', run.name, room.id, location.id)"
                                        :class="selectedPath.includes(run.id) ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                        class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                    >
                                        <button
                                            @click.stop="toggleNode(run.id)"
                                            class="w-4 h-4 flex items-center justify-center"
                                            x-show="run.children && run.children.length > 0"
                                        >
                                            <span x-show="isExpanded(run.id)">▼</span>
                                            <span x-show="!isExpanded(run.id)">▶</span>
                                        </button>
                                        <span class="w-4" x-show="!run.children || run.children.length === 0"></span>
                                        <span class="text-base">📦</span>
                                        <span class="flex-1" x-text="run.name"></span>
                                        <span
                                            x-show="run.annotation_count > 0"
                                            class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                            x-text="run.annotation_count"
                                        ></span>
                                        <!-- Visibility Toggle -->
                                        <button
                                            @click.stop="window.PdfViewerManagers.VisibilityToggleManager.toggleCabinetRunVisibility(run.id, $data)"
                                            class="w-5 h-5 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                            :title="window.PdfViewerManagers.VisibilityToggleManager.isCabinetRunVisible(run.id, $data) ? 'Hide cabinet run annotations' : 'Show cabinet run annotations'"
                                        >
                                            <span x-show="window.PdfViewerManagers.VisibilityToggleManager.isCabinetRunVisible(run.id, $data)" class="text-sm">👁️</span>
                                            <span x-show="!window.PdfViewerManagers.VisibilityToggleManager.isCabinetRunVisible(run.id, $data)" class="text-sm" style="text-decoration: line-through;">👁️</span>
                                        </button>
                                    </div>

                                    <!-- Cabinets (Children) -->
                                    <div x-show="isExpanded(run.id)" class="tree-hierarchy-indent">
                                        <template x-for="cabinet in run.children" :key="cabinet.id">
                                            <div class="tree-node mb-1">
                                                <!-- Cabinet Level (Leaf Node) -->
                                                <div
                                                    @click="handleNodeClick({ type: 'cabinet', id: cabinet.id, label: cabinet.name, locationId: location.id, roomId: room.id, cabinetRunId: run.id })"
                                                    @dblclick.prevent.stop="handleNodeDoubleClick('cabinet', cabinet.id, room.id, location.id, run.id)"
                                                    @contextmenu.prevent.stop="showContextMenu($event, cabinet.id, 'cabinet', cabinet.name, room.id, location.id, run.id)"
                                                    :class="selectedPath.includes(cabinet.id) ? 'bg-purple-100 dark:bg-purple-900 text-purple-900 dark:text-purple-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                    class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-xs"
                                                >
                                                    <span class="w-4"></span>
                                                    <span class="text-sm">🗄️</span>
                                                    <span class="flex-1" x-text="cabinet.name"></span>
                                                    <!-- Visibility Toggle -->
                                                    <button
                                                        @click.stop="window.PdfViewerManagers.VisibilityToggleManager.toggleAnnotationVisibility(cabinet.id, $data)"
                                                        class="w-5 h-5 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                                        :title="window.PdfViewerManagers.VisibilityToggleManager.isAnnotationVisible(cabinet.id, $data) ? 'Hide cabinet' : 'Show cabinet'"
                                                    >
                                                        <span x-show="window.PdfViewerManagers.VisibilityToggleManager.isAnnotationVisible(cabinet.id, $data)" class="text-sm">👁️</span>
                                                        <span x-show="!window.PdfViewerManagers.VisibilityToggleManager.isAnnotationVisible(cabinet.id, $data)" class="text-sm" style="text-decoration: line-through;">👁️</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- Add Room Button -->
    <button
        @click="roomSearchQuery = ''; showRoomDropdown = true; $nextTick(() => $el.nextElementSibling.querySelector('input')?.focus())"
        class="w-full mt-4 px-3 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
    >
        + Add Room
    </button>
</div>
