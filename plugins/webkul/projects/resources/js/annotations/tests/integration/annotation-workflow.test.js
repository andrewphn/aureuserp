/**
 * Integration Tests for Annotation Workflow
 * Tests module interactions
 * Run with: node annotation-workflow.test.js
 */

import { createAnnotationDrawer } from '../../annotation-drawer.js';
import { createAnnotationEditor } from '../../annotation-editor.js';
import { createCascadeFilters } from '../../cascade-filters.js';

console.log('🧪 Testing Annotation Workflow Integration\n');

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

// Initialize modules
const drawer = createAnnotationDrawer();
const editor = createAnnotationEditor();
const filters = createCascadeFilters();

// Test: Drawing + Editor Integration
test('Drawer creates annotation, editor can undo it', () => {
    const mockCanvas = {
        getBoundingClientRect: () => ({ left: 0, top: 0 }),
        width: 800,
        height: 600
    };
    const mockEvent = { clientX: 200, clientY: 200 };
    const drawState = { isDrawing: true, startX: 50, startY: 50 };
    const options = {
        annotationType: 'room',
        roomType: 'kitchen',
        projectNumber: 'TFW-0001',
        roomCodes: { kitchen: 'K' },
        roomColors: { kitchen: '#3B82F6' }
    };

    // Draw annotation
    const newAnnotation = drawer.stopDrawing(mockEvent, mockCanvas, drawState, options, []);
    assert(newAnnotation !== null, 'Should create annotation');

    // Save to undo stack
    let annotations = [newAnnotation];
    let undoStack = [];
    let redoStack = [];

    const saveResult = editor.saveState(annotations, undoStack);
    undoStack = saveResult.undoStack;
    redoStack = saveResult.redoStack;

    // Add another annotation
    annotations.push({ ...newAnnotation, id: Date.now() + 1 });

    // Undo should restore single annotation
    const undoResult = editor.undo(annotations, undoStack, redoStack);
    assert(undoResult.annotations.length === 1, 'Undo should restore previous state');
});

// Test: Cascade Filters + Editor Integration
test('Cascade filters work with editor selection', () => {
    const mockRooms = [
        { id: 1, name: 'Kitchen' },
        { id: 2, name: 'Pantry' }
    ];

    const mockRoomLocations = [
        { id: 10, room_id: 1, name: 'North Wall' },
        { id: 11, room_id: 1, name: 'Island' },
        { id: 12, room_id: 2, name: 'South Wall' }
    ];

    const mockCabinetRuns = [
        { id: 100, room_location_id: 10, name: 'Upper Run' },
        { id: 101, room_location_id: 11, name: 'Base Run' }
    ];

    // Filter room locations
    const filteredLocations = filters.filterRoomLocations(1, mockRoomLocations);
    assert(filteredLocations.length === 2, 'Should filter 2 locations for kitchen');

    // Filter cabinet runs
    const filteredRuns = filters.filterCabinetRuns(10, mockCabinetRuns);
    assert(filteredRuns.length === 1, 'Should filter 1 run for North Wall');

    // Reset selections
    const reset = filters.resetChildSelections();
    assert(reset.filteredRoomLocations.length === 0, 'Reset should clear filtered locations');
});

// Test: Draw, Edit, Save Workflow
test('Complete draw → edit → save workflow', () => {
    const mockCanvas = {
        getBoundingClientRect: () => ({ left: 0, top: 0 }),
        width: 800,
        height: 600
    };

    const options = {
        annotationType: 'room',
        roomType: 'kitchen',
        projectNumber: 'TFW-0001',
        roomCodes: { kitchen: 'K' },
        roomColors: { kitchen: '#3B82F6' }
    };

    let annotations = [];
    let undoStack = [];
    let redoStack = [];

    // Step 1: Draw 3 annotations
    for (let i = 0; i < 3; i++) {
        const mockEvent = { clientX: 100 + (i * 50), clientY: 100 + (i * 50) };
        const drawState = { isDrawing: true, startX: 50 + (i * 50), startY: 50 + (i * 50) };

        const newAnnotation = drawer.stopDrawing(mockEvent, mockCanvas, drawState, options, annotations);
        if (newAnnotation) {
            const saveResult = editor.saveState(annotations, undoStack);
            undoStack = saveResult.undoStack;
            redoStack = saveResult.redoStack;
            annotations.push(newAnnotation);
        }
    }

    assert(annotations.length === 3, 'Should have 3 annotations');

    // Step 2: Delete one annotation
    const deleteResult = editor.deleteSelected(annotations, annotations[1].id);
    assert(deleteResult.annotations.length === 2, 'Should have 2 annotations after delete');

    // Step 3: Undo delete
    const undoResult = editor.undo(deleteResult.annotations, undoStack, redoStack);
    assert(undoResult.annotations.length === 3, 'Undo should restore deleted annotation');

    // Step 4: Redo delete
    const redoResult = editor.redo(undoResult.annotations, undoResult.undoStack, undoResult.redoStack);
    assert(redoResult.annotations.length === 2, 'Redo should delete annotation again');
});

