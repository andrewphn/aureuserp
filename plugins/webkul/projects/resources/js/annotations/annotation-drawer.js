/**
 * Annotation Drawer
 * Handles drawing rectangles on canvas and managing drawing state
 */

export function createAnnotationDrawer() {
    return {
        /**
         * Start drawing a new annotation rectangle
         * @param {MouseEvent} e - Mouse down event
         * @param {HTMLCanvasElement} canvas - Annotation canvas
         * @param {string} currentTool - Current tool ('rectangle' or 'select')
         * @returns {Object|null} Drawing state or null if not rectangle tool
         */
        startDrawing(e, canvas, currentTool) {
            if (currentTool !== 'rectangle') return null;

            const rect = canvas.getBoundingClientRect();
            return {
                isDrawing: true,
                startX: e.clientX - rect.left,
                startY: e.clientY - rect.top
            };
        },

        /**
         * Draw preview of current rectangle being drawn
         * @param {MouseEvent} e - Mouse move event
         * @param {HTMLCanvasElement} canvas - Annotation canvas
         * @param {Object} drawState - Drawing state { isDrawing, startX, startY }
         * @param {Array} annotations - Existing annotations
         * @param {Function} redrawCallback - Callback to redraw existing annotations
         */
        drawPreview(e, canvas, drawState, annotations, redrawCallback) {
            if (!drawState.isDrawing) return;

            const rect = canvas.getBoundingClientRect();
            const currentX = e.clientX - rect.left;
            const currentY = e.clientY - rect.top;

            // Clear and redraw all existing annotations
            redrawCallback(annotations, canvas);

            // Draw current rectangle being drawn
            const ctx = canvas.getContext('2d');
            ctx.strokeStyle = '#3B82F6'; // Blue
            ctx.lineWidth = 3;
            ctx.strokeRect(
                drawState.startX,
                drawState.startY,
                currentX - drawState.startX,
                currentY - drawState.startY
            );
        },

        /**
         * Complete drawing and create annotation
         * @param {MouseEvent} e - Mouse up event
         * @param {HTMLCanvasElement} canvas - Annotation canvas
         * @param {Object} drawState - Drawing state { isDrawing, startX, startY }
         * @param {Object} options - Annotation options { annotationType, roomType, projectNumber, roomCodes, roomColors }
         * @param {Array} existingAnnotations - Current annotations array
         * @returns {Object|null} New annotation object or null if too small
         */
        stopDrawing(e, canvas, drawState, options, existingAnnotations) {
            if (!drawState.isDrawing) return null;

            const rect = canvas.getBoundingClientRect();
            const endX = e.clientX - rect.left;
            const endY = e.clientY - rect.top;

            const width = endX - drawState.startX;
            const height = endY - drawState.startY;

            // Only create annotation if rectangle has meaningful size
            if (Math.abs(width) < 10 || Math.abs(height) < 10) {
                return null;
            }

            // Determine color based on annotation type and room type
            const annotationColor = this.getAnnotationColor(
                options.annotationType,
                options.roomType,
                options.roomColors
            );

            // Generate label
            const labelText = this.generateLabel(
                options.annotationType,
                options.roomType,
                options.projectNumber,
                options.roomCodes,
                existingAnnotations.length
            );

            // Create normalized annotation (coordinates as 0-1 percentages)
            return {
                id: Date.now(),
                // Normalized coordinates (0-1 range)
                x: Math.min(drawState.startX, endX) / canvas.width,
                y: Math.min(drawState.startY, endY) / canvas.height,
                width: Math.abs(width) / canvas.width,
                height: Math.abs(height) / canvas.height,
                text: labelText,
                room_type: options.roomType || '',
                cabinet_run_id: '',
                room_id: '',
                notes: '',
                color: annotationColor,
                annotation_type: options.annotationType || 'room'
            };
        },

        /**
         * Get annotation color based on type and room type
         * @param {string} annotationType - Type of annotation
         * @param {string} roomType - Room type (kitchen, pantry, etc.)
         * @param {Object} roomColors - Room type to color mapping
         * @returns {string} Hex color code
         */
        getAnnotationColor(annotationType, roomType, roomColors) {
            if (annotationType === 'room' && roomType && roomColors[roomType]) {
                return roomColors[roomType];
            }
            // Default colors for other annotation types
            const defaultColors = {
                room: '#3B82F6',           // Blue
                room_location: '#10B981',  // Green
                cabinet_run: '#F59E0B',    // Amber
                cabinet: '#EF4444',        // Red
                dimension: '#8B5CF6'       // Purple
            };
            return defaultColors[annotationType] || '#3B82F6';
        },

        /**
         * Generate label text for annotation
         * @param {string} annotationType - Type of annotation
         * @param {string} roomType - Room type
         * @param {string} projectNumber - Project number (e.g., TFW-0001)
         * @param {Object} roomCodes - Room type to code mapping
         * @param {number} annotationCount - Current annotation count
         * @returns {string} Generated label
         */
        generateLabel(annotationType, roomType, projectNumber, roomCodes, annotationCount) {
            // For room annotations with room type
            if (annotationType === 'room' && roomType && roomCodes[roomType]) {
                const roomCode = roomCodes[roomType];
                return projectNumber
                    ? `${projectNumber}-${roomCode}`
                    : roomCode;
            }

            // Fallback label
            const labelNumber = annotationCount + 1;
            return projectNumber
                ? `${projectNumber}-${labelNumber}`
                : `Label ${labelNumber}`;
        },

        /**
         * Redraw all annotations on canvas
         * @param {Array} annotations - Array of annotations to draw
         * @param {HTMLCanvasElement} canvas - Annotation canvas
         * @param {number|null} selectedAnnotationId - ID of selected annotation for highlighting
         */
        redrawAnnotations(annotations, canvas, selectedAnnotationId = null) {
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw all saved annotations
            // Convert normalized coordinates (0-1) to actual canvas pixels
            annotations.forEach(annotation => {
                const x = annotation.x * canvas.width;
                const y = annotation.y * canvas.height;
                const width = annotation.width * canvas.width;
                const height = annotation.height * canvas.height;

                const isSelected = selectedAnnotationId && annotation.id === selectedAnnotationId;

                // Draw annotation rectangle
                ctx.strokeStyle = annotation.color || '#3B82F6';
                ctx.lineWidth = isSelected ? 4 : 3;
                ctx.strokeRect(x, y, width, height);

                // Draw selection highlight with dashed border
                if (isSelected) {
                    ctx.strokeStyle = '#F59E0B'; // Orange selection border
                    ctx.lineWidth = 2;
                    ctx.setLineDash([8, 4]); // Dashed line
                    ctx.strokeRect(x - 3, y - 3, width + 6, height + 6);
                    ctx.setLineDash([]); // Reset to solid

                    // Draw resize handles at corners
                    this.drawResizeHandles(ctx, x, y, width, height);
                }

                // Draw label text
                if (annotation.text) {
                    ctx.fillStyle = annotation.color || '#3B82F6';
                    ctx.font = 'bold 16px sans-serif';
                    ctx.fillText(annotation.text, x + 5, y - 5);
                }
            });
        },

        /**
         * Draw resize handles at annotation corners
         * @param {CanvasRenderingContext2D} ctx - Canvas context
         * @param {number} x - Annotation x position
         * @param {number} y - Annotation y position
         * @param {number} width - Annotation width
         * @param {number} height - Annotation height
         */
        drawResizeHandles(ctx, x, y, width, height) {
            const handleSize = 10;
            const handleColor = '#F59E0B'; // Orange

            const handles = [
                { x: x, y: y },                           // Top-left
                { x: x + width, y: y },                   // Top-right
                { x: x, y: y + height },                  // Bottom-left
                { x: x + width, y: y + height }           // Bottom-right
            ];

            handles.forEach(handle => {
                ctx.fillStyle = handleColor;
                ctx.fillRect(
                    handle.x - handleSize / 2,
                    handle.y - handleSize / 2,
                    handleSize,
                    handleSize
                );
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.strokeRect(
                    handle.x - handleSize / 2,
                    handle.y - handleSize / 2,
                    handleSize,
                    handleSize
                );
            });
        },

        /**
         * Check if a click is inside an annotation
         * @param {number} clickX - Click X coordinate (canvas pixels)
         * @param {number} clickY - Click Y coordinate (canvas pixels)
         * @param {Array} annotations - Array of annotations
         * @param {HTMLCanvasElement} canvas - Canvas element
         * @returns {Object|null} Clicked annotation or null
         */
        getClickedAnnotation(clickX, clickY, annotations, canvas) {
            // Check annotations in reverse order (top to bottom)
            for (let i = annotations.length - 1; i >= 0; i--) {
                const annotation = annotations[i];
                const x = annotation.x * canvas.width;
                const y = annotation.y * canvas.height;
                const width = annotation.width * canvas.width;
                const height = annotation.height * canvas.height;

                // Check if click is inside this annotation's bounds
                if (clickX >= x && clickX <= x + width &&
                    clickY >= y && clickY <= y + height) {
                    return annotation;
                }
            }
            return null;
        },

        /**
         * Check if click is on a resize handle
         * @param {number} clickX - Click X coordinate (canvas pixels)
         * @param {number} clickY - Click Y coordinate (canvas pixels)
         * @param {Object} annotation - Annotation object
         * @param {HTMLCanvasElement} canvas - Canvas element
         * @returns {string|null} Handle position ('tl', 'tr', 'bl', 'br') or null
         */
        getResizeHandle(clickX, clickY, annotation, canvas) {
            const handleSize = 10;
            const handleTolerance = 5; // Extra pixels for easier clicking

            const x = annotation.x * canvas.width;
            const y = annotation.y * canvas.height;
            const width = annotation.width * canvas.width;
            const height = annotation.height * canvas.height;

            const handles = [
                { pos: 'tl', x: x, y: y },                              // Top-left
                { pos: 'tr', x: x + width, y: y },                      // Top-right
                { pos: 'bl', x: x, y: y + height },                     // Bottom-left
                { pos: 'br', x: x + width, y: y + height }              // Bottom-right
            ];

            // Check each handle
            for (const handle of handles) {
                const distance = Math.sqrt(
                    Math.pow(clickX - handle.x, 2) +
                    Math.pow(clickY - handle.y, 2)
                );

                if (distance <= (handleSize / 2 + handleTolerance)) {
                    return handle.pos;
                }
            }

            return null;
        },

        /**
         * Resize annotation based on handle drag
         * @param {Object} annotation - Annotation being resized
         * @param {string} handlePos - Handle position ('tl', 'tr', 'bl', 'br')
         * @param {number} newX - New mouse X position (canvas pixels)
         * @param {number} newY - New mouse Y position (canvas pixels)
         * @param {HTMLCanvasElement} canvas - Canvas element
         * @returns {Object} Updated annotation bounds {x, y, width, height} in normalized coords
         */
        resizeAnnotation(annotation, handlePos, newX, newY, canvas) {
            // Convert current annotation to canvas pixels
            let x = annotation.x * canvas.width;
            let y = annotation.y * canvas.height;
            let width = annotation.width * canvas.width;
            let height = annotation.height * canvas.height;

            // Update bounds based on which handle is being dragged
            switch (handlePos) {
                case 'tl': // Top-left: change x, y, width, height
                    width = (x + width) - newX;
                    height = (y + height) - newY;
                    x = newX;
                    y = newY;
                    break;
                case 'tr': // Top-right: change y, width, height
                    width = newX - x;
                    height = (y + height) - newY;
                    y = newY;
                    break;
                case 'bl': // Bottom-left: change x, width, height
                    width = (x + width) - newX;
                    height = newY - y;
                    x = newX;
                    break;
                case 'br': // Bottom-right: change width, height
                    width = newX - x;
                    height = newY - y;
                    break;
            }

            // Prevent negative dimensions
            if (width < 0) {
                x = x + width;
                width = Math.abs(width);
            }
            if (height < 0) {
                y = y + height;
                height = Math.abs(height);
            }

            // Return normalized coordinates (0-1 range)
            return {
                x: x / canvas.width,
                y: y / canvas.height,
                width: width / canvas.width,
                height: height / canvas.height
            };
        },

        /**
         * Move annotation by delta
         * @param {Object} annotation - Annotation being moved
         * @param {number} deltaX - Movement in X (canvas pixels)
         * @param {number} deltaY - Movement in Y (canvas pixels)
         * @param {HTMLCanvasElement} canvas - Canvas element
         * @returns {Object} Updated annotation position {x, y} in normalized coords
         */
        moveAnnotation(annotation, deltaX, deltaY, canvas) {
            // Convert to canvas pixels
            let x = annotation.x * canvas.width + deltaX;
            let y = annotation.y * canvas.height + deltaY;

            // Keep annotation within canvas bounds
            const width = annotation.width * canvas.width;
            const height = annotation.height * canvas.height;

            x = Math.max(0, Math.min(x, canvas.width - width));
            y = Math.max(0, Math.min(y, canvas.height - height));

            // Return normalized coordinates
            return {
                x: x / canvas.width,
                y: y / canvas.height
            };
        },

        /**
         * Change cursor based on tool
         * @param {HTMLCanvasElement} canvas - Annotation canvas
         * @param {string} tool - Current tool ('rectangle' or 'select')
         */
        setCursor(canvas, tool) {
            if (!canvas) return;
            canvas.style.cursor = tool === 'rectangle' ? 'crosshair' : 'pointer';
        },

        /**
         * Set cursor for resize handle
         * @param {HTMLCanvasElement} canvas - Annotation canvas
         * @param {string} handlePos - Handle position ('tl', 'tr', 'bl', 'br')
         */
        setResizeCursor(canvas, handlePos) {
            if (!canvas) return;

            const cursors = {
                'tl': 'nwse-resize',  // ↖↘
                'tr': 'nesw-resize',  // ↗↙
                'bl': 'nesw-resize',  // ↗↙
                'br': 'nwse-resize'   // ↖↘
            };

            canvas.style.cursor = cursors[handlePos] || 'default';
        },

        /**
         * Set cursor for moving
         * @param {HTMLCanvasElement} canvas - Annotation canvas
         */
        setMoveCursor(canvas) {
            if (!canvas) return;
            canvas.style.cursor = 'move';
        }
    };
}
