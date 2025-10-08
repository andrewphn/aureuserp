/**
 * Annotation Editor
 * Handles undo/redo, selection, and deletion of annotations
 */

export function createAnnotationEditor() {
    return {
        /**
         * Save current state to undo stack
         * @param {Array} annotations - Current annotations
         * @param {Array} undoStack - Undo stack
         * @param {number} maxStackSize - Maximum undo stack size (default 20)
         * @returns {Object} { undoStack, redoStack }
         */
        saveState(annotations, undoStack, maxStackSize = 20) {
            // Deep clone annotations
            const state = JSON.parse(JSON.stringify(annotations));
            const newUndoStack = [...undoStack, state];

            // Limit stack size
            if (newUndoStack.length > maxStackSize) {
                newUndoStack.shift();
            }

            return {
                undoStack: newUndoStack,
                redoStack: [] // Clear redo stack when new action is performed
            };
        },

        /**
         * Undo last action
         * @param {Array} annotations - Current annotations
         * @param {Array} undoStack - Undo stack
         * @param {Array} redoStack - Redo stack
         * @returns {Object|null} { annotations, undoStack, redoStack } or null if nothing to undo
         */
        undo(annotations, undoStack, redoStack) {
            if (undoStack.length === 0) return null;

            // Save current state to redo stack
            const currentState = JSON.parse(JSON.stringify(annotations));
            const newRedoStack = [...redoStack, currentState];

            // Restore previous state from undo stack
            const newUndoStack = [...undoStack];
            const previousState = newUndoStack.pop();

            return {
                annotations: previousState,
                undoStack: newUndoStack,
                redoStack: newRedoStack
            };
        },

        /**
         * Redo last undone action
         * @param {Array} annotations - Current annotations
         * @param {Array} undoStack - Undo stack
         * @param {Array} redoStack - Redo stack
         * @returns {Object|null} { annotations, undoStack, redoStack } or null if nothing to redo
         */
        redo(annotations, undoStack, redoStack) {
            if (redoStack.length === 0) return null;

            // Save current state to undo stack
            const currentState = JSON.parse(JSON.stringify(annotations));
            const newUndoStack = [...undoStack, currentState];

            // Restore next state from redo stack
            const newRedoStack = [...redoStack];
            const nextState = newRedoStack.pop();

            return {
                annotations: nextState,
                undoStack: newUndoStack,
                redoStack: newRedoStack
            };
        },

        /**
         * Delete selected annotation
         * @param {Array} annotations - Current annotations
         * @param {number} selectedId - ID of selected annotation
         * @returns {Object|null} { annotations, selectedId: null } or null if nothing selected
         */
        deleteSelected(annotations, selectedId) {
            if (selectedId === null) return null;

            const index = annotations.findIndex(a => a.id === selectedId);
            if (index === -1) return null;

            const newAnnotations = [...annotations];
            newAnnotations.splice(index, 1);

            return {
                annotations: newAnnotations,
                selectedId: null
            };
        },

        /**
         * Remove annotation by index
         * @param {Array} annotations - Current annotations
         * @param {number} index - Index of annotation to remove
         * @returns {Array} Updated annotations array
         */
        removeAnnotation(annotations, index) {
            if (index < 0 || index >= annotations.length) return annotations;

            const newAnnotations = [...annotations];
            newAnnotations.splice(index, 1);
            return newAnnotations;
        },

        /**
         * Clear last annotation (most recent)
         * @param {Array} annotations - Current annotations
         * @returns {Array} Updated annotations array
         */
        clearLastAnnotation(annotations) {
            if (annotations.length === 0) return annotations;

            const newAnnotations = [...annotations];
            newAnnotations.pop();
            return newAnnotations;
        },

        /**
         * Clear all annotations with confirmation
         * @param {Array} annotations - Current annotations
         * @param {Function} confirmCallback - Confirmation callback (returns boolean)
         * @returns {Array} Empty array if confirmed, original array if cancelled
         */
        clearAllAnnotations(annotations, confirmCallback) {
            const confirmed = confirmCallback('Clear all annotations? This cannot be undone.');
            return confirmed ? [] : annotations;
        },

        /**
         * Select annotation by coordinates (for click detection)
         * @param {Array} annotations - Current annotations
         * @param {number} x - Click X coordinate (normalized 0-1)
         * @param {number} y - Click Y coordinate (normalized 0-1)
         * @param {number} tolerance - Click tolerance (default 0.01)
         * @returns {number|null} Selected annotation ID or null
         */
        selectAnnotation(annotations, x, y, tolerance = 0.01) {
            // Find annotation that contains the click point
            const found = annotations.find(ann => {
                return x >= (ann.x - tolerance) &&
                       x <= (ann.x + ann.width + tolerance) &&
                       y >= (ann.y - tolerance) &&
                       y <= (ann.y + ann.height + tolerance);
            });

            return found ? found.id : null;
        },

        /**
         * Deselect current annotation
         * @returns {null} Always returns null
         */
        deselectAnnotation() {
            return null;
        }
    };
}
