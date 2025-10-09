/**
 * Phase 4: Annotation Editing - Integration Tests
 * Tests complete edit workflows with Alpine.js component state
 */

import { createAnnotationDrawer } from '../../annotation-drawer.js';
import { createAnnotationEditor } from '../../annotation-editor.js';

const drawer = createAnnotationDrawer();
const editor = createAnnotationEditor();

// Mock Alpine component state
function createMockComponentState() {
    return {
        // Annotations
        annotations: [
            { id: 1001, x: 0.2, y: 0.3, width: 0.15, height: 0.12, color: '#3B82F6', text: 'Room 1' },
            { id: 1002, x: 0.5, y: 0.5, width: 0.2, height: 0.15, color: '#10B981', text: 'Room 2' }
        ],

        // State
        currentTool: 'select',
        selectedAnnotationId: null,
        isResizing: false,
        isMoving: false,
        resizeHandle: null,
        moveStartX: 0,
        moveStartY: 0,

        // Undo/redo
        undoStack: [],
        redoStack: [],

        // Mock canvas
        $refs: {
            annotationCanvas: {
                width: 1000,
                height: 800,
                getBoundingClientRect: () => ({ left: 0, top: 0 })
            }
        }
    };
}

function assertEqual(actual, expected, message) {
    if (JSON.stringify(actual) !== JSON.stringify(expected)) {
        throw new Error(`${message}\nExpected: ${JSON.stringify(expected)}\nActual: ${JSON.stringify(actual)}`);
    }
}

function assert(condition, message) {
    if (!condition) {
        throw new Error(message);
    }
}

console.log('🧪 Testing Phase 4: Integration Workflows\n');

let passed = 0;
let total = 0;

// ========== SCENARIO 1: SELECT AND RESIZE ANNOTATION ==========

total++;
try {
    const component = createMockComponentState();

    // Step 1: Select annotation by clicking
    const clickX = 0.275 * component.$refs.annotationCanvas.width; // Center of annotation 1
    const clickY = 0.36 * component.$refs.annotationCanvas.height;

    const clicked = drawer.getClickedAnnotation(
        clickX,
        clickY,
        component.annotations,
        component.$refs.annotationCanvas
    );

    component.selectedAnnotationId = clicked.id;
    assertEqual(component.selectedAnnotationId, 1001, 'Annotation 1 should be selected');

    // Step 2: Start resizing by grabbing BR handle
    const brX = (0.2 + 0.15) * component.$refs.annotationCanvas.width;
    const brY = (0.3 + 0.12) * component.$refs.annotationCanvas.height;

    const handle = drawer.getResizeHandle(brX, brY, clicked, component.$refs.annotationCanvas);
    assertEqual(handle, 'br', 'Should detect BR handle');

    component.isResizing = true;
    component.resizeHandle = handle;

    // Step 3: Drag BR handle to resize
    const newX = (0.2 + 0.25) * component.$refs.annotationCanvas.width; // Increase width by 0.1
    const newY = (0.3 + 0.20) * component.$refs.annotationCanvas.height; // Increase height by 0.08

    const newBounds = drawer.resizeAnnotation(
        clicked,
        component.resizeHandle,
        newX,
        newY,
        component.$refs.annotationCanvas
    );

    Object.assign(clicked, newBounds);

    // Step 4: Finish resizing
    component.isResizing = false;
    component.resizeHandle = null;

    // Verify
    assert(clicked.width >= 0.24, 'Width should have increased');
    assert(clicked.height >= 0.19, 'Height should have increased');

    console.log('✅ Scenario 1 passed: Select and resize annotation workflow');
    passed++;
} catch (e) {
    console.error('❌ Scenario 1 failed:', e.message);
}

// ========== SCENARIO 2: SELECT AND MOVE ANNOTATION ==========

