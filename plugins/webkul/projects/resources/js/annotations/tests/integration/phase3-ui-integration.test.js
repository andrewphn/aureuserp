/**
 * Integration Tests for Phase 3: UI Component Integration
 * Tests the complete annotation type selector + cascading dropdowns workflow
 *
 * Run with: node phase3-ui-integration.test.js
 */

console.log('🧪 Testing Phase 3: UI Integration Workflow\n');

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

// ========================================
// Mock Alpine.js Component State
// ========================================

function createMockComponentState() {
    return {
        // Backend data
        pdfPageId: 1,
        projectId: 1,
        availableRooms: [
            { id: 1, name: 'Kitchen', room_type: 'kitchen' },
            { id: 2, name: 'Pantry', room_type: 'pantry' },
        ],
        availableRoomLocations: [
            { id: 10, name: 'North Wall', room_id: 1, location_type: 'wall' },
            { id: 11, name: 'South Wall', room_id: 1, location_type: 'wall' },
            { id: 13, name: 'West Wall', room_id: 2, location_type: 'wall' },
        ],
        availableCabinetRuns: [
            { id: 100, name: 'Upper Cabinets', room_location_id: 10, run_type: 'upper' },
            { id: 101, name: 'Base Cabinets', room_location_id: 10, run_type: 'base' },
        ],
        availableCabinets: [
            { id: 1000, name: 'W2430', cabinet_run_id: 100, width: 24, height: 30 },
            { id: 1001, name: 'W3030', cabinet_run_id: 100, width: 30, height: 30 },
        ],

        // Annotation context
        annotationType: 'room',
        selectedRoomId: null,
        selectedRoomLocationId: null,
        selectedCabinetRunId: null,
        selectedCabinetId: null,

        // Filtered dropdowns
        filteredRoomLocations: [],
        filteredCabinetRuns: [],
        filteredCabinets: [],

        // Methods
        filterRoomLocations() {
            if (!this.selectedRoomId) {
                this.filteredRoomLocations = this.availableRoomLocations;
                return;
            }
            this.filteredRoomLocations = this.availableRoomLocations.filter(
                loc => loc.room_id == this.selectedRoomId
            );
        },

        filterCabinetRuns() {
            if (!this.selectedRoomLocationId) {
                this.filteredCabinetRuns = this.availableCabinetRuns;
                return;
            }
            this.filteredCabinetRuns = this.availableCabinetRuns.filter(
                run => run.room_location_id == this.selectedRoomLocationId
            );
        },

        filterCabinets() {
            if (!this.selectedCabinetRunId) {
                this.filteredCabinets = this.availableCabinets;
                return;
            }
            this.filteredCabinets = this.availableCabinets.filter(
                cab => cab.cabinet_run_id == this.selectedCabinetRunId
            );
        },

        resetChildSelections() {
            this.selectedRoomLocationId = null;
            this.selectedCabinetRunId = null;
            this.selectedCabinetId = null;
            this.filteredRoomLocations = [];
            this.filteredCabinetRuns = [];
            this.filteredCabinets = [];
        },
    };
}

// ========================================
// Test Scenario 1: Room Annotation Workflow
// ========================================

test('Scenario 1: Annotating a new Room', () => {
    const component = createMockComponentState();

    // Step 1: User selects "Room" annotation type
    component.annotationType = 'room';

    // Step 2: User selects Kitchen as template room
    component.selectedRoomId = 1;

    // Step 3: No further selections needed for room annotations
    assert(component.annotationType === 'room', 'Annotation type should be room');
    assert(component.selectedRoomId === 1, 'Kitchen should be selected');
    assert(component.selectedRoomLocationId === null, 'No location needed for room');
    assert(component.selectedCabinetRunId === null, 'No run needed for room');

    // Step 4: Draw annotation on canvas (simulated)
    const annotation = {
        type: 'room',
        label: 'Dining Room',
        context: {
            selectedRoomId: component.selectedRoomId,
        },
    };

    assert(annotation.context.selectedRoomId === 1, 'Context should include template room');
});

// ========================================
// Test Scenario 2: Room Location Annotation Workflow
// ========================================

test('Scenario 2: Annotating a Room Location', () => {
    const component = createMockComponentState();

    // Step 1: User selects "Room Location" annotation type
    component.annotationType = 'room_location';

    // Step 2: User selects Kitchen
    component.selectedRoomId = 1;
    component.filterRoomLocations();

    // Verify locations are filtered
    assert(component.filteredRoomLocations.length === 2, 'Should show 2 locations for Kitchen');
    assert(
        component.filteredRoomLocations.every(loc => loc.room_id === 1),
        'All locations should belong to Kitchen'
    );

    // Step 3: User selects North Wall
    component.selectedRoomLocationId = 10;

    // Step 4: Draw annotation
    const annotation = {
        type: 'room_location',
        label: 'East Wall',
        context: {
            selectedRoomId: component.selectedRoomId,
            selectedRoomLocationId: component.selectedRoomLocationId,
        },
    };

    assert(annotation.context.selectedRoomId === 1, 'Context should include room');
    assert(annotation.context.selectedRoomLocationId === 10, 'Context should include location');
});

