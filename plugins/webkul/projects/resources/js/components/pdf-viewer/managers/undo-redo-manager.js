/**
 * Undo/Redo Manager
 * Manages history stack for annotation operations
 */

import { deepClone } from '../utilities.js';

/**
 * Push current state to history stack
 * @param {Object} state - Component state
 * @param {String} action - Action description
 */
export function pushToHistory(state, action) {
    // Don't record undo/redo actions
    if (state.isUndoRedoAction) return;

    // Create snapshot of current annotations
    const snapshot = {
        annotations: deepClone(state.annotations),
        timestamp: Date.now(),
        action: action
    };

    // Remove any states after current index (branching)
    if (state.historyIndex < state.historyStack.length - 1) {
        state.historyStack = state.historyStack.slice(0, state.historyIndex + 1);
    }

    // Add new snapshot
    state.historyStack.push(snapshot);

    // Limit history size
    if (state.historyStack.length > state.maxHistorySize) {
        state.historyStack.shift();
    } else {
        state.historyIndex++;
    }

    console.log(`📚 History: ${action} (${state.historyIndex + 1}/${state.historyStack.length})`);
}

/**
 * Undo last action
 * @param {Object} state - Component state
 */
export function undo(state) {
    if (!canUndo(state)) {
        console.log('⏮️ Nothing to undo');
        return;
    }

    state.historyIndex--;
    const snapshot = state.historyStack[state.historyIndex];

    // Apply snapshot
    state.isUndoRedoAction = true;
    state.annotations = deepClone(snapshot.annotations);
    state.isUndoRedoAction = false;

    console.log(`⏮️ Undo: ${snapshot.action} (${state.historyIndex + 1}/${state.historyStack.length})`);
}

/**
 * Redo last undone action
 * @param {Object} state - Component state
 */
export function redo(state) {
    if (!canRedo(state)) {
        console.log('⏭️ Nothing to redo');
        return;
    }

    state.historyIndex++;
    const snapshot = state.historyStack[state.historyIndex];

    // Apply snapshot
    state.isUndoRedoAction = true;
    state.annotations = deepClone(snapshot.annotations);
    state.isUndoRedoAction = false;

    console.log(`⏭️ Redo: ${snapshot.action} (${state.historyIndex + 1}/${state.historyStack.length})`);
}

/**
 * Check if undo is available
 * @param {Object} state - Component state
 * @returns {Boolean} True if can undo
 */
export function canUndo(state) {
    return state.historyIndex > 0;
}

/**
 * Check if redo is available
 * @param {Object} state - Component state
 * @returns {Boolean} True if can redo
 */
export function canRedo(state) {
    return state.historyIndex < state.historyStack.length - 1;
}

/**
 * Clear history stack
 * @param {Object} state - Component state
 */
export function clearHistory(state) {
    state.historyStack = [];
    state.historyIndex = -1;
    console.log('🗑️ History cleared');
}

/**
 * Get history info for debugging
 * @param {Object} state - Component state
 * @returns {Object} History information
 */
export function getHistoryInfo(state) {
    return {
        size: state.historyStack.length,
        index: state.historyIndex,
        canUndo: canUndo(state),
        canRedo: canRedo(state),
        current: state.historyStack[state.historyIndex]?.action || 'none',
        next: state.historyStack[state.historyIndex + 1]?.action || 'none',
        previous: state.historyStack[state.historyIndex - 1]?.action || 'none'
    };
}

/**
 * Setup keyboard shortcuts for undo/redo
 * @param {Object} state - Component state
 */
export function setupUndoRedoKeyboards(state) {
    document.addEventListener('keydown', (event) => {
        // Ctrl+Z (or Cmd+Z on Mac) - Undo
        if ((event.ctrlKey || event.metaKey) && event.key === 'z' && !event.shiftKey) {
            event.preventDefault();
            undo(state);
        }

        // Ctrl+Shift+Z or Ctrl+Y (or Cmd equivalents) - Redo
        if ((event.ctrlKey || event.metaKey) && (
            (event.shiftKey && event.key === 'z') ||
            event.key === 'y'
        )) {
            event.preventDefault();
            redo(state);
        }
    });

    console.log('⌨️ Undo/Redo keyboard shortcuts registered');
}
