@props([
    'pdfPageId',
    'pdfUrl',
    'pageNumber',
    'projectId',
    'totalPages' => 1,
    'pageType' => null,
    'pageMap' => [],
])

@php
    // Generate unique ID for this viewer instance
    $viewerId = 'overlayViewer_' . $pdfPageId . '_' . uniqid();
@endphp

<style>
    /* Prevent body-level scroll - force viewport-constrained layout */
    html, body {
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: hidden !important;
    }

    /* Ensure FilamentPHP containers fill height properly */
    .fi-layout, .fi-main-ctn, .fi-main, .fi-page, .fi-page-content, .fi-page-main {
        height: 100% !important;
        max-height: 100% !important;
        overflow: hidden !important;
    }

    /* Constrain PDF viewer container with viewport-based max-height */
    .pdf-viewer-container {
        overflow: auto !important;
        max-height: calc(100vh - 280px) !important;
    }

    /* PDF container should just contain content */
    [id^="pdf-container"] {
        overflow: visible !important;
        height: auto !important;
    }
</style>

<div
    wire:ignore
    x-cloak
    x-data="annotationSystemV3({
        pdfUrl: '{{ $pdfUrl }}',
        pageNumber: {{ $pageNumber }},
        pdfPageId: {{ $pdfPageId ?? 'null' }},
        projectId: {{ $projectId }},
        totalPages: {{ $totalPages }},
        pageType: {{ $pageType ? "'" . $pageType . "'" : 'null' }},
        pageMap: {{ json_encode($pageMap) }}
    })"
    x-init="init()"
    class="w-full h-full flex flex-col bg-gray-100 dark:bg-gray-900 overflow-hidden"