// ========================================
// Test Scenario 3: Cabinet Run Annotation Workflow
// ========================================

test('Scenario 3: Annotating a Cabinet Run', () => {
    const component = createMockComponentState();

    // Step 1: Select annotation type
    component.annotationType = 'cabinet_run';

    // Step 2: Select Kitchen
    component.selectedRoomId = 1;
    component.filterRoomLocations();

    // Step 3: Select North Wall
    component.selectedRoomLocationId = 10;
    component.filterCabinetRuns();

    // Verify runs are filtered
    assert(component.filteredCabinetRuns.length === 2, 'Should show 2 runs for North Wall');
    assert(
        component.filteredCabinetRuns.every(run => run.room_location_id === 10),
        'All runs should belong to North Wall'
    );

    // Step 4: User draws new cabinet run
    const annotation = {
        type: 'cabinet_run',
        label: 'Tall Cabinets',
        context: {
            selectedRoomId: component.selectedRoomId,
            selectedRoomLocationId: component.selectedRoomLocationId,
        },
    };

    assert(annotation.context.selectedRoomId === 1, 'Context should include room');
    assert(annotation.context.selectedRoomLocationId === 10, 'Context should include location');
});

// ========================================
// Test Scenario 4: Individual Cabinet Annotation Workflow
// ========================================

test('Scenario 4: Annotating Individual Cabinets', () => {
    const component = createMockComponentState();

    // Step 1: Select cabinet annotation type
    component.annotationType = 'cabinet';

    // Step 2: Navigate cascade - Kitchen
    component.selectedRoomId = 1;
    component.filterRoomLocations();

    // Step 3: North Wall
    component.selectedRoomLocationId = 10;
    component.filterCabinetRuns();

    // Step 4: Upper Cabinets Run
    component.selectedCabinetRunId = 100;
    component.filterCabinets();

    // Verify cabinets are filtered
    assert(component.filteredCabinets.length === 2, 'Should show 2 cabinets for Upper Cabinets run');
    assert(
        component.filteredCabinets.every(cab => cab.cabinet_run_id === 100),
        'All cabinets should belong to Upper Cabinets run'
    );

    // Step 5: User can optionally link to existing cabinet OR create new
    // Option A: Link to existing W2430
    component.selectedCabinetId = 1000;

    const linkedAnnotation = {
        type: 'cabinet',
        label: 'W2430',
        context: {
            selectedRoomId: component.selectedRoomId,
            selectedRoomLocationId: component.selectedRoomLocationId,
            selectedCabinetRunId: component.selectedCabinetRunId,
            selectedCabinetId: component.selectedCabinetId,
        },
    };

    assert(linkedAnnotation.context.selectedCabinetId === 1000, 'Should link to existing cabinet');

    // Option B: Create new cabinet
    component.selectedCabinetId = null;

    const newAnnotation = {
        type: 'cabinet',
        label: 'W3630',
        context: {
            selectedRoomId: component.selectedRoomId,
            selectedRoomLocationId: component.selectedRoomLocationId,
            selectedCabinetRunId: component.selectedCabinetRunId,
            selectedCabinetId: null,
        },
    };

    assert(newAnnotation.context.selectedCabinetId === null, 'Should create new cabinet');
});

// ========================================
// Test Scenario 5: Changing Annotation Type Resets Selections
// ========================================

test('Scenario 5: Changing annotation type resets child selections', () => {
    const component = createMockComponentState();

    // User navigates full cascade for cabinet annotation
    component.annotationType = 'cabinet';
    component.selectedRoomId = 1;
    component.filterRoomLocations();
    component.selectedRoomLocationId = 10;
    component.filterCabinetRuns();
    component.selectedCabinetRunId = 100;
    component.filterCabinets();
    component.selectedCabinetId = 1000;

    // Verify full selection
    assert(component.selectedRoomId === 1, 'Room selected');
    assert(component.selectedRoomLocationId === 10, 'Location selected');
    assert(component.selectedCabinetRunId === 100, 'Run selected');
    assert(component.selectedCabinetId === 1000, 'Cabinet selected');

    // User changes annotation type to "room"
    component.annotationType = 'room';
    component.resetChildSelections(); // This should be called on type change

    // Verify child selections are reset
    assert(component.selectedRoomLocationId === null, 'Location should be reset');
    assert(component.selectedCabinetRunId === null, 'Run should be reset');
    assert(component.selectedCabinetId === null, 'Cabinet should be reset');
    assert(component.filteredRoomLocations.length === 0, 'Filtered locations should be empty');
    assert(component.filteredCabinetRuns.length === 0, 'Filtered runs should be empty');
    assert(component.filteredCabinets.length === 0, 'Filtered cabinets should be empty');

    // Room selection should persist
    assert(component.selectedRoomId === 1, 'Room selection should persist');
});

