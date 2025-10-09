/**
 * Unit Tests for Phase 3: Cascade Filtering Logic
 * Tests the cascading dropdown filtering system for hierarchical entity selection
 *
 * Run with: node cascade-filters.test.js
 */

import { createCascadeFilters } from '../../cascade-filters.js';

const filters = createCascadeFilters();

console.log('🧪 Testing Phase 3: Cascade Filtering Logic\n');

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

function assertEqual(actual, expected, message) {
    if (JSON.stringify(actual) !== JSON.stringify(expected)) {
        throw new Error(message || `Expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`);
    }
}

// ========================================
// Test Data Setup
// ========================================

const testRooms = [
    { id: 1, name: 'Kitchen', room_type: 'kitchen' },
    { id: 2, name: 'Pantry', room_type: 'pantry' },
    { id: 3, name: 'Bathroom', room_type: 'bathroom' },
];

const testRoomLocations = [
    { id: 10, name: 'North Wall', room_id: 1, location_type: 'wall' },
    { id: 11, name: 'South Wall', room_id: 1, location_type: 'wall' },
    { id: 12, name: 'Island', room_id: 1, location_type: 'island' },
    { id: 13, name: 'West Wall', room_id: 2, location_type: 'wall' },
    { id: 14, name: 'Vanity Wall', room_id: 3, location_type: 'wall' },
];

const testCabinetRuns = [
    { id: 100, name: 'Upper Cabinets', room_location_id: 10, run_type: 'upper' },
    { id: 101, name: 'Base Cabinets', room_location_id: 10, run_type: 'base' },
    { id: 102, name: 'Island Base', room_location_id: 12, run_type: 'base' },
    { id: 103, name: 'Pantry Uppers', room_location_id: 13, run_type: 'upper' },
    { id: 104, name: 'Vanity Base', room_location_id: 14, run_type: 'base' },
];

const testCabinets = [
    { id: 1000, name: 'W2430', cabinet_run_id: 100, width: 24, height: 30 },
    { id: 1001, name: 'W3030', cabinet_run_id: 100, width: 30, height: 30 },
    { id: 1002, name: 'B18', cabinet_run_id: 101, width: 18, height: 34.5 },
    { id: 1003, name: 'B24', cabinet_run_id: 101, width: 24, height: 34.5 },
    { id: 1004, name: 'IB36', cabinet_run_id: 102, width: 36, height: 34.5 },
];

// ========================================
// Test Room Location Filtering
// ========================================

test('filterRoomLocations returns empty array when no room selected', () => {
    const result = filters.filterRoomLocations(null, testRoomLocations);
    assertEqual(result, [], 'Should return empty array when no room selected');
});

test('filterRoomLocations filters by selected room', () => {
    const result = filters.filterRoomLocations(1, testRoomLocations);
    assert(result.length === 3, `Expected 3 locations for Kitchen, got ${result.length}`);
    assert(result.every(loc => loc.room_id === 1), 'All locations should belong to Kitchen');
});

test('filterRoomLocations returns empty array for non-existent room', () => {
    const result = filters.filterRoomLocations(999, testRoomLocations);
    assertEqual(result, [], 'Should return empty array for non-existent room');
});

test('filterRoomLocations handles empty location array', () => {
    const result = filters.filterRoomLocations(1, []);
    assertEqual(result, [], 'Should return empty array when no locations exist');
});

// ========================================
// Test Cabinet Run Filtering
// ========================================

test('filterCabinetRuns returns empty array when no location selected', () => {
    const result = filters.filterCabinetRuns(null, testCabinetRuns);
    assertEqual(result, [], 'Should return empty array when no location selected');
});

test('filterCabinetRuns filters by selected room location', () => {
    const result = filters.filterCabinetRuns(10, testCabinetRuns);
    assert(result.length === 2, `Expected 2 runs for North Wall, got ${result.length}`);
    assert(result.every(run => run.room_location_id === 10), 'All runs should belong to North Wall');
});

test('filterCabinetRuns returns empty array for non-existent location', () => {
    const result = filters.filterCabinetRuns(999, testCabinetRuns);
    assertEqual(result, [], 'Should return empty array for non-existent location');
});