// Test: Label generation with different annotation types
test('Drawer generates correct labels for different annotation types', () => {
    const roomCodes = {
        kitchen: 'K',
        pantry: 'P',
        bathroom: 'B'
    };

    const roomLabel = drawer.generateLabel('room', 'kitchen', 'TFW-0001', roomCodes, 0);
    assert(roomLabel === 'TFW-0001-K', 'Kitchen label should be TFW-0001-K');

    const pantryLabel = drawer.generateLabel('room', 'pantry', 'TFW-0001', roomCodes, 1);
    assert(pantryLabel === 'TFW-0001-P', 'Pantry label should be TFW-0001-P');

    const fallbackLabel = drawer.generateLabel('room', null, 'TFW-0001', roomCodes, 5);
    assert(fallbackLabel === 'TFW-0001-6', 'Fallback label should be TFW-0001-6');
});

// Test: Editor state persistence
test('Editor maintains undo/redo state correctly', () => {
    let annotations = [
        { id: 1, text: 'State 1' }
    ];
    let undoStack = [];
    let redoStack = [];

    // Save state 1
    const save1 = editor.saveState(annotations, undoStack);
    undoStack = save1.undoStack;
    redoStack = save1.redoStack;

    // Modify to state 2
    annotations = [{ id: 1, text: 'State 1' }, { id: 2, text: 'State 2' }];
    const save2 = editor.saveState(annotations, undoStack);
    undoStack = save2.undoStack;
    redoStack = save2.redoStack;

    // Modify to state 3
    annotations = [{ id: 1, text: 'State 1' }, { id: 2, text: 'State 2' }, { id: 3, text: 'State 3' }];

    assert(undoStack.length === 2, 'Should have 2 states in undo stack');

    // Undo to state 2
    const undo1 = editor.undo(annotations, undoStack, redoStack);
    assert(undo1.annotations.length === 2, 'Should return to state 2');
    assert(undo1.redoStack.length === 1, 'Should have 1 state in redo stack');

    // Redo to state 3
    const redo1 = editor.redo(undo1.annotations, undo1.undoStack, undo1.redoStack);
    assert(redo1.annotations.length === 3, 'Should return to state 3');
});

// Test: Annotation selection and editing
test('Editor can select and delete annotations', () => {
    const annotations = [
        { id: 1, x: 0.1, y: 0.1, width: 0.2, height: 0.2 },
        { id: 2, x: 0.4, y: 0.4, width: 0.2, height: 0.2 },
        { id: 3, x: 0.7, y: 0.7, width: 0.2, height: 0.2 }
    ];

    // Select annotation at 0.5, 0.5 (should find annotation 2)
    const selectedId = editor.selectAnnotation(annotations, 0.5, 0.5);
    assert(selectedId === 2, 'Should select annotation 2');

    // Delete selected
    const deleteResult = editor.deleteSelected(annotations, selectedId);
    assert(deleteResult.annotations.length === 2, 'Should have 2 annotations left');
    assert(!deleteResult.annotations.find(a => a.id === 2), 'Annotation 2 should be removed');
});

console.log(`\n📊 Results: ${passedTests}/${totalTests} tests passed`);

if (passedTests === totalTests) {
    console.log('✅ All integration tests passed!');
    process.exit(0);
} else {
    console.log(`❌ ${totalTests - passedTests} test(s) failed`);
    process.exit(1);
}