total++;
try {
    const component = createMockComponentState();

    // Step 1: Select annotation 2
    const clickX = 0.6 * component.$refs.annotationCanvas.width;
    const clickY = 0.575 * component.$refs.annotationCanvas.height;

    const clicked = drawer.getClickedAnnotation(
        clickX,
        clickY,
        component.annotations,
        component.$refs.annotationCanvas
    );

    component.selectedAnnotationId = clicked.id;
    assertEqual(component.selectedAnnotationId, 1002, 'Annotation 2 should be selected');

    // Store original position
    const origX = clicked.x;
    const origY = clicked.y;

    // Step 2: Start moving
    component.isMoving = true;
    component.moveStartX = clickX;
    component.moveStartY = clickY;

    // Step 3: Drag annotation
    const currentX = clickX + 100; // Move 100px right
    const currentY = clickY + 50;  // Move 50px down

    const deltaX = currentX - component.moveStartX;
    const deltaY = currentY - component.moveStartY;

    const newPos = drawer.moveAnnotation(
        clicked,
        deltaX,
        deltaY,
        component.$refs.annotationCanvas
    );

    clicked.x = newPos.x;
    clicked.y = newPos.y;

    // Step 4: Finish moving
    component.isMoving = false;

    // Verify
    assert(clicked.x > origX, 'X should have moved right');
    assert(clicked.y > origY, 'Y should have moved down');

    console.log('✅ Scenario 2 passed: Select and move annotation workflow');
    passed++;
} catch (e) {
    console.error('❌ Scenario 2 failed:', e.message);
}

// ========== SCENARIO 3: SELECT AND DELETE ANNOTATION ==========

total++;
try {
    const component = createMockComponentState();

    // Step 1: Select annotation 1
    component.selectedAnnotationId = 1001;

    // Step 2: Save state for undo
    const stateUpdate = editor.saveState(component.annotations, component.undoStack);
    component.undoStack = stateUpdate.undoStack;

    // Step 3: Delete selected annotation
    const result = editor.deleteSelected(component.annotations, component.selectedAnnotationId);

    component.annotations = result.annotations;
    component.selectedAnnotationId = result.selectedId;

    // Verify
    assertEqual(component.annotations.length, 1, 'Should have 1 annotation left');
    assertEqual(component.annotations[0].id, 1002, 'Annotation 2 should remain');
    assertEqual(component.selectedAnnotationId, null, 'Selection should be cleared');

    console.log('✅ Scenario 3 passed: Select and delete annotation workflow');
    passed++;
} catch (e) {
    console.error('❌ Scenario 3 failed:', e.message);
}

// ========== SCENARIO 4: UNDO/REDO WORKFLOW ==========

total++;
try {
    const component = createMockComponentState();

    // Step 1: Save initial state
    let stateUpdate = editor.saveState(component.annotations, component.undoStack);
    component.undoStack = stateUpdate.undoStack;

    // Step 2: Delete annotation 1
    component.selectedAnnotationId = 1001;
    const deleteResult = editor.deleteSelected(component.annotations, component.selectedAnnotationId);
    component.annotations = deleteResult.annotations;

    assertEqual(component.annotations.length, 1, 'Should have 1 annotation after delete');

    // Step 3: Undo delete
    const undoResult = editor.undo(component.annotations, component.undoStack, component.redoStack);
    component.annotations = undoResult.annotations;
    component.undoStack = undoResult.undoStack;
    component.redoStack = undoResult.redoStack;

    assertEqual(component.annotations.length, 2, 'Should have 2 annotations after undo');

    // Step 4: Redo delete
    const redoResult = editor.redo(component.annotations, component.undoStack, component.redoStack);
    component.annotations = redoResult.annotations;
    component.undoStack = redoResult.undoStack;
    component.redoStack = redoResult.redoStack;

    assertEqual(component.annotations.length, 1, 'Should have 1 annotation after redo');

    console.log('✅ Scenario 4 passed: Undo/redo workflow');
    passed++;
} catch (e) {
    console.error('❌ Scenario 4 failed:', e.message);
}

// ========== SCENARIO 5: RESIZE HANDLES NEGATIVE DIMENSIONS ==========

