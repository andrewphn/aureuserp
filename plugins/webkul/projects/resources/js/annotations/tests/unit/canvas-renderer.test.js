/**
 * Unit Tests for Canvas Renderer Module
 * Run with: node canvas-renderer.test.js
 */

import { createCanvasRenderer } from '../../canvas-renderer.js';

console.log('🧪 Testing Canvas Renderer Module\n');

const renderer = createCanvasRenderer();
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

// Test zoom functions
test('zoomIn increases zoom level correctly', () => {
    const result = renderer.zoomIn(1.0);
    assert(result === 1.25, `Expected 1.25, got ${result}`);
});

test('zoomIn respects maximum zoom', () => {
    const result = renderer.zoomIn(2.8);
    assert(result === 3.0, `Expected 3.0, got ${result}`);
});

test('zoomOut decreases zoom level correctly', () => {
    const result = renderer.zoomOut(1.0);
    assert(result === 0.75, `Expected 0.75, got ${result}`);
});

test('zoomOut respects minimum zoom', () => {
    const result = renderer.zoomOut(0.6);
    assert(result === 0.5, `Expected 0.5, got ${result}`);
});

test('resetZoom returns 1.0', () => {
    const result = renderer.resetZoom();
    assert(result === 1.0, `Expected 1.0, got ${result}`);
});

// Test rotation functions
test('rotateClockwise rotates 90 degrees', () => {
    const result = renderer.rotateClockwise(0);
    assert(result === 90, `Expected 90, got ${result}`);
});

test('rotateClockwise wraps at 360 degrees', () => {
    const result = renderer.rotateClockwise(270);
    assert(result === 0, `Expected 0, got ${result}`);
});

test('rotateCounterClockwise rotates -90 degrees', () => {
    const result = renderer.rotateCounterClockwise(90);
    assert(result === 0, `Expected 0, got ${result}`);
});

test('rotateCounterClockwise wraps correctly', () => {
    const result = renderer.rotateCounterClockwise(0);
    assert(result === 270, `Expected 270, got ${result}`);
});

// Test view management
test('resetView returns default state', () => {
    const result = renderer.resetView();
    assert(result.zoomLevel === 1.0, 'Zoom level should be 1.0');
    assert(result.rotation === 0, 'Rotation should be 0');
});

test('saveView stores current state', () => {
    const result = renderer.saveView(1.5, 90, 3);
    assert(result.zoomLevel === 1.5, 'Should save zoom level');
    assert(result.rotation === 90, 'Should save rotation');
    assert(result.pageNum === 3, 'Should save page number');
});

// Test base scale calculation
test('calculateBaseScale computes correct scale', () => {
    const mockViewport = { width: 800, height: 600 };
    const result = renderer.calculateBaseScale(mockViewport, 384, 100);
    const expected = (window.innerWidth - 384 - 100) / 800;
    assert(Math.abs(result - expected) < 0.01, `Expected ${expected}, got ${result}`);
});

// Test fit calculations (mock container)
test('calculateFitToPage returns valid zoom level', () => {
    const mockPage = {
        getViewport: () => ({ width: 800, height: 600 })
    };
    const mockContainer = {
        clientWidth: 1000,
        clientHeight: 800
    };
    const result = renderer.calculateFitToPage(mockPage, mockContainer, 1.0, 0);
    assert(result > 0 && result < 10, 'Zoom level should be reasonable');
});

test('calculateFitToWidth returns valid zoom level', () => {
    const mockPage = {
        getViewport: () => ({ width: 800, height: 600 })
    };
    const mockContainer = {
        clientWidth: 1000
    };
    const result = renderer.calculateFitToWidth(mockPage, mockContainer, 1.0, 0);
    assert(result > 0 && result < 10, 'Zoom level should be reasonable');
});

test('calculateFitToHeight returns valid zoom level', () => {
    const mockPage = {
        getViewport: () => ({ width: 800, height: 600 })
    };
    const mockContainer = {
        clientHeight: 800
    };
    const result = renderer.calculateFitToHeight(mockPage, mockContainer, 1.0, 0);
    assert(result > 0 && result < 10, 'Zoom level should be reasonable');
});

console.log(`\n📊 Results: ${passedTests}/${totalTests} tests passed`);

if (passedTests === totalTests) {
    console.log('✅ All canvas renderer tests passed!');
    process.exit(0);
} else {
    console.log(`❌ ${totalTests - passedTests} test(s) failed`);
    process.exit(1);
}
