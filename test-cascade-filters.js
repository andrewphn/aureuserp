/**
 * Test script for cascade-filters.js module
 * Run with: node test-cascade-filters.js
 */

// Import the module (Node.js ES modules - requires "type": "module" in package.json or .mjs extension)
import { createCascadeFilters } from './plugins/webkul/projects/resources/js/annotations/cascade-filters.js';

console.log('🧪 Testing Cascade Filters Module\n');

// Mock data
const mockRooms = [
    { id: 1, name: 'Kitchen', room_type: 'kitchen' },
    { id: 2, name: 'Master Bedroom', room_type: 'bedroom' }
];

const mockRoomLocations = [
    { id: 1, room_id: 1, name: 'North Wall', location_type: 'wall' },
    { id: 2, room_id: 1, name: 'Island', location_type: 'island' },
    { id: 3, room_id: 2, name: 'West Wall', location_type: 'wall' }
];

const mockCabinetRuns = [
    { id: 1, room_location_id: 1, name: 'Upper Run A', run_type: 'upper' },
    { id: 2, room_location_id: 1, name: 'Base Run A', run_type: 'base' },
    { id: 3, room_location_id: 2, name: 'Island Base', run_type: 'base' }
];

const mockCabinets = [
    { id: 1, cabinet_run_id: 1, cabinet_number: 'W2436' },
    { id: 2, cabinet_run_id: 1, cabinet_number: 'W3036' },
    { id: 3, cabinet_run_id: 2, cabinet_number: 'B18' }
];

// Create filter instance
const filters = createCascadeFilters();

// Test 1: Filter room locations by room
console.log('Test 1: Filter room locations by room ID 1 (Kitchen)');
const filteredLocations = filters.filterRoomLocations(1, mockRoomLocations);
console.log(`  Expected: 2 locations (North Wall, Island)`);
console.log(`  Got: ${filteredLocations.length} locations`);
console.log(`  ✅ Pass: ${filteredLocations.length === 2}\n`);

// Test 2: Filter cabinet runs by room location
console.log('Test 2: Filter cabinet runs by room location ID 1 (North Wall)');
const filteredRuns = filters.filterCabinetRuns(1, mockCabinetRuns);
console.log(`  Expected: 2 runs (Upper Run A, Base Run A)`);
console.log(`  Got: ${filteredRuns.length} runs`);
console.log(`  ✅ Pass: ${filteredRuns.length === 2}\n`);

// Test 3: Filter cabinets by cabinet run
console.log('Test 3: Filter cabinets by cabinet run ID 1 (Upper Run A)');
const filteredCabinets = filters.filterCabinets(1, mockCabinets);
console.log(`  Expected: 2 cabinets (W2436, W3036)`);
console.log(`  Got: ${filteredCabinets.length} cabinets`);
console.log(`  ✅ Pass: ${filteredCabinets.length === 2}\n`);

// Test 4: Reset child selections
console.log('Test 4: Reset child selections');
const resetState = filters.resetChildSelections();
const expectedKeys = ['selectedRoomLocationId', 'selectedCabinetRunId', 'selectedCabinetId',
                       'filteredRoomLocations', 'filteredCabinetRuns', 'filteredCabinets'];
const hasAllKeys = expectedKeys.every(key => key in resetState);
const allNull = resetState.selectedRoomLocationId === null &&
                resetState.selectedCabinetRunId === null &&
                resetState.selectedCabinetId === null;
const allEmpty = resetState.filteredRoomLocations.length === 0 &&
                 resetState.filteredCabinetRuns.length === 0 &&
                 resetState.filteredCabinets.length === 0;
console.log(`  Has all keys: ${hasAllKeys}`);
console.log(`  All selections null: ${allNull}`);
console.log(`  All arrays empty: ${allEmpty}`);
console.log(`  ✅ Pass: ${hasAllKeys && allNull && allEmpty}\n`);

// Test 5: Null handling
console.log('Test 5: Filter with null selected ID');
const nullFiltered = filters.filterRoomLocations(null, mockRoomLocations);
console.log(`  Expected: 0 locations`);
console.log(`  Got: ${nullFiltered.length} locations`);
console.log(`  ✅ Pass: ${nullFiltered.length === 0}\n`);

console.log('✅ All cascade filter tests completed!');
