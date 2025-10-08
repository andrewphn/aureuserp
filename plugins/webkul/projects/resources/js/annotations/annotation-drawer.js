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
         */
        redrawAnnotations(annotations, canvas) {
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

                ctx.strokeStyle = annotation.color || '#3B82F6';
                ctx.lineWidth = 3;
                ctx.strokeRect(x, y, width, height);

                // Draw label text
                if (annotation.text) {
                    ctx.fillStyle = annotation.color || '#3B82F6';
                    ctx.font = 'bold 16px sans-serif';
                    ctx.fillText(annotation.text, x + 5, y - 5);
                }
            });
        },

        /**
         * Change cursor based on tool
         * @param {HTMLCanvasElement} canvas - Annotation canvas
         * @param {string} tool - Current tool ('rectangle' or 'select')
         */
        setCursor(canvas, tool) {
            if (!canvas) return;
            canvas.style.cursor = tool === 'rectangle' ? 'crosshair' : 'default';
        }
    };
}