>
    @include('webkul-project::components.pdf.context-bar')

    <!-- Isolation Mode Breadcrumb (NEW - Illustrator-style) -->
    <div x-show="isolationMode" x-transition class="isolation-breadcrumb bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 border-b-4 border-primary-500 px-6 py-4 shadow-lg" style="position: relative; z-index: 15;">
        <div class="flex items-center gap-4">
            <!-- Lock Icon + Label -->
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-lock-closed" class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                <span class="text-sm font-bold text-primary-900 dark:text-primary-100 uppercase tracking-wide">
                    Isolation Mode
                </span>
            </div>

            <!-- Breadcrumb Path (using computed property) -->
            <div class="flex items-center gap-2 text-sm font-semibold text-primary-800 dark:text-primary-200">
                <template x-for="(crumb, index) in isolationBreadcrumbs" :key="crumb.level">
                    <div class="flex items-center gap-2">
                        <!-- Chevron separator (skip for first item) -->
                        <template x-if="index > 0">
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 text-primary-600" />
                        </template>

                        <!-- Breadcrumb item -->
                        <span class="flex items-center gap-1.5">
                            <span class="text-lg" x-text="crumb.icon"></span>
                            <span x-text="crumb.label"></span>
                        </span>
                    </div>
                </template>
            </div>

            <!-- Exit Button -->
            <button
                @click="exitIsolationMode()"
                class="ml-auto px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-semibold shadow-md transition-all flex items-center gap-2"
                title="Exit Isolation Mode (Esc)"
            >
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                <span>Exit Isolation</span>
            </button>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content-area flex flex-1 min-h-0 overflow-hidden">
        @include('webkul-project::components.pdf.tree.sidebar')

        <!-- Context Menu Overlay (Global) -->
        <div
            x-show="contextMenu.show"
            @click.away="contextMenu.show = false"
            :style="`position: fixed; top: ${contextMenu.y}px; left: ${contextMenu.x}px; z-index: 9999;`"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 min-w-[180px]"
        >
            <button
                @click="deleteTreeNode()"
                class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 text-sm"
                style="color: var(--danger-600);"
            >
                <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                <span>Delete <span x-text="contextMenu.nodeType === 'room' ? 'Room' : contextMenu.nodeType === 'room_location' ? 'Location' : 'Cabinet Run'"></span></span>
            </button>
        </div>

        <!-- PDF Viewer (Center) with HTML Overlay -->
        <div class="pdf-viewer-container flex flex-col flex-1 min-h-0 bg-white dark:bg-gray-900 overflow-hidden relative">
            <!-- Skeleton Loading Overlay -->
            <div
                x-show="!systemReady"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-white dark:bg-gray-900 z-50 flex items-center justify-center"
                @touchmove.prevent
                @wheel.prevent
            >
                <div class="w-full h-full flex flex-col">
                    <!-- Skeleton Header Bar -->
                    <div class="h-16 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 animate-pulse"></div>

                    <!-- Skeleton Content Area -->
                    <div class="flex-1 flex items-center justify-center p-8">
                        <div class="max-w-md w-full space-y-6">
                            <!-- Loading Icon -->
                            <div class="flex justify-center">
                                <svg class="animate-spin h-16 w-16 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <!-- Loading Text -->
                            <div class="text-center space-y-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Loading PDF Viewer</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Preparing your document and annotations...</p>
                            </div>

                            <!-- Skeleton PDF Preview -->
                            <div class="space-y-3">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-5/6"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-4/6"></div>
                                <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mt-4"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/6"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-5/6"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Display -->
            <div
                x-show="systemReady && error"
                class="absolute inset-0 bg-white dark:bg-gray-900 z-40 flex items-center justify-center p-8"
            >
                <div class="max-w-md w-full space-y-6 text-center">
                    <!-- Error Icon -->
                    <div class="flex justify-center">
                        <svg class="h-16 w-16 text-danger-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>

                    <!-- Error Message -->
                    <div class="space-y-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Failed to Load PDF Viewer</h3>
                        <p class="text-sm text-danger-600 dark:text-danger-400" x-text="error"></p>
                    </div>

                    <!-- Help Text -->
                    <div class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
                        <p>This error may occur if:</p>
                        <ul class="list-disc list-inside text-left space-y-1">
                            <li>The PDF file is missing or corrupted</li>
                            <li>Your browser blocked the PDF due to security settings</li>
                            <li>There's a network connectivity issue</li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 justify-center">
                        <button
                            @click="window.location.reload()"
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                        >
                            Reload Page
                        </button>
                        <a
                            href="{{ url()->previous() }}"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition-colors"
                        >
                            Go Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- PDF Container -->
            <div id="pdf-container-{{ $viewerId }}" class="relative w-full flex-1 min-h-0 overflow-auto"
                :class="{ 'overflow-hidden': !systemReady }"
            >
                <!-- PDFObject.js embed goes here -->
                <div x-ref="pdfEmbed" class="w-full h-full min-h-full"></div>

                <!-- Current View Badge - Fixed Position Top-Left -->
                <div class="absolute top-4 left-4 z-50 pointer-events-none">
                    <div
                        class="px-4 py-2 rounded-lg shadow-lg text-white font-bold text-sm flex items-center gap-2"
                        :style="{ backgroundColor: getCurrentViewColor() }"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span x-text="getCurrentViewLabel()"></span>
                    </div>
                </div>

                <!-- Isolation Mode Blur - Positioned exactly like annotation overlay -->
                <div
                    x-show="isolationMode"
                    x-cloak
                    x-ref="isolationBlur"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute top-0 left-0 pointer-events-none"
                    style="z-index: 1; display: none;"
                    :style="`width: ${overlayWidth}; height: ${overlayHeight}; display: ${isolationMode ? 'block' : 'none'};`"
                >
                    <!-- SVG for blur with proper masking -->
                    <!-- Only render viewBox when we have pixel dimensions (not '100%') -->
                    <svg
                        x-show="overlayWidth.includes('px')"
                        xmlns="http://www.w3.org/2000/svg"
                        preserveAspectRatio="none"
                        style="display: block; width: 100%; height: 100%;"
                        :viewBox="`0 0 ${overlayWidth.replace('px', '')} ${overlayHeight.replace('px', '')}`"
                    >
                        <defs>
                            <!-- Blur filter for background -->
                            <filter id="blur">
                                <feGaussianBlur in="SourceGraphic" stdDeviation="4"/>
                            </filter>

                            <!-- Feather filter for soft mask edges -->
                            <filter id="feather">
                                <feGaussianBlur in="SourceGraphic" stdDeviation="15"/>
                            </filter>

                            <!-- Mask: white = show blur, black = hide blur -->
                            <mask id="blurMask">
                                <!-- White everywhere = show blur everywhere -->
                                <!-- Dynamically sized to match canvas -->
                                <rect
                                    x="0"
                                    y="0"
                                    :width="overlayWidth.replace('px', '')"
                                    :height="overlayHeight.replace('px', '')"
                                    fill="white"
                                />

                                <!-- Black rectangle at selected annotation and its visible children = hide blur there -->
                                <!-- This excludes the focused area from the darkening blur in isolation mode -->
                                <g id="maskRects"></g>
                            </mask>
                        </defs>

                        <!-- Dark overlay with blur, masked to exclude annotation -->
                        <!-- Dynamically sized to match canvas -->
                        <rect
                            x="0"
                            y="0"
                            :width="overlayWidth.replace('px', '')"
                            :height="overlayHeight.replace('px', '')"
                            fill="rgba(0, 0, 0, 0.65)"
                            filter="url(#blur)"
                            mask="url(#blurMask)"
                        />
                    </svg>
                </div>

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
        </div>
    </div>

    {{-- Filament Annotation Editor Component --}}
    @livewire('annotation-editor')

    {{-- Hierarchy Builder Modal Component --}}
    @livewire('hierarchy-builder-modal')


@once
    <!-- Load PDFObject.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfobject/2.3.0/pdfobject.min.js"></script>

    <!-- PDF.js is loaded via annotations.js Vite bundle (pdfjs-dist v5.4.296) -->
    <!-- The bundled version is already configured with worker and exported to window.pdfjsLib -->

@endonce
