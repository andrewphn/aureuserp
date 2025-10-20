/**
 * Annotation Interactions Module
 *
 * Handles drag-to-move and resize functionality for PDF annotations.
 * This module provides reusable interaction handlers that can be attached
 * to annotation elements in the Alpine component.
 */

export class AnnotationInteractions {
    constructor(options = {}) {
        this.onUpdate = options.onUpdate || (() => {});
        this.onResizeStart = options.onResizeStart || (() => {});
        this.onResizeEnd = options.onResizeEnd || (() => {});
        this.onMoveStart = options.onMoveStart || (() => {});
        this.onMoveEnd = options.onMoveEnd || (() => {});

        // Interaction state
        this.isDragging = false;
        this.isResizing = false;
        this.dragStartX = 0;
        this.dragStartY = 0;
        this.resizeHandle = null;
        this.currentAnnotation = null;
        this.originalRect = null;

        // Minimum annotation size (in pixels)
        this.minWidth = 20;
        this.minHeight = 20;

        // Bind methods to maintain context
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleMouseUp = this.handleMouseUp.bind(this);
    }

    /**
     * Initialize drag-to-move for an annotation
     *
     * @param {Event} event - mousedown event
     * @param {Object} annotation - annotation data object
     * @param {HTMLElement} containerEl - container element for coordinate calculation
     */
    initMove(event, annotation, containerEl) {
        // Ignore if clicking on a resize handle
        if (event.target.classList.contains('resize-handle')) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.isDragging = true;
        this.currentAnnotation = annotation;

        const rect = containerEl.getBoundingClientRect();
        this.dragStartX = event.clientX - rect.left - annotation.screenX;
        this.dragStartY = event.clientY - rect.top - annotation.screenY;

        this.originalRect = {
            x: annotation.screenX,
            y: annotation.screenY,
            width: annotation.screenWidth,
            height: annotation.screenHeight
        };

        this.onMoveStart(annotation);

        // Add global listeners
        document.addEventListener('mousemove', this.handleMouseMove);
        document.addEventListener('mouseup', this.handleMouseUp);

        console.log('🎯 Move started:', annotation.label);
    }

    /**
     * Initialize resize for an annotation
     *
     * @param {Event} event - mousedown event on resize handle
     * @param {Object} annotation - annotation data object
     * @param {string} handle - resize handle position ('nw', 'ne', 'sw', 'se')
     * @param {HTMLElement} containerEl - container element for coordinate calculation
     */
    initResize(event, annotation, handle, containerEl) {
        event.preventDefault();
        event.stopPropagation();

        this.isResizing = true;
        this.currentAnnotation = annotation;
        this.resizeHandle = handle;

        const rect = containerEl.getBoundingClientRect();
        this.dragStartX = event.clientX - rect.left;
        this.dragStartY = event.clientY - rect.top;

        this.originalRect = {
            x: annotation.screenX,
            y: annotation.screenY,
            width: annotation.screenWidth,
            height: annotation.screenHeight
        };

        this.onResizeStart(annotation, handle);

        // Add global listeners
        document.addEventListener('mousemove', this.handleMouseMove);
        document.addEventListener('mouseup', this.handleMouseUp);

        console.log('📐 Resize started:', annotation.label, 'handle:', handle);
    }

    /**
     * Handle mouse move for both drag and resize
     * @private
     */
    handleMouseMove(event) {
        if (!this.isDragging && !this.isResizing) return;

        const containerEl = event.target.closest('.pdf-viewer-container') ||
                           event.target.closest('.annotation-overlay');
        if (!containerEl) return;

        const rect = containerEl.getBoundingClientRect();
        const currentX = event.clientX - rect.left;
        const currentY = event.clientY - rect.top;

        if (this.isDragging) {
            this.handleMove(currentX, currentY, rect);
        } else if (this.isResizing) {
            this.handleResize(currentX, currentY, rect);
        }
    }

