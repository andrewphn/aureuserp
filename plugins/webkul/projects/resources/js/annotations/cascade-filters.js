/**
 * Cascade Filtering Logic for Hierarchical Dropdowns
 * Filters child entities based on parent selection
 */

export function createCascadeFilters() {
    return {
        /**
         * Filter room locations by selected room
         * @param {number|null} selectedRoomId
         * @param {Array} allRoomLocations
         * @returns {Array}
         */
        filterRoomLocations(selectedRoomId, allRoomLocations) {
            if (!selectedRoomId || !allRoomLocations) {
                return [];
            }
            return allRoomLocations.filter(loc => loc.room_id == selectedRoomId);
        },

        /**
         * Filter cabinet runs by selected room location
         * @param {number|null} selectedRoomLocationId
         * @param {Array} allCabinetRuns
         * @returns {Array}
         */
        filterCabinetRuns(selectedRoomLocationId, allCabinetRuns) {
            if (!selectedRoomLocationId || !allCabinetRuns) {
                return [];
            }
            return allCabinetRuns.filter(run => run.room_location_id == selectedRoomLocationId);
        },

        /**
         * Filter cabinets by selected cabinet run
         * @param {number|null} selectedCabinetRunId
         * @param {Array} allCabinets
         * @returns {Array}
         */
        filterCabinets(selectedCabinetRunId, allCabinets) {
            if (!selectedCabinetRunId || !allCabinets) {
                return [];
            }
            return allCabinets.filter(cab => cab.cabinet_run_id == selectedCabinetRunId);
        },

        /**
         * Reset child selections when parent changes
         */
        resetChildSelections() {
            return {
                selectedRoomLocationId: null,
                selectedCabinetRunId: null,
                selectedCabinetId: null,
                filteredRoomLocations: [],
                filteredCabinetRuns: [],
                filteredCabinets: []
            };
        }
    };
}
