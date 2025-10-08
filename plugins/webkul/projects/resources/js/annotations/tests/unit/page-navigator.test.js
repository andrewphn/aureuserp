/**
 * Unit Tests for Page Navigator Module
 * Run with: node page-navigator.test.js
 */

import { createPageNavigator } from '../../page-navigator.js';

console.log('🧪 Testing Page Navigator Module\n');

const navigator = createPageNavigator();
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

// Mock PDF document
function createMockPdfDocument(totalPages = 10) {
    return {
        getPage: async (num) => {
            if (num < 1 || num > totalPages) {
                throw new Error('Invalid page number');
            }
            return { pageNumber: num, data: `Page ${num}` };
        }
    };
}

// Test goToPage
test('goToPage returns valid page within range', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToPage(mockDoc, 5, 10);
    assert(result !== null, 'Should return result object');
    assert(result.pageNum === 5, 'Should return correct page number');
    assert(result.pdfPage.pageNumber === 5, 'Should return correct page object');
});

test('goToPage returns null for page below range', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToPage(mockDoc, 0, 10);
    assert(result === null, 'Should return null for page 0');
});

test('goToPage returns null for page above range', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToPage(mockDoc, 11, 10);
    assert(result === null, 'Should return null for page 11');
});

test('goToPage returns null for null document', async () => {
    const result = await navigator.goToPage(null, 5, 10);
    assert(result === null, 'Should return null for null document');
});

// Test goToFirstPage
test('goToFirstPage navigates to page 1', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToFirstPage(mockDoc, 10);
    assert(result !== null, 'Should return result object');
    assert(result.pageNum === 1, 'Should navigate to page 1');
});

// Test goToLastPage
test('goToLastPage navigates to last page', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToLastPage(mockDoc, 10);
    assert(result !== null, 'Should return result object');
    assert(result.pageNum === 10, 'Should navigate to page 10');
});

// Test goToNextPage
test('goToNextPage navigates forward', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToNextPage(mockDoc, 5, 10);
    assert(result !== null, 'Should return result object');
    assert(result.pageNum === 6, 'Should navigate to page 6');
});

test('goToNextPage returns null at last page', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToNextPage(mockDoc, 10, 10);
    assert(result === null, 'Should return null at last page');
});

// Test goToPreviousPage
test('goToPreviousPage navigates backward', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToPreviousPage(mockDoc, 5, 10);
    assert(result !== null, 'Should return result object');
    assert(result.pageNum === 4, 'Should navigate to page 4');
});

test('goToPreviousPage returns null at first page', async () => {
    const mockDoc = createMockPdfDocument(10);
    const result = await navigator.goToPreviousPage(mockDoc, 1, 10);
    assert(result === null, 'Should return null at first page');
});

// Test isValidPageNumber
test('isValidPageNumber validates correct page', () => {
    const result = navigator.isValidPageNumber(5, 10);
    assert(result === true, 'Page 5 of 10 should be valid');
});

test('isValidPageNumber rejects page below range', () => {
    const result = navigator.isValidPageNumber(0, 10);
    assert(result === false, 'Page 0 should be invalid');
});

test('isValidPageNumber rejects page above range', () => {
    const result = navigator.isValidPageNumber(11, 10);
    assert(result === false, 'Page 11 of 10 should be invalid');
});

test('isValidPageNumber rejects non-integer', () => {
    const result = navigator.isValidPageNumber(5.5, 10);
    assert(result === false, 'Page 5.5 should be invalid');
});

// Test sanitizePageInput
test('sanitizePageInput returns valid number as-is', () => {
    const result = navigator.sanitizePageInput(5, 10);
    assert(result === 5, 'Valid number should be returned as-is');
});

test('sanitizePageInput clamps to minimum', () => {
    const result = navigator.sanitizePageInput(0, 10);
    assert(result === 1, 'Should clamp to minimum of 1');
});

test('sanitizePageInput clamps to maximum', () => {
    const result = navigator.sanitizePageInput(15, 10);
    assert(result === 10, 'Should clamp to maximum of 10');
});

test('sanitizePageInput handles string input', () => {
    const result = navigator.sanitizePageInput('5', 10);
    assert(result === 5, 'Should parse string "5" to number 5');
});

test('sanitizePageInput handles invalid input', () => {
    const result = navigator.sanitizePageInput('abc', 10);
    assert(result === 1, 'Should return 1 for invalid input');
});

test('sanitizePageInput handles negative numbers', () => {
    const result = navigator.sanitizePageInput(-5, 10);
    assert(result === 1, 'Should return 1 for negative numbers');
});

console.log(`\n📊 Results: ${passedTests}/${totalTests} tests passed`);

if (passedTests === totalTests) {
    console.log('✅ All page navigator tests passed!');
    process.exit(0);
} else {
    console.log(`❌ ${totalTests - passedTests} test(s) failed`);
    process.exit(1);
}
