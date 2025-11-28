/**
 * Undo/Redo Manager Tests
 * Tests for history stack management and undo/redo operations
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    pushToHistory,
    undo,
    redo,
    canUndo,
    canRedo,
    clearHistory,
    getHistoryInfo,
    setupUndoRedoKeyboards,
} from '@pdf-viewer/managers/undo-redo-manager.js';
import { createMockState, createMockAnnotation } from '../../mocks/pdf-viewer-mocks.js';

// Mock utilities module
vi.mock('@pdf-viewer/utilities.js', () => ({
    deepClone: vi.fn((obj) => {
        if (obj === null || typeof obj !== 'object') return obj;
        if (obj instanceof Array) return obj.map(item => JSON.parse(JSON.stringify(item)));
        return JSON.parse(JSON.stringify(obj));
    }),
}));

describe('Undo/Redo Manager', () => {
    let state;

    beforeEach(() => {
        state = createMockState({
            annotations: [
                createMockAnnotation({ id: 1, label: 'Initial Room' }),
            ],
            historyStack: [],
            historyIndex: -1,
            maxHistorySize: 50,
            isUndoRedoAction: false,
        });

        // Reset console.log mock
        vi.spyOn(console, 'log').mockImplementation(() => {});
    });

    describe('pushToHistory', () => {
        it('should push current state to history', () => {
            pushToHistory(state, 'Add room');

            expect(state.historyStack.length).toBe(1);
            expect(state.historyIndex).toBe(0);
            expect(state.historyStack[0].action).toBe('Add room');
            expect(state.historyStack[0].annotations).toHaveLength(1);
        });

        it('should create snapshot with timestamp', () => {
            const beforeTime = Date.now();
            pushToHistory(state, 'Add room');
            const afterTime = Date.now();

            expect(state.historyStack[0].timestamp).toBeGreaterThanOrEqual(beforeTime);
            expect(state.historyStack[0].timestamp).toBeLessThanOrEqual(afterTime);
        });

        it('should not record when isUndoRedoAction flag is set', () => {
            state.isUndoRedoAction = true;
            pushToHistory(state, 'Undo action');

            expect(state.historyStack.length).toBe(0);
            expect(state.historyIndex).toBe(-1);
        });

        it('should remove states after current index (branching)', () => {
            // Build history: [A, B, C]
            pushToHistory(state, 'Action A');
            state.annotations.push(createMockAnnotation({ id: 2 }));
            pushToHistory(state, 'Action B');
            state.annotations.push(createMockAnnotation({ id: 3 }));
            pushToHistory(state, 'Action C');

            expect(state.historyStack.length).toBe(3);
            expect(state.historyIndex).toBe(2);

            // Undo twice: [A, B, C] -> at B
            state.historyIndex = 1;

            // New action: [A, B] -> [A, B, D]
            state.annotations = [createMockAnnotation({ id: 4 })];
            pushToHistory(state, 'Action D');

            expect(state.historyStack.length).toBe(3);
            expect(state.historyStack[0].action).toBe('Action A');
            expect(state.historyStack[1].action).toBe('Action B');
            expect(state.historyStack[2].action).toBe('Action D');
        });

        it('should limit history size', () => {
            state.maxHistorySize = 3;

            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            pushToHistory(state, 'Action 3');
            pushToHistory(state, 'Action 4');

            expect(state.historyStack.length).toBe(3);
            expect(state.historyStack[0].action).toBe('Action 2');
            expect(state.historyStack[1].action).toBe('Action 3');
            expect(state.historyStack[2].action).toBe('Action 4');
        });

        it('should increment historyIndex correctly', () => {
            expect(state.historyIndex).toBe(-1);

            pushToHistory(state, 'Action 1');
            expect(state.historyIndex).toBe(0);

            pushToHistory(state, 'Action 2');
            expect(state.historyIndex).toBe(1);

            pushToHistory(state, 'Action 3');
            expect(state.historyIndex).toBe(2);
        });

        it('should deep clone annotations', () => {
            const originalAnnotation = createMockAnnotation({ id: 1, label: 'Original' });
            state.annotations = [originalAnnotation];

            pushToHistory(state, 'Save state');

            // Modify original annotation
            originalAnnotation.label = 'Modified';

            // Snapshot should have original value
            expect(state.historyStack[0].annotations[0].label).toBe('Original');
        });

        it('should log history action', () => {
            pushToHistory(state, 'Test action');

            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('📚 History: Test action (1/1)')
            );
        });
    });

    describe('undo', () => {
        beforeEach(() => {
            // Setup history with 3 states
            pushToHistory(state, 'Initial state');

            state.annotations = [createMockAnnotation({ id: 1 }), createMockAnnotation({ id: 2 })];
            pushToHistory(state, 'Add annotation 2');

            state.annotations = [
                createMockAnnotation({ id: 1 }),
                createMockAnnotation({ id: 2 }),
                createMockAnnotation({ id: 3 }),
            ];
            pushToHistory(state, 'Add annotation 3');

            // Now at index 2 with 3 annotations
            expect(state.historyIndex).toBe(2);
            expect(state.annotations).toHaveLength(3);
        });

        it('should undo last action', () => {
            undo(state);

            expect(state.historyIndex).toBe(1);
            expect(state.annotations).toHaveLength(2);
        });

        it('should restore previous snapshot', () => {
            undo(state);

            expect(state.annotations[0].id).toBe(1);
            expect(state.annotations[1].id).toBe(2);
        });

        it('should set isUndoRedoAction flag during operation', () => {
            const mockState = {
                ...state,
                isUndoRedoAction: false,
            };

            undo(mockState);

            // Flag should be false after operation
            expect(mockState.isUndoRedoAction).toBe(false);
        });

        it('should do nothing if cannot undo', () => {
            state.historyIndex = 0;
            undo(state);

            expect(state.historyIndex).toBe(0);
        });

        it('should log undo action', () => {
            undo(state);

            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('⏮️ Undo: Add annotation 2')
            );
        });

        it('should allow multiple undos', () => {
            undo(state); // Back to 2 annotations
            expect(state.annotations).toHaveLength(2);

            undo(state); // Back to 1 annotation
            expect(state.annotations).toHaveLength(1);
        });

        it('should log when nothing to undo', () => {
            state.historyIndex = 0;
            undo(state);

            expect(console.log).toHaveBeenCalledWith('⏮️ Nothing to undo');
        });
    });

    describe('redo', () => {
        beforeEach(() => {
            // Setup history with 3 states
            pushToHistory(state, 'Initial state');

            state.annotations = [createMockAnnotation({ id: 1 }), createMockAnnotation({ id: 2 })];
            pushToHistory(state, 'Add annotation 2');

            state.annotations = [
                createMockAnnotation({ id: 1 }),
                createMockAnnotation({ id: 2 }),
                createMockAnnotation({ id: 3 }),
            ];
            pushToHistory(state, 'Add annotation 3');

            // Undo twice to prepare for redo
            undo(state);
            undo(state);

            // Now at index 0 with 1 annotation
            expect(state.historyIndex).toBe(0);
            expect(state.annotations).toHaveLength(1);
        });

        it('should redo last undone action', () => {
            redo(state);

            expect(state.historyIndex).toBe(1);
            expect(state.annotations).toHaveLength(2);
        });

        it('should restore next snapshot', () => {
            redo(state);

            expect(state.annotations[0].id).toBe(1);
            expect(state.annotations[1].id).toBe(2);
        });

        it('should set isUndoRedoAction flag during operation', () => {
            const mockState = {
                ...state,
                isUndoRedoAction: false,
            };

            redo(mockState);

            // Flag should be false after operation
            expect(mockState.isUndoRedoAction).toBe(false);
        });

        it('should do nothing if cannot redo', () => {
            state.historyIndex = 2;
            redo(state);

            expect(state.historyIndex).toBe(2);
        });

        it('should log redo action', () => {
            redo(state);

            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('⏭️ Redo: Add annotation 2')
            );
        });

        it('should allow multiple redos', () => {
            redo(state); // Forward to 2 annotations
            expect(state.annotations).toHaveLength(2);

            redo(state); // Forward to 3 annotations
            expect(state.annotations).toHaveLength(3);
        });

        it('should log when nothing to redo', () => {
            state.historyIndex = 2;
            redo(state);

            expect(console.log).toHaveBeenCalledWith('⏭️ Nothing to redo');
        });
    });

    describe('canUndo', () => {
        it('should return false when historyIndex is 0', () => {
            state.historyIndex = 0;
            expect(canUndo(state)).toBe(false);
        });

        it('should return false when historyIndex is -1', () => {
            state.historyIndex = -1;
            expect(canUndo(state)).toBe(false);
        });

        it('should return true when historyIndex is greater than 0', () => {
            state.historyIndex = 1;
            expect(canUndo(state)).toBe(true);

            state.historyIndex = 5;
            expect(canUndo(state)).toBe(true);
        });

        it('should return correct value after undo operations', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            pushToHistory(state, 'Action 3');
            pushToHistory(state, 'Action 4');

            // At index 3, can undo
            expect(canUndo(state)).toBe(true);

            undo(state); // Now at index 2
            expect(canUndo(state)).toBe(true);

            undo(state); // Now at index 1
            expect(canUndo(state)).toBe(true);

            undo(state); // Now at index 0
            expect(canUndo(state)).toBe(false);
        });
    });

    describe('canRedo', () => {
        it('should return false when at end of history', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            expect(canRedo(state)).toBe(false);
        });

        it('should return false when history is empty', () => {
            expect(canRedo(state)).toBe(false);
        });

        it('should return true after undo', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            undo(state);

            expect(canRedo(state)).toBe(true);
        });

        it('should return false after redo to end', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            undo(state);
            redo(state);

            expect(canRedo(state)).toBe(false);
        });

        it('should handle multiple undo/redo cycles', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            pushToHistory(state, 'Action 3');

            undo(state);
            undo(state);
            expect(canRedo(state)).toBe(true);

            redo(state);
            expect(canRedo(state)).toBe(true);

            redo(state);
            expect(canRedo(state)).toBe(false);
        });
    });

    describe('clearHistory', () => {
        it('should clear history stack', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            pushToHistory(state, 'Action 3');

            clearHistory(state);

            expect(state.historyStack).toEqual([]);
        });

        it('should reset historyIndex to -1', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            clearHistory(state);

            expect(state.historyIndex).toBe(-1);
        });

        it('should log clear action', () => {
            clearHistory(state);

            expect(console.log).toHaveBeenCalledWith('🗑️ History cleared');
        });

        it('should allow new history after clear', () => {
            pushToHistory(state, 'Action 1');
            clearHistory(state);

            pushToHistory(state, 'New Action');

            expect(state.historyStack.length).toBe(1);
            expect(state.historyIndex).toBe(0);
            expect(state.historyStack[0].action).toBe('New Action');
        });
    });

    describe('getHistoryInfo', () => {
        it('should return correct info for empty history', () => {
            const info = getHistoryInfo(state);

            expect(info).toEqual({
                size: 0,
                index: -1,
                canUndo: false,
                canRedo: false,
                current: 'none',
                next: 'none',
                previous: 'none',
            });
        });

        it('should return correct info at start of history', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            state.historyIndex = 0;

            const info = getHistoryInfo(state);

            expect(info.size).toBe(2);
            expect(info.index).toBe(0);
            expect(info.canUndo).toBe(false);
            expect(info.canRedo).toBe(true);
            expect(info.current).toBe('Action 1');
            expect(info.next).toBe('Action 2');
            expect(info.previous).toBe('none');
        });

        it('should return correct info in middle of history', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            pushToHistory(state, 'Action 3');

            state.historyIndex = 1;

            const info = getHistoryInfo(state);

            expect(info.size).toBe(3);
            expect(info.index).toBe(1);
            expect(info.canUndo).toBe(true);
            expect(info.canRedo).toBe(true);
            expect(info.current).toBe('Action 2');
            expect(info.next).toBe('Action 3');
            expect(info.previous).toBe('Action 1');
        });

        it('should return correct info at end of history', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            const info = getHistoryInfo(state);

            expect(info.size).toBe(2);
            expect(info.index).toBe(1);
            expect(info.canUndo).toBe(true);
            expect(info.canRedo).toBe(false);
            expect(info.current).toBe('Action 2');
            expect(info.next).toBe('none');
            expect(info.previous).toBe('Action 1');
        });
    });

    describe('setupUndoRedoKeyboards', () => {
        let mockEventTarget;

        beforeEach(() => {
            mockEventTarget = document.createElement('div');
            vi.spyOn(document, 'addEventListener').mockImplementation((event, handler) => {
                mockEventTarget.addEventListener(event, handler);
            });
        });

        it('should register keyboard event listener', () => {
            setupUndoRedoKeyboards(state);

            expect(document.addEventListener).toHaveBeenCalledWith(
                'keydown',
                expect.any(Function)
            );
        });

        it('should log registration message', () => {
            setupUndoRedoKeyboards(state);

            expect(console.log).toHaveBeenCalledWith(
                '⌨️ Undo/Redo keyboard shortcuts registered'
            );
        });

        it('should handle Ctrl+Z for undo', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            setupUndoRedoKeyboards(state);

            const event = new KeyboardEvent('keydown', {
                key: 'z',
                ctrlKey: true,
                bubbles: true,
            });

            Object.defineProperty(event, 'preventDefault', {
                value: vi.fn(),
            });

            mockEventTarget.dispatchEvent(event);

            expect(state.historyIndex).toBe(0);
        });

        it('should handle Cmd+Z for undo (Mac)', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            setupUndoRedoKeyboards(state);

            const event = new KeyboardEvent('keydown', {
                key: 'z',
                metaKey: true,
                bubbles: true,
            });

            Object.defineProperty(event, 'preventDefault', {
                value: vi.fn(),
            });

            mockEventTarget.dispatchEvent(event);

            expect(state.historyIndex).toBe(0);
        });

        it('should handle Ctrl+Shift+Z for redo', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            undo(state);

            setupUndoRedoKeyboards(state);

            const event = new KeyboardEvent('keydown', {
                key: 'z',
                ctrlKey: true,
                shiftKey: true,
                bubbles: true,
            });

            Object.defineProperty(event, 'preventDefault', {
                value: vi.fn(),
            });

            mockEventTarget.dispatchEvent(event);

            expect(state.historyIndex).toBe(1);
        });

        it('should handle Ctrl+Y for redo', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            undo(state);

            setupUndoRedoKeyboards(state);

            const event = new KeyboardEvent('keydown', {
                key: 'y',
                ctrlKey: true,
                bubbles: true,
            });

            Object.defineProperty(event, 'preventDefault', {
                value: vi.fn(),
            });

            mockEventTarget.dispatchEvent(event);

            expect(state.historyIndex).toBe(1);
        });

        it('should prevent default on undo/redo shortcuts', () => {
            setupUndoRedoKeyboards(state);

            const undoEvent = new KeyboardEvent('keydown', {
                key: 'z',
                ctrlKey: true,
                bubbles: true,
            });

            const preventDefault = vi.fn();
            Object.defineProperty(undoEvent, 'preventDefault', {
                value: preventDefault,
            });

            mockEventTarget.dispatchEvent(undoEvent);

            expect(preventDefault).toHaveBeenCalled();
        });

        it('should not trigger on Ctrl+Shift+Z for undo', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');

            setupUndoRedoKeyboards(state);

            const event = new KeyboardEvent('keydown', {
                key: 'z',
                ctrlKey: true,
                shiftKey: true,
                bubbles: true,
            });

            mockEventTarget.dispatchEvent(event);

            // Should be redo, not undo
            expect(state.historyIndex).toBe(1); // No change (can't redo from end)
        });
    });

    describe('Edge Cases', () => {
        it('should handle empty annotations array', () => {
            state.annotations = [];
            pushToHistory(state, 'Empty state');

            expect(state.historyStack[0].annotations).toEqual([]);
        });

        it('should handle complex annotation objects', () => {
            state.annotations = [
                createMockAnnotation({
                    id: 1,
                    label: 'Complex',
                    metadata: { nested: { deep: 'value' } },
                }),
            ];

            pushToHistory(state, 'Complex annotation');

            expect(state.historyStack[0].annotations[0].metadata.nested.deep).toBe('value');
        });

        it('should maintain history integrity after multiple operations', () => {
            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            pushToHistory(state, 'Action 3');

            undo(state);
            undo(state);
            redo(state);
            pushToHistory(state, 'Action 4');

            const info = getHistoryInfo(state);
            expect(info.size).toBe(3);
            expect(state.historyStack[0].action).toBe('Action 1');
            expect(state.historyStack[1].action).toBe('Action 2');
            expect(state.historyStack[2].action).toBe('Action 4');
        });

        it('should handle undo/redo at boundaries gracefully', () => {
            expect(() => undo(state)).not.toThrow();
            expect(() => redo(state)).not.toThrow();

            pushToHistory(state, 'Action 1');

            expect(() => redo(state)).not.toThrow();
            expect(() => undo(state)).not.toThrow();
            expect(() => undo(state)).not.toThrow();
        });

        it('should handle history size limit with undo/redo', () => {
            state.maxHistorySize = 3;

            pushToHistory(state, 'Action 1');
            pushToHistory(state, 'Action 2');
            pushToHistory(state, 'Action 3');
            pushToHistory(state, 'Action 4');

            undo(state);
            undo(state);

            expect(state.historyIndex).toBe(0);
            expect(canUndo(state)).toBe(false);
            expect(canRedo(state)).toBe(true);
        });
    });
});