total++;
try {
    const component = createMockComponentState();

    // Step 1: Select annotation
    const testAnnotation = component.annotations[0];
    component.selectedAnnotationId = testAnnotation.id;

    // Step 2: Drag BR handle past TL corner (would create negative dimensions)
    const newX = (testAnnotation.x - 0.1) * component.$refs.annotationCanvas.width;
    const newY = (testAnnotation.y - 0.1) * component.$refs.annotationCanvas.height;

    const newBounds = drawer.resizeAnnotation(
        testAnnotation,
        'br',
        newX,
        newY,
        component.$refs.annotationCanvas
    );

    // Verify - dimensions should be auto-corrected to positive
    assert(newBounds.width > 0, 'Width should be positive after correction');
    assert(newBounds.height > 0, 'Height should be positive after correction');

    console.log('✅ Scenario 5 passed: Resize handles negative dimensions');
    passed++;
} catch (e) {
    console.error('❌ Scenario 5 failed:', e.message);
}

// ========== SCENARIO 6: MOVE WITH BOUNDARY CONSTRAINTS ==========

total++;
try {
    const component = createMockComponentState();

    // Step 1: Select annotation near bottom edge
    const edgeAnnotation = { id: 1004, x: 0.5, y: 0.85, width: 0.1, height: 0.1 };
    component.annotations.push(edgeAnnotation);
    component.selectedAnnotationId = 1004;

    // Step 2: Try to move past bottom boundary
    const deltaX = 0;
    const deltaY = 200; // Try to move far down

    const newPos = drawer.moveAnnotation(
        edgeAnnotation,
        deltaX,
        deltaY,
        component.$refs.annotationCanvas
    );

    // Verify - should be constrained
    assert(newPos.y + edgeAnnotation.height <= 1.0, 'Should not exceed bottom boundary');

    console.log('✅ Scenario 6 passed: Move with boundary constraints');
    passed++;
} catch (e) {
    console.error('❌ Scenario 6 failed:', e.message);
}

// ========== SCENARIO 7: DESELECT BY CLICKING EMPTY SPACE ==========

total++;
try {
    const component = createMockComponentState();

    // Step 1: Select annotation
    component.selectedAnnotationId = 1001;
    assert(component.selectedAnnotationId !== null, 'Annotation should be selected');

    // Step 2: Click empty space
    const emptyX = 0.05 * component.$refs.annotationCanvas.width;
    const emptyY = 0.05 * component.$refs.annotationCanvas.height;

    const clicked = drawer.getClickedAnnotation(
        emptyX,
        emptyY,
        component.annotations,
        component.$refs.annotationCanvas
    );

    if (!clicked) {
        component.selectedAnnotationId = null;
    }

    // Verify
    assertEqual(component.selectedAnnotationId, null, 'Selection should be cleared');

    console.log('✅ Scenario 7 passed: Deselect by clicking empty space');
    passed++;
} catch (e) {
    console.error('❌ Scenario 7 failed:', e.message);
}

// ========== SCENARIO 8: SWITCH SELECTION BETWEEN ANNOTATIONS ==========

total++;
try {
    const component = createMockComponentState();

    // Step 1: Select annotation 1
    component.selectedAnnotationId = 1001;
    assertEqual(component.selectedAnnotationId, 1001, 'Annotation 1 should be selected');

    // Step 2: Click annotation 2
    const clickX = 0.6 * component.$refs.annotationCanvas.width;
    const clickY = 0.575 * component.$refs.annotationCanvas.height;

    const clicked = drawer.getClickedAnnotation(
        clickX,
        clickY,
        component.annotations,
        component.$refs.annotationCanvas
    );

    component.selectedAnnotationId = clicked.id;

    // Verify
    assertEqual(component.selectedAnnotationId, 1002, 'Selection should switch to annotation 2');

    console.log('✅ Scenario 8 passed: Switch selection between annotations');
    passed++;
} catch (e) {
    console.error('❌ Scenario 8 failed:', e.message);
}

// ========== SUMMARY ==========

console.log(`\n${'='.repeat(50)}`);
console.log(`Phase 4 Integration Test Results: ${passed}/${total} tests passed`);
console.log(`${'='.repeat(50)}\n`);

if (passed === total) {
    console.log('🎉 All Phase 4 integration tests passed!\n');
    process.exit(0);
} else {
    console.log(`❌ ${total - passed} test(s) failed\n`);
    process.exit(1);
}
