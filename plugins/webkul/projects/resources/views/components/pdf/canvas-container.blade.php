{{-- PDF Container with Overlays --}}
<div id="pdf-container-{{ $viewerId }}" class="relative w-full flex-1 min-h-0 overflow-auto"
    :class="{ 'overflow-hidden': !systemReady }"
>
    <!-- PDFObject.js embed goes here -->
    <div x-ref="pdfEmbed" class="w-full h-full min-h-full"></div>

    <!-- View Badge -->
    @include('webkul-project::components.pdf.canvas.view-badge')

    <!-- Isolation Blur -->
    @include('webkul-project::components.pdf.canvas.isolation-blur')

    <!-- Annotation Overlay (HTML Elements) -->
    <div
        x-ref="annotationOverlay"
        @click="activeAnnotationId = null; selectedAnnotation = null;"
        @mousedown="startDrawing($event)"
        @mousemove="if (isResizing) window.PdfViewerManagers?.ResizeMoveSystem?.handleResizeMove($event, $data); else if (isMoving) window.PdfViewerManagers?.ResizeMoveSystem?.handleMoveUpdate($event, $data); else updateDrawing($event);"
        @mouseup="if (isResizing) window.PdfViewerManagers?.ResizeMoveSystem?.handleResizeEnd($event, $data, $refs); else if (isMoving) window.PdfViewerManagers?.ResizeMoveSystem?.handleMoveEnd($event, $data, $refs); else finishDrawing($event);"
        @mouseleave="if (isResizing) window.PdfViewerManagers?.ResizeMoveSystem?.handleResizeEnd($event, $data, $refs); else if (isMoving) window.PdfViewerManagers?.ResizeMoveSystem?.handleMoveEnd($event, $data, $refs); else cancelDrawing($event);"
        :class="(drawMode && !editorModalOpen) ? 'pointer-events-auto cursor-crosshair' : 'pointer-events-none'"
        class="annotation-overlay absolute top-0 left-0"
        :style="`z-index: 10; will-change: width, height; width: ${overlayWidth}; height: ${overlayHeight};`"
    >
        <!-- Existing Annotations -->
        <template x-for="anno in filteredAnnotations.filter(a => !hiddenAnnotations.includes(a.id) && isAnnotationVisibleInView(a) && isAnnotationVisibleInIsolation(a))" :key="anno.id">
            <!-- Wrapper div to hide frame of isolated object itself (you "jumped into it") -->
            <div x-show="!isolationMode || (isolationLevel === 'room' && anno.id !== isolatedRoomId) || (isolationLevel === 'location' && anno.id !== isolatedLocationId) || (isolationLevel === 'cabinet_run' && anno.id !== isolatedCabinetRunId)">
                <div
                    x-data="{ showMenu: false }"
                    :style="`
                        position: absolute;
                        transform: translate(${anno.screenX}px, ${anno.screenY}px);
                        width: ${anno.screenWidth}px;
                        height: ${anno.screenHeight}px;
                        border: ${activeAnnotationId === anno.id ? '3px' : '2px'} solid ${anno.color};
                        background: ${anno.color}33;
                        border-radius: 4px;
                        pointer-events: ${anno.locked ? 'none' : 'auto'};
                        cursor: ${anno.locked ? 'not-allowed' : (isMoving && activeAnnotationId === anno.id ? 'grabbing' : 'grab')};
                        z-index: ${window.PdfViewerManagers?.AnnotationManager?.getAnnotationZIndex(anno, this) || 10};
                        transition: ${(isResizing || isMoving) && activeAnnotationId === anno.id ? 'none' : 'all 0.2s'};
                        will-change: transform;
                        opacity: ${anno.locked ? 0.7 : 1};
                        box-shadow: ${activeAnnotationId === anno.id ? '0 0 0 2px rgba(59, 130, 246, 0.3)' : 'none'};
                    `"
                    @click.stop="!anno.locked && handleNodeClick(anno)"
                    @dblclick.prevent.stop="!anno.locked && handleAnnotationDoubleClick(anno)"
                    @mousedown="!anno.locked && startMove($event, anno)"
                    @mouseenter="$el.style.background = anno.color + '66'; showMenu = true"
                    @mouseleave="$el.style.background = anno.color + '33'; showMenu = anno.locked"
                    class="annotation-marker group"
                >
                <!-- Annotation Label - Bottom Left -->
                <div class="annotation-label absolute -bottom-7 left-0 bg-white dark:bg-gray-900 px-2 py-1 rounded text-xs font-medium whitespace-nowrap shadow-md border z-30" style="color: var(--gray-900); border-color: var(--primary-400); pointer-events: none;">
                    <span x-text="anno.label" class="dark:text-white"></span>
                </div>

                <!-- Edit/Lock/Delete Buttons (visible on hover or if locked) - Top Right Corner -->
                <div
                    x-show="showMenu || anno.locked"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute -top-7 -right-2 flex gap-1 z-30 bg-white dark:bg-gray-900 px-2 py-1 rounded shadow-md border border-gray-300 dark:border-gray-600"
                    @click.stop
                >
                    <!-- Edit Button -->
                    <x-filament::icon-button
                        icon="heroicon-o-pencil"
                        @click="editAnnotation(anno)"
                        tooltip="Edit annotation"
                        size="sm"
                        color="primary"
                    />

                    <!-- Lock Button - Unlocked State -->
                    <span x-show="!anno.locked">
                        <x-filament::icon-button
                            icon="heroicon-o-lock-open"
                            @click="window.PdfViewerManagers?.AnnotationManager?.toggleLockAnnotation(anno, $data)"
                            tooltip="Lock annotation"
                            size="sm"
                            color="info"
                        />
                    </span>

                    <!-- Lock Button - Locked State -->
                    <span x-show="anno.locked">
                        <x-filament::icon-button
                            icon="heroicon-s-lock-closed"
                            @click="window.PdfViewerManagers?.AnnotationManager?.toggleLockAnnotation(anno, $data)"
                            tooltip="Unlock annotation"
                            size="sm"
                            color="warning"
                        />
                    </span>

                    <!-- Delete Button -->
                    <x-filament::icon-button
                        icon="heroicon-o-x-mark"
                        @click="deleteAnnotation(anno)"
                        tooltip="Delete annotation"
                        size="sm"
                        color="danger"
                    />
                </div>

                <!-- Corner Resize Handles -->
                <!-- Top-Left Corner -->
                <div
                    @mousedown.prevent.stop="startResize($event, anno, 'nw')"
                    :style="`
                        position: absolute;
                        top: -4px;
                        left: -4px;
                        width: 12px;
                        height: 12px;
                        background: ${anno.color};
                        border: 2px solid white;
                        border-radius: 50%;
                        cursor: nw-resize;
                        pointer-events: auto;
                        z-index: 200;
                        opacity: ${showMenu || activeAnnotationId === anno.id ? 1 : 0};
                        transition: opacity 0.2s;
                    `"
                    class="resize-handle"
                ></div>

                <!-- Top-Right Corner -->
                <div
                    @mousedown.prevent.stop="startResize($event, anno, 'ne')"
                    :style="`
                        position: absolute;
                        top: -4px;
                        right: -4px;
                        width: 12px;
                        height: 12px;
                        background: ${anno.color};
                        border: 2px solid white;
                        border-radius: 50%;
                        cursor: ne-resize;
                        pointer-events: auto;
                        z-index: 200;
                        opacity: ${showMenu || activeAnnotationId === anno.id ? 1 : 0};
                        transition: opacity 0.2s;
                    `"
                    class="resize-handle"
                ></div>

                <!-- Bottom-Left Corner -->
                <div
                    @mousedown.prevent.stop="startResize($event, anno, 'sw')"
                    :style="`
                        position: absolute;
                        bottom: -4px;
                        left: -4px;
                        width: 12px;
                        height: 12px;
                        background: ${anno.color};
                        border: 2px solid white;
                        border-radius: 50%;
                        cursor: sw-resize;
                        pointer-events: auto;
                        z-index: 200;
                        opacity: ${showMenu || activeAnnotationId === anno.id ? 1 : 0};
                        transition: opacity 0.2s;
                    `"
                    class="resize-handle"
                ></div>

                <!-- Bottom-Right Corner -->
                <div
                    @mousedown.prevent.stop="startResize($event, anno, 'se')"
                    :style="`
                        position: absolute;
                        bottom: -4px;
                        right: -4px;
                        width: 12px;
                        height: 12px;
                        background: ${anno.color};
                        border: 2px solid white;
                        border-radius: 50%;
                        cursor: se-resize;
                        pointer-events: auto;
                        z-index: 200;
                        opacity: ${showMenu || activeAnnotationId === anno.id ? 1 : 0};
                        transition: opacity 0.2s;
                    `"
                    class="resize-handle"
                ></div>

                <!-- Edge Resize Handles (Invisible hit areas) -->
                <!-- Top Edge -->
                <div
                    @mousedown.prevent.stop="startResize($event, anno, 'n')"
                    :style="`
                        position: absolute;
                        top: -4px;
                        left: 12px;
                        right: 12px;
                        height: 8px;
                        cursor: n-resize;
                        pointer-events: auto;
                        z-index: 200;
                        opacity: ${showMenu || activeAnnotationId === anno.id ? 0.3 : 0};
                        background: ${anno.color};
                        transition: opacity 0.2s;
                    `"
                    class="resize-handle-edge"
                ></div>

                <!-- Right Edge -->
                <div
                    @mousedown.prevent.stop="startResize($event, anno, 'e')"
                    :style="`
                        position: absolute;
                        top: 12px;
                        bottom: 12px;
                        right: -4px;
                        width: 8px;
                        cursor: e-resize;
                        pointer-events: auto;
                        z-index: 200;
                        opacity: ${showMenu || activeAnnotationId === anno.id ? 0.3 : 0};
                        background: ${anno.color};
                        transition: opacity 0.2s;
                    `"
                    class="resize-handle-edge"
                ></div>

                <!-- Bottom Edge -->
                <div
                    @mousedown.prevent.stop="startResize($event, anno, 's')"
                    :style="`
                        position: absolute;
                        bottom: -4px;
                        left: 12px;
                        right: 12px;
                        height: 8px;
                        cursor: s-resize;
                        pointer-events: auto;
                        z-index: 200;
                        opacity: ${showMenu || activeAnnotationId === anno.id ? 0.3 : 0};
                        background: ${anno.color};
                        transition: opacity 0.2s;
                    `"
                    class="resize-handle-edge"
                ></div>

                <!-- Left Edge -->
                <div
                    @mousedown.prevent.stop="startResize($event, anno, 'w')"
                    :style="`
                        position: absolute;
                        top: 12px;
                        bottom: 12px;
                        left: -4px;
                        width: 8px;
                        cursor: w-resize;
                        pointer-events: auto;
                        z-index: 200;
                        opacity: ${showMenu || activeAnnotationId === anno.id ? 0.3 : 0};
                        background: ${anno.color};
                        transition: opacity 0.2s;
                    `"
                    class="resize-handle-edge"
                ></div>

            </div>
            </div><!-- End wrapper div for hiding frames in isolation mode -->
        </template>

        <!-- Drawing Preview (Current Rectangle Being Drawn) -->
        <template x-if="isDrawing && drawPreview">
            <div
                :style="`
                    position: absolute;
                    left: ${drawPreview.x}px;
                    top: ${drawPreview.y}px;
                    width: ${drawPreview.width}px;
                    height: ${drawPreview.height}px;
                    border: 2px dashed ${getDrawColor()};
                    background: ${getDrawColor()}22;
                    pointer-events: none;
                `"
            ></div>
        </template>
    </div>
</div>