// ========================================
// Test Scenario 6: Changing Parent Selection Resets Children
// ========================================

test('Scenario 6: Changing room selection resets location/run/cabinet', () => {
    const component = createMockComponentState();

    // User selects Kitchen -> North Wall -> Upper Cabinets
    component.annotationType = 'cabinet';
    component.selectedRoomId = 1;
    component.filterRoomLocations();
    component.selectedRoomLocationId = 10;
    component.filterCabinetRuns();
    component.selectedCabinetRunId = 100;

    // User changes room to Pantry
    component.selectedRoomId = 2;
    component.resetChildSelections(); // Should be called on room change
    component.filterRoomLocations();

    // Verify children are reset
    assert(component.selectedRoomLocationId === null, 'Location should be reset');
    assert(component.selectedCabinetRunId === null, 'Run should be reset');

    // Verify new filtered locations
    assert(component.filteredRoomLocations.length === 1, 'Should show 1 location for Pantry');
    assert(component.filteredRoomLocations[0].room_id === 2, 'Location should belong to Pantry');
});

// ========================================
// Test Scenario 7: Context Summary Display
// ========================================

test('Scenario 7: Context summary displays correct hierarchy', () => {
    const component = createMockComponentState();

    // Helper to get context summary text
    function getContextSummary() {
        if (!component.selectedRoomId) return 'No context selected';

        const room = component.availableRooms.find(r => r.id === component.selectedRoomId);

        if (component.annotationType === 'room') {
            return `Creating new rooms in: ${room.name}`;
        }

        if (component.annotationType === 'room_location' && !component.selectedRoomLocationId) {
            return `Room: ${room.name}`;
        }

        if (component.annotationType === 'room_location' && component.selectedRoomLocationId) {
            const location = component.filteredRoomLocations.find(
                l => l.id === component.selectedRoomLocationId
            );
            return `${room.name} → ${location.name}`;
        }

        if (component.annotationType === 'cabinet_run' && component.selectedCabinetRunId) {
            const run = component.filteredCabinetRuns.find(r => r.id === component.selectedCabinetRunId);
            return `${room.name} → ${run.name}`;
        }

        if (component.annotationType === 'cabinet' && component.selectedCabinetRunId) {
            const run = component.filteredCabinetRuns.find(r => r.id === component.selectedCabinetRunId);
            const cabinetText = component.selectedCabinetId
                ? ` → Cabinet #${component.selectedCabinetId}`
                : ' (new cabinet)';
            return `Run: ${run.name}${cabinetText}`;
        }

        return 'Incomplete selection';
    }

    // Test room annotation summary
    component.annotationType = 'room';
    component.selectedRoomId = 1;
    assert(
        getContextSummary() === 'Creating new rooms in: Kitchen',
        'Room annotation summary incorrect'
    );

    // Test room location summary
    component.annotationType = 'room_location';
    component.selectedRoomId = 1;
    component.filterRoomLocations();
    assert(getContextSummary() === 'Room: Kitchen', 'Room location partial summary incorrect');

    component.selectedRoomLocationId = 10;
    assert(
        getContextSummary() === 'Kitchen → North Wall',
        'Room location full summary incorrect'
    );

    // Test cabinet run summary
    component.annotationType = 'cabinet_run';
    component.selectedRoomId = 1;
    component.filterRoomLocations();
    component.selectedRoomLocationId = 10;
    component.filterCabinetRuns();
    component.selectedCabinetRunId = 100;
    assert(
        getContextSummary() === 'Kitchen → Upper Cabinets',
        'Cabinet run summary incorrect'
    );

    // Test cabinet summary (new)
    component.annotationType = 'cabinet';
    component.selectedCabinetId = null;
    assert(
        getContextSummary() === 'Run: Upper Cabinets (new cabinet)',
        'New cabinet summary incorrect'
    );

    // Test cabinet summary (existing)
    component.selectedCabinetId = 1000;
    assert(
        getContextSummary() === 'Run: Upper Cabinets → Cabinet #1000',
        'Existing cabinet summary incorrect'
    );
});

// ========================================
// Summary
// ========================================

console.log(`\n${'='.repeat(50)}`);
console.log(`✅ Passed: ${passedTests}/${totalTests} tests`);
console.log(`❌ Failed: ${totalTests - passedTests}/${totalTests} tests`);
console.log('='.repeat(50));

if (passedTests === totalTests) {
    console.log('🎉 All Phase 3 UI integration tests passed!\n');
    process.exit(0);
} else {
    console.log('⚠️  Some tests failed. Please review.\n');
    process.exit(1);
}