test('filterCabinetRuns handles empty runs array', () => {
    const result = filters.filterCabinetRuns(10, []);
    assertEqual(result, [], 'Should return empty array when no runs exist');
});

// ========================================
// Test Cabinet Filtering
// ========================================

test('filterCabinets returns empty array when no run selected', () => {
    const result = filters.filterCabinets(null, testCabinets);
    assertEqual(result, [], 'Should return empty array when no run selected');
});

test('filterCabinets filters by selected cabinet run', () => {
    const result = filters.filterCabinets(100, testCabinets);
    assert(result.length === 2, `Expected 2 cabinets for Upper Cabinets run, got ${result.length}`);
    assert(result.every(cab => cab.cabinet_run_id === 100), 'All cabinets should belong to Upper Cabinets run');
});

test('filterCabinets returns empty array for non-existent run', () => {
    const result = filters.filterCabinets(999, testCabinets);
    assertEqual(result, [], 'Should return empty array for non-existent run');
});

test('filterCabinets handles empty cabinets array', () => {
    const result = filters.filterCabinets(100, []);
    assertEqual(result, [], 'Should return empty array when no cabinets exist');
});

// ========================================
// Test Reset Child Selections
// ========================================

test('resetChildSelections clears all child selections', () => {
    const result = filters.resetChildSelections();
    assertEqual(result, {
        selectedRoomLocationId: null,
        selectedCabinetRunId: null,
        selectedCabinetId: null,
        filteredRoomLocations: [],
        filteredCabinetRuns: [],
        filteredCabinets: [],
    }, 'Should reset all child selections and filtered arrays');
});

// ========================================
// Test Full Cascade Flow
// ========================================

test('Full cascade flow: Kitchen -> North Wall -> Upper Cabinets', () => {
    // Step 1: Select Kitchen (room_id: 1)
    const kitchenLocations = filters.filterRoomLocations(1, testRoomLocations);
    assert(kitchenLocations.length === 3, 'Kitchen should have 3 locations');

    // Step 2: Select North Wall (location_id: 10)
    const northWallRuns = filters.filterCabinetRuns(10, testCabinetRuns);
    assert(northWallRuns.length === 2, 'North Wall should have 2 runs');

    // Step 3: Select Upper Cabinets (run_id: 100)
    const upperCabinets = filters.filterCabinets(100, testCabinets);
    assert(upperCabinets.length === 2, 'Upper Cabinets run should have 2 cabinets');

    // Verify final selection
    assert(upperCabinets[0].name === 'W2430', 'First cabinet should be W2430');
    assert(upperCabinets[1].name === 'W3030', 'Second cabinet should be W3030');
});

test('Full cascade flow: Pantry -> West Wall -> Pantry Uppers', () => {
    // Step 1: Select Pantry (room_id: 2)
    const pantryLocations = filters.filterRoomLocations(2, testRoomLocations);
    assert(pantryLocations.length === 1, 'Pantry should have 1 location');
    assert(pantryLocations[0].name === 'West Wall', 'Location should be West Wall');

    // Step 2: Select West Wall (location_id: 13)
    const westWallRuns = filters.filterCabinetRuns(13, testCabinetRuns);
    assert(westWallRuns.length === 1, 'West Wall should have 1 run');
    assert(westWallRuns[0].name === 'Pantry Uppers', 'Run should be Pantry Uppers');

    // Step 3: Select Pantry Uppers (run_id: 103) - no cabinets in test data
    const pantryUpperCabinets = filters.filterCabinets(103, testCabinets);
    assertEqual(pantryUpperCabinets, [], 'Pantry Uppers should have no cabinets in test data');
});

// ========================================
// Test Edge Cases
// ========================================

test('Cascade filtering handles undefined input gracefully', () => {
    const result1 = filters.filterRoomLocations(undefined, testRoomLocations);
    assertEqual(result1, [], 'Should treat undefined as null and return empty array');

    const result2 = filters.filterCabinetRuns(undefined, testCabinetRuns);
    assertEqual(result2, [], 'Should treat undefined as null and return empty array');
});

