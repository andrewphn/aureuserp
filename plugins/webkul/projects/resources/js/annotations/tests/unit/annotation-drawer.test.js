/**
 * Unit Tests for Annotation Drawer Module
 * Run with: node annotation-drawer.test.js
 */

import { createAnnotationDrawer } from '../../annotation-drawer.js';

console.log('🧪 Testing Annotation Drawer Module\n');

const drawer = createAnnotationDrawer();
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

// Test color selection
test('getAnnotationColor returns room color when provided', () => {
    const roomColors = { kitchen: '#3B82F6', pantry: '#10B981' };
    const result = drawer.getAnnotationColor('room', 'kitchen', roomColors);
    assert(result === '#3B82F6', `Expected #3B82F6, got ${result}`);
});

test('getAnnotationColor returns default for unknown room type', () => {
    const roomColors = { kitchen: '#3B82F6' };
    const result = drawer.getAnnotationColor('room', 'unknown', roomColors);
    assert(result === '#3B82F6', `Expected default #3B82F6, got ${result}`);
});

test('getAnnotationColor returns correct color for room_location', () => {
    const result = drawer.getAnnotationColor('room_location', null, {});
    assert(result === '#10B981', `Expected #10B981, got ${result}`);
});

test('getAnnotationColor returns correct color for cabinet_run', () => {
    const result = drawer.getAnnotationColor('cabinet_run', null, {});
    assert(result === '#F59E0B', `Expected #F59E0B, got ${result}`);
});

test('getAnnotationColor returns correct color for cabinet', () => {
    const result = drawer.getAnnotationColor('cabinet', null, {});
    assert(result === '#EF4444', `Expected #EF4444, got ${result}`);
});

test('getAnnotationColor returns correct color for dimension', () => {
    const result = drawer.getAnnotationColor('dimension', null, {});
    assert(result === '#8B5CF6', `Expected #8B5CF6, got ${result}`);
});

// Test label generation
test('generateLabel creates correct room label with code', () => {
    const roomCodes = { kitchen: 'K' };
    const result = drawer.generateLabel('room', 'kitchen', 'TFW-0001', roomCodes, 0);
    assert(result === 'TFW-0001-K', `Expected TFW-0001-K, got ${result}`);
});

test('generateLabel creates room code without project number', () => {
    const roomCodes = { kitchen: 'K' };
    const result = drawer.generateLabel('room', 'kitchen', null, roomCodes, 0);
    assert(result === 'K', `Expected K, got ${result}`);
});

test('generateLabel creates fallback label when no room code', () => {
    const result = drawer.generateLabel('room', null, 'TFW-0001', {}, 5);
    assert(result === 'TFW-0001-6', `Expected TFW-0001-6, got ${result}`);
});

test('generateLabel creates simple fallback without project number', () => {
    const result = drawer.generateLabel('room', null, null, {}, 5);
    assert(result === 'Label 6', `Expected Label 6, got ${result}`);
});

// Test drawing state
test('startDrawing returns null for non-rectangle tool', () => {
    const mockCanvas = { getBoundingClientRect: () => ({ left: 0, top: 0 }) };
    const mockEvent = { clientX: 100, clientY: 150 };
    const result = drawer.startDrawing(mockEvent, mockCanvas, 'select');
    assert(result === null, 'Should return null for select tool');
});

test('startDrawing returns valid state for rectangle tool', () => {
    const mockCanvas = { getBoundingClientRect: () => ({ left: 10, top: 20 }) };
    const mockEvent = { clientX: 100, clientY: 150 };
    const result = drawer.startDrawing(mockEvent, mockCanvas, 'rectangle');
    assert(result !== null, 'Should return state object');
    assert(result.isDrawing === true, 'isDrawing should be true');
    assert(result.startX === 90, `Expected startX 90, got ${result.startX}`);
    assert(result.startY === 130, `Expected startY 130, got ${result.startY}`);
});

// Test stopDrawing
test('stopDrawing returns null for small rectangles', () => {
    const mockCanvas = {
        getBoundingClientRect: () => ({ left: 0, top: 0 }),
        width: 800,
        height: 600
    };
    const mockEvent = { clientX: 105, clientY: 103 };
    const drawState = { isDrawing: true, startX: 100, startY: 100 };
    const options = {
        annotationType: 'room',
        roomType: 'kitchen',
        projectNumber: 'TFW-0001',
        roomCodes: { kitchen: 'K' },
        roomColors: { kitchen: '#3B82F6' }
    };
    const result = drawer.stopDrawing(mockEvent, mockCanvas, drawState, options, []);
    assert(result === null, 'Should return null for rectangles smaller than 10px');
});

test('stopDrawing creates valid annotation for large rectangles', () => {
    const mockCanvas = {
        getBoundingClientRect: () => ({ left: 0, top: 0 }),
        width: 800,
        height: 600
    };
    const mockEvent = { clientX: 200, clientY: 200 };
    const drawState = { isDrawing: true, startX: 100, startY: 100 };
    const options = {
        annotationType: 'room',
        roomType: 'kitchen',
        projectNumber: 'TFW-0001',
        roomCodes: { kitchen: 'K' },
        roomColors: { kitchen: '#3B82F6' }
    };
    const result = drawer.stopDrawing(mockEvent, mockCanvas, drawState, options, []);

    assert(result !== null, 'Should return annotation object');
    assert(result.annotation_type === 'room', 'Should have correct type');
    assert(result.text === 'TFW-0001-K', 'Should have correct label');
    assert(result.color === '#3B82F6', 'Should have correct color');
    assert(result.x >= 0 && result.x <= 1, 'X should be normalized');
    assert(result.y >= 0 && result.y <= 1, 'Y should be normalized');
    assert(result.width > 0 && result.width <= 1, 'Width should be normalized');
    assert(result.height > 0 && result.height <= 1, 'Height should be normalized');
});

// Test cursor setting
test('setCursor sets crosshair for rectangle tool', () => {
    const mockCanvas = { style: {} };
    drawer.setCursor(mockCanvas, 'rectangle');
    assert(mockCanvas.style.cursor === 'crosshair', 'Should set crosshair cursor');
});

test('setCursor sets default for select tool', () => {
    const mockCanvas = { style: {} };
    drawer.setCursor(mockCanvas, 'select');
    assert(mockCanvas.style.cursor === 'default', 'Should set default cursor');
});

console.log(`\n📊 Results: ${passedTests}/${totalTests} tests passed`);

if (passedTests === totalTests) {
    console.log('✅ All annotation drawer tests passed!');
    process.exit(0);
} else {
    console.log(`❌ ${totalTests - passedTests} test(s) failed`);
    process.exit(1);
}
