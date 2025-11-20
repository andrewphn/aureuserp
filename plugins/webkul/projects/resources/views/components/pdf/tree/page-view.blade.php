{{-- Tree Content - Page View --}}
<div x-show="!loading && !error && treeViewMode === 'page'">
    <template x-for="page in getPageGroupedAnnotations()" :key="page.pageNumber">
        <div class="tree-node mb-2">
            <!-- Page Level -->
            <div
                @click="goToPage(page.pageNumber)"
                :class="currentPage === page.pageNumber ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
            >
                <button
                    @click.stop="toggleNode('page_' + page.pageNumber)"
                    class="w-4 h-4 flex items-center justify-center"
                >
                    <span x-show="isExpanded('page_' + page.pageNumber)">▼</span>
                    <span x-show="!isExpanded('page_' + page.pageNumber)">▶</span>
                </button>
                <span class="text-lg">📄</span>
                <span class="text-sm font-medium flex-1" x-text="`Page ${page.pageNumber}`"></span>
                <span
                    x-show="page.annotations.length > 0"
                    class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                    x-text="page.annotations.length"
                ></span>
            </div>

            <!-- Annotations on this page (hierarchical) -->
            <div x-show="isExpanded('page_' + page.pageNumber)" class="tree-hierarchy-indent">
                <template x-for="anno in page.annotations" :key="anno.id">
                    <div class="tree-node mb-1">
                        <!-- Root Annotation (Room or orphan) -->
                        <div
                            @click="handleNodeClick(anno)"
                            @dblclick.prevent.stop="anno.type === 'room' && handleTreeNodeDoubleClick('room', anno.roomId, anno.label, anno.roomId, anno.label)"
                            :class="selectedAnnotation?.id === anno.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                        >
                            <!-- Expand/collapse button if has children -->
                            <button
                                x-show="anno.children && anno.children.length > 0"
                                @click.stop="toggleNode('anno_' + anno.id)"
                                class="w-4 h-4 flex items-center justify-center"
                            >
                                <span x-show="isExpanded('anno_' + anno.id)">▼</span>
                                <span x-show="!isExpanded('anno_' + anno.id)">▶</span>
                            </button>
                            <span class="w-4" x-show="!anno.children || anno.children.length === 0"></span>

                            <span x-text="anno.type === 'room' ? '🏠' : anno.type === 'location' ? '📍' : anno.type === 'cabinet_run' ? '📦' : '🗄️'"></span>
                            <span class="flex-1" x-text="anno.label"></span>

                            <!-- Children count badge -->
                            <span
                                x-show="anno.children && anno.children.length > 0"
                                class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                x-text="anno.children.length"
                            ></span>
                        </div>

                        <!-- Location Children (Level 2) -->
                        <div x-show="isExpanded('anno_' + anno.id)" class="tree-hierarchy-indent">
                            <template x-for="location in anno.children" :key="location.id">
                                <div class="tree-node mb-1">
                                    <!-- Location Level -->
                                    <div
                                        @click="handleNodeClick(location)"
                                        @dblclick.prevent.stop="location.type === 'location' && handleTreeNodeDoubleClick('location', location.roomLocationId, location.label, anno.roomId, anno.label, location.roomLocationId, location.label)"
                                        :class="selectedAnnotation?.id === location.id ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-900 dark:text-indigo-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                        class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                    >
                                        <!-- Expand/collapse button if has children -->
                                        <button
                                            x-show="location.children && location.children.length > 0"
                                            @click.stop="toggleNode('anno_' + location.id)"
                                            class="w-4 h-4 flex items-center justify-center"
                                        >
                                            <span x-show="isExpanded('anno_' + location.id)">▼</span>
                                            <span x-show="!isExpanded('anno_' + location.id)">▶</span>
                                        </button>
                                        <span class="w-4" x-show="!location.children || location.children.length === 0"></span>

                                        <span>📍</span>
                                        <span class="flex-1" x-text="location.label"></span>

                                        <!-- Children count badge -->
                                        <span
                                            x-show="location.children && location.children.length > 0"
                                            class="badge bg-indigo-600 text-white px-2 py-0.5 rounded-full text-xs"
                                            x-text="location.children.length"
                                        ></span>
                                    </div>

                                    <!-- Cabinet Run Children (Level 3) -->
                                    <div x-show="isExpanded('anno_' + location.id)" class="tree-hierarchy-indent">
                                        <template x-for="run in location.children" :key="run.id">
                                            <div class="tree-node mb-1">
                                                <!-- Cabinet Run Level -->
                                                <div
                                                    @click="handleNodeClick(run)"
                                                    @dblclick.prevent.stop="run.type === 'cabinet_run' && handleTreeNodeDoubleClick('cabinet_run', run.cabinetRunId, run.label, anno.roomId, anno.label, location.roomLocationId, location.label, run.cabinetRunId)"
                                                    :class="selectedAnnotation?.id === run.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                    class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                                >
                                                    <!-- Expand/collapse button if has children -->
                                                    <button
                                                        x-show="run.children && run.children.length > 0"
                                                        @click.stop="toggleNode('anno_' + run.id)"
                                                        class="w-4 h-4 flex items-center justify-center"
                                                    >
                                                        <span x-show="isExpanded('anno_' + run.id)">▼</span>
                                                        <span x-show="!isExpanded('anno_' + run.id)">▶</span>
                                                    </button>
                                                    <span class="w-4" x-show="!run.children || run.children.length === 0"></span>

                                                    <span>📦</span>
                                                    <span class="flex-1" x-text="run.label"></span>

                                                    <!-- Children count badge -->
                                                    <span
                                                        x-show="run.children && run.children.length > 0"
                                                        class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                                        x-text="run.children.length"
                                                    ></span>
                                                </div>

                                                <!-- Cabinet Children (Level 4) -->
                                                <div x-show="isExpanded('anno_' + run.id)" class="tree-hierarchy-indent">
                                                    <template x-for="cabinet in run.children" :key="cabinet.id">
                                                        <div class="tree-node mb-1">
                                                            <!-- Cabinet Level (Leaf) -->
                                                            <div
                                                                @click="handleNodeClick(cabinet)"
                                                                :class="selectedAnnotation?.id === cabinet.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                                class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                                            >
                                                                <span class="w-4"></span>
                                                                <span>🗄️</span>
                                                                <span class="flex-1" x-text="cabinet.label"></span>
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
            </div>
        </div>
    </template>

    <div x-show="!annotations || annotations.length === 0" class="text-center py-8 text-sm text-gray-500">
        No annotations yet
    </div>
</div>