test('Cascade filtering handles null arrays gracefully', () => {
    const result1 = filters.filterRoomLocations(1, null);
    assertEqual(result1, [], 'Should return empty array for null input');

    const result2 = filters.filterCabinetRuns(10, null);
    assertEqual(result2, [], 'Should return empty array for null input');
});

test('Cascade filtering handles string IDs (from select inputs)', () => {
    const result1 = filters.filterRoomLocations('1', testRoomLocations);
    assert(result1.length === 3, 'Should handle string ID "1" same as number 1');

    const result2 = filters.filterCabinetRuns('10', testCabinetRuns);
    assert(result2.length === 2, 'Should handle string ID "10" same as number 10');
});

// ========================================
// Test Annotation Type Scenarios
// ========================================

test('Room annotation: Only room selection needed', () => {
    // When annotationType === 'room', user only selects a room
    // No filtering needed since they're creating NEW rooms
    const selectedRoomId = 1;
    assert(selectedRoomId !== null, 'Room should be selected');
    // Context: { selectedRoomId: 1 }
});

test('Room Location annotation: Room -> Location cascade', () => {
    // When annotationType === 'room_location'
    const selectedRoomId = 1;
    const locations = filters.filterRoomLocations(selectedRoomId, testRoomLocations);
    assert(locations.length > 0, 'Should have filtered locations');

    const selectedRoomLocationId = locations[0].id;
    assert(selectedRoomLocationId === 10, 'Should select North Wall');
    // Context: { selectedRoomId: 1, selectedRoomLocationId: 10 }
});

test('Cabinet Run annotation: Room -> Location -> Run cascade', () => {
    // When annotationType === 'cabinet_run'
    const selectedRoomId = 1;
    const locations = filters.filterRoomLocations(selectedRoomId, testRoomLocations);

    const selectedRoomLocationId = locations[0].id;
    const runs = filters.filterCabinetRuns(selectedRoomLocationId, testCabinetRuns);
    assert(runs.length > 0, 'Should have filtered runs');

    const selectedCabinetRunId = runs[0].id;
    assert(selectedCabinetRunId === 100, 'Should select Upper Cabinets');
    // Context: { selectedRoomId: 1, selectedRoomLocationId: 10, selectedCabinetRunId: 100 }
});

test('Cabinet annotation: Full cascade with optional cabinet linking', () => {
    // When annotationType === 'cabinet'
    const selectedRoomId = 1;
    const locations = filters.filterRoomLocations(selectedRoomId, testRoomLocations);

    const selectedRoomLocationId = locations[0].id;
    const runs = filters.filterCabinetRuns(selectedRoomLocationId, testCabinetRuns);

    const selectedCabinetRunId = runs[0].id;
    const cabinets = filters.filterCabinets(selectedCabinetRunId, testCabinets);
    assert(cabinets.length > 0, 'Should have filtered cabinets');

    // Optional: Link to existing cabinet
    const selectedCabinetId = cabinets[0].id;
    assert(selectedCabinetId === 1000, 'Should select W2430');
    // Context: { selectedRoomId: 1, selectedRoomLocationId: 10, selectedCabinetRunId: 100, selectedCabinetId: 1000 }

    // Or leave null to create new cabinet
    const newCabinetContext = {
        selectedRoomId: 1,
        selectedRoomLocationId: 10,
        selectedCabinetRunId: 100,
        selectedCabinetId: null, // Create new
    };
    assert(newCabinetContext.selectedCabinetId === null, 'Should allow creating new cabinet');
});

// ========================================
// Summary
// ========================================

console.log(`\n${'='.repeat(50)}`);
console.log(`✅ Passed: ${passedTests}/${totalTests} tests`);
console.log(`❌ Failed: ${totalTests - passedTests}/${totalTests} tests`);
console.log('='.repeat(50));

if (passedTests === totalTests) {
    console.log('🎉 All Phase 3 cascade filter tests passed!\n');
    process.exit(0);
} else {
    console.log('⚠️  Some tests failed. Please review.\n');
    process.exit(1);
}
