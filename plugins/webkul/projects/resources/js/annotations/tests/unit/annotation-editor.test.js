/**
 * Unit Tests for Annotation Editor Module
 * Run with: node annotation-editor.test.js
 */

import { createAnnotationEditor } from '../../annotation-editor.js';

console.log('🧪 Testing Annotation Editor Module\n');

const editor = createAnnotationEditor();
let passedTests = 0;
let totalTests = 0;

function test(name, fn) {
    totalTests++;
    try {
        fn();
        console.log(`✅ ${name}`);
        passedTests++;
    } catch (error) {
        console.log(`❌ ${name}`);
        console.error(`   Error: ${error.message}`);
    }
}

function assert(condition, message) {
    if (!condition) {
        throw new Error(message || 'Assertion failed');
    }
}

// Mock annotations
const mockAnnotations = [
    { id: 1, x: 0.1, y: 0.1, width: 0.2, height: 0.2, text: 'Room 1' },
    { id: 2, x: 0.3, y: 0.3, width: 0.2, height: 0.2, text: 'Room 2' },
    { id: 3, x: 0.5, y: 0.5, width: 0.2, height: 0.2, text: 'Room 3' }
];

// Test saveState
test('saveState adds to undo stack', () => {
    const result = editor.saveState(mockAnnotations, []);
    assert(result.undoStack.length === 1, 'Undo stack should have 1 item');
    assert(result.redoStack.length === 0, 'Redo stack should be cleared');
});

test('saveState clears redo stack', () => {
    const result = editor.saveState(mockAnnotations, [], 20);
    assert(result.redoStack.length === 0, 'Redo stack should be empty');
});

test('saveState limits stack size', () => {
    const largeStack = new Array(25).fill([{ id: 1 }]);
    const result = editor.saveState(mockAnnotations, largeStack, 20);
    assert(result.undoStack.length === 20, 'Stack should be limited to max size');
});

test('saveState deep clones annotations', () => {
    const result = editor.saveState(mockAnnotations, []);
    assert(result.undoStack[0] !== mockAnnotations, 'Should be a new array');
    assert(result.undoStack[0][0] !== mockAnnotations[0], 'Should deep clone objects');
});

// Test undo
test('undo returns null when stack is empty', () => {
    const result = editor.undo(mockAnnotations, [], []);
    assert(result === null, 'Should return null with empty undo stack');
});

test('undo restores previous state', () => {
    const previousState = [{ id: 1, text: 'Old' }];
    const undoStack = [previousState];
    const result = editor.undo(mockAnnotations, undoStack, []);

    assert(result !== null, 'Should return result object');
    assert(result.annotations === previousState, 'Should restore previous state');
    assert(result.undoStack.length === 0, 'Undo stack should be reduced');
    assert(result.redoStack.length === 1, 'Redo stack should have current state');
});

// Test redo
test('redo returns null when stack is empty', () => {
    const result = editor.redo(mockAnnotations, [], []);
    assert(result === null, 'Should return null with empty redo stack');
});

test('redo restores next state', () => {
    const nextState = [{ id: 1, text: 'New' }];
    const redoStack = [nextState];
    const result = editor.redo(mockAnnotations, [], redoStack);

    assert(result !== null, 'Should return result object');
    assert(result.annotations === nextState, 'Should restore next state');
    assert(result.redoStack.length === 0, 'Redo stack should be reduced');
    assert(result.undoStack.length === 1, 'Undo stack should have current state');
});

// Test deleteSelected
test('deleteSelected returns null when nothing selected', () => {
    const result = editor.deleteSelected(mockAnnotations, null);
    assert(result === null, 'Should return null when selectedId is null');
});

test('deleteSelected returns null for non-existent ID', () => {
    const result = editor.deleteSelected(mockAnnotations, 999);
    assert(result === null, 'Should return null for non-existent ID');
});

test('deleteSelected removes annotation by ID', () => {
    const result = editor.deleteSelected(mockAnnotations, 2);
    assert(result !== null, 'Should return result object');
    assert(result.annotations.length === 2, 'Should have 2 annotations left');
    assert(result.selectedId === null, 'Selected ID should be cleared');
    assert(!result.annotations.find(a => a.id === 2), 'ID 2 should be removed');
});

// Test removeAnnotation
test('removeAnnotation removes by index', () => {
    const result = editor.removeAnnotation(mockAnnotations, 1);
    assert(result.length === 2, 'Should have 2 annotations left');
    assert(result[0].id === 1, 'First annotation should remain');
    assert(result[1].id === 3, 'Third annotation should become second');
});

test('removeAnnotation handles invalid index', () => {
    const result = editor.removeAnnotation(mockAnnotations, 999);
    assert(result.length === 3, 'Should return original array for invalid index');
});

test('removeAnnotation handles negative index', () => {
    const result = editor.removeAnnotation(mockAnnotations, -1);
    assert(result.length === 3, 'Should return original array for negative index');
});

// Test clearLastAnnotation
test('clearLastAnnotation removes last item', () => {
    const result = editor.clearLastAnnotation(mockAnnotations);
    assert(result.length === 2, 'Should have 2 annotations left');
    assert(result[result.length - 1].id === 2, 'Last item should now be ID 2');
});

test('clearLastAnnotation handles empty array', () => {
    const result = editor.clearLastAnnotation([]);
    assert(result.length === 0, 'Should return empty array');
});

// Test clearAllAnnotations
test('clearAllAnnotations clears when confirmed', () => {
    const mockConfirm = () => true;
    const result = editor.clearAllAnnotations(mockAnnotations, mockConfirm);
    assert(result.length === 0, 'Should return empty array when confirmed');
});

test('clearAllAnnotations preserves when cancelled', () => {
    const mockConfirm = () => false;
    const result = editor.clearAllAnnotations(mockAnnotations, mockConfirm);
    assert(result.length === 3, 'Should preserve array when cancelled');
});

// Test selectAnnotation
test('selectAnnotation finds annotation at coordinates', () => {
    const result = editor.selectAnnotation(mockAnnotations, 0.15, 0.15);
    assert(result === 1, 'Should find annotation with ID 1');
});

test('selectAnnotation returns null for empty space', () => {
    const result = editor.selectAnnotation(mockAnnotations, 0.9, 0.9);
    assert(result === null, 'Should return null when no annotation found');
});

test('selectAnnotation respects tolerance', () => {
    const result = editor.selectAnnotation(mockAnnotations, 0.095, 0.095, 0.01);
    assert(result === 1, 'Should find annotation within tolerance');
});

// Test deselectAnnotation
test('deselectAnnotation always returns null', () => {
    const result = editor.deselectAnnotation();
    assert(result === null, 'Should always return null');
});

console.log(`\n📊 Results: ${passedTests}/${totalTests} tests passed`);

if (passedTests === totalTests) {
    console.log('✅ All annotation editor tests passed!');
    process.exit(0);
} else {
    console.log(`❌ ${totalTests - passedTests} test(s) failed`);
    process.exit(1);
}
