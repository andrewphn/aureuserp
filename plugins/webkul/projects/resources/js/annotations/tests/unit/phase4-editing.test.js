/**
 * Phase 4: Annotation Editing - Unit Tests
 * Tests for resize, move, delete, and undo/redo functionality
 */

import { createAnnotationDrawer } from '../../annotation-drawer.js';

const drawer = createAnnotationDrawer();

// Test data
const testAnnotation = {
    id: 1001,
    x: 0.2,      // 20% from left
    y: 0.3,      // 30% from top
    width: 0.15, // 15% of canvas width
    height: 0.12, // 12% of canvas height
    color: '#3B82F6',
    text: 'Test Annotation'
};

const mockCanvas = {
    width: 1000,
    height: 800
};

// Test utilities
function assertEqual(actual, expected, message) {
    if (JSON.stringify(actual) !== JSON.stringify(expected)) {
        throw new Error(`${message}\nExpected: ${JSON.stringify(expected)}\nActual: ${JSON.stringify(actual)}`);
    }
}

function assertNear(actual, expected, tolerance, message) {
    if (Math.abs(actual - expected) > tolerance) {
        throw new Error(`${message}\nExpected: ${expected} (±${tolerance})\nActual: ${actual}`);
    }
}

// ========== RESIZE HANDLE DETECTION TESTS ==========

console.log('🧪 Testing Phase 4: Resize Handle Detection\n');

let passed = 0;
let total = 0;

// Test 1: getResizeHandle detects top-left handle
total++;
try {
    const x = testAnnotation.x * mockCanvas.width;
    const y = testAnnotation.y * mockCanvas.height;

    const handle = drawer.getResizeHandle(x, y, testAnnotation, mockCanvas);
    assertEqual(handle, 'tl', 'Should detect top-left handle');
    console.log('✅ Test 1 passed: Top-left handle detected');
    passed++;
} catch (e) {
    console.error('❌ Test 1 failed:', e.message);
}

// Test 2: getResizeHandle detects top-right handle
total++;
try {
    const x = (testAnnotation.x + testAnnotation.width) * mockCanvas.width;
    const y = testAnnotation.y * mockCanvas.height;

    const handle = drawer.getResizeHandle(x, y, testAnnotation, mockCanvas);
    assertEqual(handle, 'tr', 'Should detect top-right handle');
    console.log('✅ Test 2 passed: Top-right handle detected');
    passed++;
} catch (e) {
    console.error('❌ Test 2 failed:', e.message);
}

// Test 3: getResizeHandle detects bottom-left handle
total++;
try {
    const x = testAnnotation.x * mockCanvas.width;
    const y = (testAnnotation.y + testAnnotation.height) * mockCanvas.height;

    const handle = drawer.getResizeHandle(x, y, testAnnotation, mockCanvas);
    assertEqual(handle, 'bl', 'Should detect bottom-left handle');
    console.log('✅ Test 3 passed: Bottom-left handle detected');
    passed++;
} catch (e) {
    console.error('❌ Test 3 failed:', e.message);
}

// Test 4: getResizeHandle detects bottom-right handle
total++;
try {
    const x = (testAnnotation.x + testAnnotation.width) * mockCanvas.width;
    const y = (testAnnotation.y + testAnnotation.height) * mockCanvas.height;

    const handle = drawer.getResizeHandle(x, y, testAnnotation, mockCanvas);
    assertEqual(handle, 'br', 'Should detect bottom-right handle');
    console.log('✅ Test 4 passed: Bottom-right handle detected');
    passed++;
} catch (e) {
    console.error('❌ Test 4 failed:', e.message);
}

// Test 5: getResizeHandle returns null for center click
total++;
try {
    const x = (testAnnotation.x + testAnnotation.width / 2) * mockCanvas.width;
    const y = (testAnnotation.y + testAnnotation.height / 2) * mockCanvas.height;

    const handle = drawer.getResizeHandle(x, y, testAnnotation, mockCanvas);
    assertEqual(handle, null, 'Should return null for center click');
    console.log('✅ Test 5 passed: Center click returns null');
    passed++;
} catch (e) {
    console.error('❌ Test 5 failed:', e.message);
}

// Test 6: getResizeHandle has tolerance for easier clicking
total++;
try {
    const x = testAnnotation.x * mockCanvas.width + 4; // 4px away from handle (within 5px tolerance)
    const y = testAnnotation.y * mockCanvas.height + 4;

    const handle = drawer.getResizeHandle(x, y, testAnnotation, mockCanvas);
    assertEqual(handle, 'tl', 'Should detect handle within tolerance');
    console.log('✅ Test 6 passed: Handle tolerance works');
    passed++;
} catch (e) {
    console.error('❌ Test 6 failed:', e.message);
}