    /**
     * Handle move calculation
     * @private
     */
    handleMove(currentX, currentY, containerRect) {
        const newX = currentX - this.dragStartX;
        const newY = currentY - this.dragStartY;

        // Constrain to container bounds
        const constrainedX = Math.max(0, Math.min(newX, containerRect.width - this.currentAnnotation.screenWidth));
        const constrainedY = Math.max(0, Math.min(newY, containerRect.height - this.currentAnnotation.screenHeight));

        // Update annotation screen position
        this.currentAnnotation.screenX = constrainedX;
        this.currentAnnotation.screenY = constrainedY;

        // Notify parent component
        this.onUpdate(this.currentAnnotation, {
            type: 'move',
            x: constrainedX,
            y: constrainedY
        });
    }

    /**
     * Handle resize calculation
     * @private
     */
    handleResize(currentX, currentY, containerRect) {
        const deltaX = currentX - this.dragStartX;
        const deltaY = currentY - this.dragStartY;

        let newX = this.originalRect.x;
        let newY = this.originalRect.y;
        let newWidth = this.originalRect.width;
        let newHeight = this.originalRect.height;

        // Calculate new dimensions based on resize handle
        switch (this.resizeHandle) {
            case 'nw': // Northwest (top-left)
                newX = this.originalRect.x + deltaX;
                newY = this.originalRect.y + deltaY;
                newWidth = this.originalRect.width - deltaX;
                newHeight = this.originalRect.height - deltaY;
                break;

            case 'ne': // Northeast (top-right)
                newY = this.originalRect.y + deltaY;
                newWidth = this.originalRect.width + deltaX;
                newHeight = this.originalRect.height - deltaY;
                break;

            case 'sw': // Southwest (bottom-left)
                newX = this.originalRect.x + deltaX;
                newWidth = this.originalRect.width - deltaX;
                newHeight = this.originalRect.height + deltaY;
                break;

            case 'se': // Southeast (bottom-right)
                newWidth = this.originalRect.width + deltaX;
                newHeight = this.originalRect.height + deltaY;
                break;
        }

        // Enforce minimum size
        if (newWidth < this.minWidth) {
            newWidth = this.minWidth;
            if (this.resizeHandle.includes('w')) {
                newX = this.originalRect.x + this.originalRect.width - this.minWidth;
            }
        }

        if (newHeight < this.minHeight) {
            newHeight = this.minHeight;
            if (this.resizeHandle.includes('n')) {
                newY = this.originalRect.y + this.originalRect.height - this.minHeight;
            }
        }

        // Constrain to container bounds
        newX = Math.max(0, Math.min(newX, containerRect.width - newWidth));
        newY = Math.max(0, Math.min(newY, containerRect.height - newHeight));
        newWidth = Math.min(newWidth, containerRect.width - newX);
        newHeight = Math.min(newHeight, containerRect.height - newY);

        // Update annotation
        this.currentAnnotation.screenX = newX;
        this.currentAnnotation.screenY = newY;
        this.currentAnnotation.screenWidth = newWidth;
        this.currentAnnotation.screenHeight = newHeight;

        // Notify parent component
        this.onUpdate(this.currentAnnotation, {
            type: 'resize',
            handle: this.resizeHandle,
            x: newX,
            y: newY,
            width: newWidth,
            height: newHeight
        });
    }

    /**
     * Handle mouse up to end drag or resize
     * @private
     */
    handleMouseUp(event) {
        if (!this.isDragging && !this.isResizing) return;

        const wasDragging = this.isDragging;
        const wasResizing = this.isResizing;

        // Remove global listeners
        document.removeEventListener('mousemove', this.handleMouseMove);
        document.removeEventListener('mouseup', this.handleMouseUp);

        if (wasDragging) {
            this.onMoveEnd(this.currentAnnotation, {
                startX: this.originalRect.x,
                startY: this.originalRect.y,
                endX: this.currentAnnotation.screenX,
                endY: this.currentAnnotation.screenY
            });
            console.log('✓ Move completed:', this.currentAnnotation.label);
        }

        if (wasResizing) {
            this.onResizeEnd(this.currentAnnotation, {
                startRect: this.originalRect,
                endRect: {
                    x: this.currentAnnotation.screenX,
                    y: this.currentAnnotation.screenY,
                    width: this.currentAnnotation.screenWidth,
                    height: this.currentAnnotation.screenHeight
                }
            });
            console.log('✓ Resize completed:', this.currentAnnotation.label);
        }

        // Reset state
        this.isDragging = false;
        this.isResizing = false;
        this.resizeHandle = null;
        this.currentAnnotation = null;
        this.originalRect = null;
    }

    /**
     * Cleanup - remove all event listeners
     */
    destroy() {
        document.removeEventListener('mousemove', this.handleMouseMove);
        document.removeEventListener('mouseup', this.handleMouseUp);
    }
}

/**
 * Generate resize handles HTML for annotation
 *
 * @param {Object} annotation - annotation data
 * @returns {string} HTML string for resize handles
 */
export function generateResizeHandles(annotation) {
    const handleSize = 8; // 8px handles
    const positions = {
        nw: { top: -handleSize/2, left: -handleSize/2, cursor: 'nw-resize' },
        ne: { top: -handleSize/2, right: -handleSize/2, cursor: 'ne-resize' },
        sw: { bottom: -handleSize/2, left: -handleSize/2, cursor: 'sw-resize' },
        se: { bottom: -handleSize/2, right: -handleSize/2, cursor: 'se-resize' }
    };

    return Object.entries(positions).map(([handle, style]) => `
        <div
            class="resize-handle resize-handle-${handle}"
            data-handle="${handle}"
            style="
                position: absolute;
                width: ${handleSize}px;
                height: ${handleSize}px;
                background: white;
                border: 2px solid ${annotation.color};
                border-radius: 50%;
                cursor: ${style.cursor};
                z-index: 100;
                ${style.top !== undefined ? `top: ${style.top}px;` : ''}
                ${style.bottom !== undefined ? `bottom: ${style.bottom}px;` : ''}
                ${style.left !== undefined ? `left: ${style.left}px;` : ''}
                ${style.right !== undefined ? `right: ${style.right}px;` : ''}
            "
        ></div>
    `).join('');
}

/**
 * Alpine.js helper to integrate interactions into annotation component
 *
 * Usage in Alpine component:
 * ```javascript
 * import { createInteractionHelpers } from './annotation-interactions.js';
 *
 * Alpine.data('annotationSystemV3', (config) => ({
 *     ...createInteractionHelpers(),
 *     // ... rest of component
 * }))
 * ```
 */
export function createInteractionHelpers() {
    let interactions = null;

    return {
        // Initialize interactions system
        initInteractions() {
            interactions = new AnnotationInteractions({
                onUpdate: (annotation, change) => {
                    // Update happens in real-time via Alpine reactivity
                    this.recalculatePdfCoordinates(annotation);
                },
                onMoveEnd: (annotation, movement) => {
                    this.recalculatePdfCoordinates(annotation);
                    console.log('📍 Annotation moved:', annotation.label, movement);
                },
                onResizeEnd: (annotation, resize) => {
                    this.recalculatePdfCoordinates(annotation);
                    console.log('📏 Annotation resized:', annotation.label, resize);
                }
            });
        },

        // Start moving annotation
        startMove(event, annotation) {
            const container = this.$refs.annotationOverlay;
            interactions.initMove(event, annotation, container);
        },

        // Start resizing annotation
        startResize(event, annotation, handle) {
            const container = this.$refs.annotationOverlay;
            interactions.initResize(event, annotation, handle, container);
        },

        // Recalculate PDF coordinates after screen position change
        recalculatePdfCoordinates(annotation) {
            if (!this.pageDimensions) return;

            const canvasRect = this.getCanvasRect();
            if (!canvasRect) return;

            // Convert screen coordinates back to PDF coordinates
            const normalizedX = annotation.screenX / canvasRect.width;
            const normalizedY = annotation.screenY / canvasRect.height;

            annotation.normalizedX = normalizedX;
            annotation.normalizedY = normalizedY;
            annotation.pdfX = normalizedX * this.pageDimensions.width;
            annotation.pdfY = this.pageDimensions.height - (normalizedY * this.pageDimensions.height);
            annotation.pdfWidth = (annotation.screenWidth / canvasRect.width) * this.pageDimensions.width;
            annotation.pdfHeight = (annotation.screenHeight / canvasRect.height) * this.pageDimensions.height;
        },

        // Cleanup on component destroy
        destroyInteractions() {
            if (interactions) {
                interactions.destroy();
                interactions = null;
            }
        }
    };
}