// ========== RESIZE ANNOTATION TESTS ==========

console.log('\n🧪 Testing Phase 4: Resize Annotation\n');

// Test 7: resizeAnnotation - bottom-right handle drag
total++;
try {
    const newX = (testAnnotation.x + testAnnotation.width + 0.1) * mockCanvas.width;
    const newY = (testAnnotation.y + testAnnotation.height + 0.08) * mockCanvas.height;

    const result = drawer.resizeAnnotation(testAnnotation, 'br', newX, newY, mockCanvas);

    assertNear(result.x, testAnnotation.x, 0.01, 'X should not change for BR resize');
    assertNear(result.y, testAnnotation.y, 0.01, 'Y should not change for BR resize');
    assertNear(result.width, 0.25, 0.01, 'Width should increase by 0.1');
    assertNear(result.height, 0.20, 0.01, 'Height should increase by 0.08');
    console.log('✅ Test 7 passed: Bottom-right resize works');
    passed++;
} catch (e) {
    console.error('❌ Test 7 failed:', e.message);
}

// Test 8: resizeAnnotation - top-left handle drag
total++;
try {
    const newX = (testAnnotation.x - 0.05) * mockCanvas.width;
    const newY = (testAnnotation.y - 0.04) * mockCanvas.height;

    const result = drawer.resizeAnnotation(testAnnotation, 'tl', newX, newY, mockCanvas);

    assertNear(result.x, 0.15, 0.01, 'X should move left by 0.05');
    assertNear(result.y, 0.26, 0.01, 'Y should move up by 0.04');
    assertNear(result.width, 0.20, 0.01, 'Width should increase by 0.05');
    assertNear(result.height, 0.16, 0.01, 'Height should increase by 0.04');
    console.log('✅ Test 8 passed: Top-left resize works');
    passed++;
} catch (e) {
    console.error('❌ Test 8 failed:', e.message);
}

// Test 9: resizeAnnotation handles negative dimensions (flipping)
total++;
try {
    // Drag BR handle past TL corner
    const newX = (testAnnotation.x - 0.05) * mockCanvas.width;
    const newY = (testAnnotation.y - 0.05) * mockCanvas.height;

    const result = drawer.resizeAnnotation(testAnnotation, 'br', newX, newY, mockCanvas);

    // Should auto-correct to positive dimensions
    if (result.width < 0 || result.height < 0) {
        throw new Error('Dimensions should be positive');
    }
    console.log('✅ Test 9 passed: Negative dimension handling works');
    passed++;
} catch (e) {
    console.error('❌ Test 9 failed:', e.message);
}

// ========== MOVE ANNOTATION TESTS ==========

console.log('\n🧪 Testing Phase 4: Move Annotation\n');

// Test 10: moveAnnotation - simple move
total++;
try {
    const deltaX = 50;  // 50px right
    const deltaY = 30;  // 30px down

    const result = drawer.moveAnnotation(testAnnotation, deltaX, deltaY, mockCanvas);

    assertNear(result.x, 0.25, 0.01, 'X should move right by 50px (0.05)');
    assertNear(result.y, 0.3375, 0.01, 'Y should move down by 30px (0.0375)');
    console.log('✅ Test 10 passed: Simple move works');
    passed++;
} catch (e) {
    console.error('❌ Test 10 failed:', e.message);
}

// Test 11: moveAnnotation - boundary constraint (left edge)
total++;
try {
    const deltaX = -300;  // Try to move far left (off canvas)
    const deltaY = 0;

    const result = drawer.moveAnnotation(testAnnotation, deltaX, deltaY, mockCanvas);

    assertEqual(result.x, 0, 'X should be constrained to 0 (left edge)');
    console.log('✅ Test 11 passed: Left boundary constraint works');
    passed++;
} catch (e) {
    console.error('❌ Test 11 failed:', e.message);
}

// Test 12: moveAnnotation - boundary constraint (right edge)
total++;
try {
    const deltaX = 1000;  // Try to move far right (off canvas)
    const deltaY = 0;

    const result = drawer.moveAnnotation(testAnnotation, deltaX, deltaY, mockCanvas);

    const maxX = 1 - testAnnotation.width; // Right edge constraint
    assertNear(result.x, maxX, 0.01, 'X should be constrained to right edge');
    console.log('✅ Test 12 passed: Right boundary constraint works');
    passed++;
} catch (e) {
    console.error('❌ Test 12 failed:', e.message);
}

// Test 13: moveAnnotation - boundary constraint (top edge)
total++;
try {
    const deltaX = 0;
    const deltaY = -300;  // Try to move far up (off canvas)

    const result = drawer.moveAnnotation(testAnnotation, deltaX, deltaY, mockCanvas);

    assertEqual(result.y, 0, 'Y should be constrained to 0 (top edge)');
    console.log('✅ Test 13 passed: Top boundary constraint works');
    passed++;
} catch (e) {
    console.error('❌ Test 13 failed:', e.message);
}

// Test 14: moveAnnotation - boundary constraint (bottom edge)
total++;
try {
    const deltaX = 0;
    const deltaY = 1000;  // Try to move far down (off canvas)

    const result = drawer.moveAnnotation(testAnnotation, deltaX, deltaY, mockCanvas);

    const maxY = 1 - testAnnotation.height; // Bottom edge constraint
    assertNear(result.y, maxY, 0.01, 'Y should be constrained to bottom edge');
    console.log('✅ Test 14 passed: Bottom boundary constraint works');
    passed++;
} catch (e) {
    console.error('❌ Test 14 failed:', e.message);
}

// ========== ANNOTATION SELECTION TESTS ==========

console.log('\n🧪 Testing Phase 4: Annotation Selection\n');

const testAnnotations = [
    { id: 1, x: 0.1, y: 0.1, width: 0.2, height: 0.15 },
    { id: 2, x: 0.5, y: 0.5, width: 0.2, height: 0.15 },
    { id: 3, x: 0.7, y: 0.2, width: 0.15, height: 0.2 }
];

// Test 15: getClickedAnnotation - click inside annotation
total++;
try {
    const clickX = (0.1 + 0.1) * mockCanvas.width; // Center of annotation 1
    const clickY = (0.1 + 0.075) * mockCanvas.height;

    const result = drawer.getClickedAnnotation(clickX, clickY, testAnnotations, mockCanvas);
    assertEqual(result?.id, 1, 'Should return annotation 1');
    console.log('✅ Test 15 passed: Click inside annotation detected');
    passed++;
} catch (e) {
    console.error('❌ Test 15 failed:', e.message);
}

// Test 16: getClickedAnnotation - click outside all annotations
total++;
try {
    const clickX = 0.01 * mockCanvas.width; // Far left, outside all
    const clickY = 0.01 * mockCanvas.height;

    const result = drawer.getClickedAnnotation(clickX, clickY, testAnnotations, mockCanvas);
    assertEqual(result, null, 'Should return null for click outside annotations');
    console.log('✅ Test 16 passed: Click outside returns null');
    passed++;
} catch (e) {
    console.error('❌ Test 16 failed:', e.message);
}

// Test 17: getClickedAnnotation - overlapping annotations (should return topmost)
total++;
try {
    // Add overlapping annotation
    const overlapping = [
        { id: 1, x: 0.2, y: 0.2, width: 0.3, height: 0.3 },
        { id: 2, x: 0.25, y: 0.25, width: 0.2, height: 0.2 } // On top
    ];

    const clickX = 0.3 * mockCanvas.width; // In overlap area
    const clickY = 0.3 * mockCanvas.height;

    const result = drawer.getClickedAnnotation(clickX, clickY, overlapping, mockCanvas);
    assertEqual(result?.id, 2, 'Should return topmost annotation (last in array)');
    console.log('✅ Test 17 passed: Overlapping annotations return topmost');
    passed++;
} catch (e) {
    console.error('❌ Test 17 failed:', e.message);
}

// Test 18: getClickedAnnotation - edge click
total++;
try {
    const clickX = testAnnotations[0].x * mockCanvas.width; // Left edge of annotation 1
    const clickY = testAnnotations[0].y * mockCanvas.height; // Top edge

    const result = drawer.getClickedAnnotation(clickX, clickY, testAnnotations, mockCanvas);
    assertEqual(result?.id, 1, 'Should detect click on edge');
    console.log('✅ Test 18 passed: Edge click detected');
    passed++;
} catch (e) {
    console.error('❌ Test 18 failed:', e.message);
}

// ========== SUMMARY ==========

console.log(`\n${'='.repeat(50)}`);
console.log(`Phase 4 Unit Test Results: ${passed}/${total} tests passed`);
console.log(`${'='.repeat(50)}\n`);

if (passed === total) {
    console.log('🎉 All Phase 4 unit tests passed!\n');
    process.exit(0);
} else {
    console.log(`❌ ${total - passed} test(s) failed\n`);
    process.exit(1);
}
