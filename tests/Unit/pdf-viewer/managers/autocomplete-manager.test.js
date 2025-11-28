/**
 * Autocomplete Manager Tests
 * Tests for room and location search and selection with create-new functionality
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import {
    searchRooms,
    selectRoom,
    searchLocations,
    selectLocation,
    clearRoomSearch,
    clearLocationSearch,
} from '@pdf-viewer/managers/autocomplete-manager.js';
import { createMockState } from '../../mocks/pdf-viewer-mocks.js';

// Mock utilities module
vi.mock('@pdf-viewer/utilities.js', () => ({
    getCsrfToken: vi.fn(() => 'mock-csrf-token'),
}));

describe('Autocomplete Manager', () => {
    let state;
    let mockFetch;
    let mockAlert;

    beforeEach(() => {
        // Create mock tree structure
        state = createMockState({
            projectId: 123,
            tree: [
                { id: 1, name: 'Kitchen', children: [
                    { id: 10, name: 'Island' },
                    { id: 11, name: 'Pantry' },
                ]},
                { id: 2, name: 'Bathroom', children: [
                    { id: 20, name: 'Vanity' },
                ]},
                { id: 3, name: 'Living Room', children: [] },
            ],
            roomSearchQuery: '',
            roomSuggestions: [],
            showRoomDropdown: false,
            locationSearchQuery: '',
            locationSuggestions: [],
            showLocationDropdown: false,
            activeRoomId: null,
            activeRoomName: null,
            activeLocationId: null,
            activeLocationName: null,
        });

        // Mock console.log to reduce noise
        vi.spyOn(console, 'log').mockImplementation(() => {});
        vi.spyOn(console, 'error').mockImplementation(() => {});

        // Mock global fetch
        mockFetch = vi.fn();
        global.fetch = mockFetch;

        // Mock global alert
        mockAlert = vi.fn();
        global.alert = mockAlert;
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('searchRooms', () => {
        it('should show all existing rooms when query is empty', () => {
            searchRooms('', state);

            expect(state.roomSuggestions).toHaveLength(3);
            expect(state.roomSuggestions[0]).toEqual({ id: 1, name: 'Kitchen', isNew: false });
            expect(state.roomSuggestions[1]).toEqual({ id: 2, name: 'Bathroom', isNew: false });
            expect(state.roomSuggestions[2]).toEqual({ id: 3, name: 'Living Room', isNew: false });
        });

        it('should show all existing rooms when query is null', () => {
            searchRooms(null, state);

            expect(state.roomSuggestions).toHaveLength(3);
        });

        it('should show all existing rooms when query is whitespace', () => {
            searchRooms('  ', state);

            expect(state.roomSuggestions).toHaveLength(3);
        });

        it('should filter rooms and add create-new option', () => {
            searchRooms('kit', state);

            expect(state.roomSuggestions).toHaveLength(2);
            expect(state.roomSuggestions[0].isNew).toBe(true);
            expect(state.roomSuggestions[0].name).toBe('kit');
            expect(state.roomSuggestions[1]).toEqual({ id: 1, name: 'Kitchen', isNew: false });
        });

        it('should be case-insensitive in filtering', () => {
            searchRooms('BATH', state);

            expect(state.roomSuggestions).toHaveLength(2);
            expect(state.roomSuggestions[0].isNew).toBe(true);
            expect(state.roomSuggestions[1]).toEqual({ id: 2, name: 'Bathroom', isNew: false });
        });

        it('should handle no matching rooms', () => {
            searchRooms('Garage', state);

            expect(state.roomSuggestions).toHaveLength(1);
            expect(state.roomSuggestions[0].isNew).toBe(true);
            expect(state.roomSuggestions[0].name).toBe('Garage');
        });

        it('should generate unique ID for create-new option', () => {
            const beforeTime = Date.now();
            searchRooms('New Room', state);
            const afterTime = Date.now();

            const newRoomId = parseInt(state.roomSuggestions[0].id.replace('new_', ''));
            expect(newRoomId).toBeGreaterThanOrEqual(beforeTime);
            expect(newRoomId).toBeLessThanOrEqual(afterTime);
        });

        it('should handle empty tree', () => {
            state.tree = [];
            searchRooms('Test', state);

            expect(state.roomSuggestions).toHaveLength(1);
            expect(state.roomSuggestions[0].isNew).toBe(true);
        });

        it('should handle null tree', () => {
            state.tree = null;
            searchRooms('Test', state);

            expect(state.roomSuggestions).toHaveLength(1);
            expect(state.roomSuggestions[0].isNew).toBe(true);
        });

        it('should match partial strings', () => {
            searchRooms('Living', state);

            expect(state.roomSuggestions).toHaveLength(2);
            expect(state.roomSuggestions[0].isNew).toBe(true);
            expect(state.roomSuggestions[1]).toEqual({ id: 3, name: 'Living Room', isNew: false });
        });

        it('should log search activity', () => {
            searchRooms('Kitchen', state);

            expect(console.log).toHaveBeenCalledWith('🔍 Searching rooms with query:', 'Kitchen');
        });
    });

    describe('selectRoom', () => {
        it('should select existing room', async () => {
            const room = { id: 1, name: 'Kitchen', isNew: false };
            await selectRoom(room, state);

            expect(state.activeRoomId).toBe(1);
            expect(state.activeRoomName).toBe('Kitchen');
            expect(state.roomSearchQuery).toBe('Kitchen');
            expect(state.showRoomDropdown).toBe(false);
        });

        it('should create new room via API', async () => {
            const newRoom = { id: 'new_123', name: 'Garage', isNew: true };
            const refreshTree = vi.fn();

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    room: { id: 99, name: 'Garage' },
                }),
            });

            await selectRoom(newRoom, state, refreshTree);

            expect(mockFetch).toHaveBeenCalledWith(
                '/api/project/123/rooms',
                expect.objectContaining({
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': 'mock-csrf-token',
                    },
                    body: JSON.stringify({
                        name: 'Garage',
                        room_type: null,
                    }),
                })
            );

            expect(state.activeRoomId).toBe(99);
            expect(state.activeRoomName).toBe('Garage');
            expect(state.roomSearchQuery).toBe('Garage');
            expect(state.showRoomDropdown).toBe(false);
            expect(refreshTree).toHaveBeenCalled();
        });

        it('should handle API error when creating room', async () => {
            const newRoom = { id: 'new_123', name: 'Garage', isNew: true };

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: false,
                    error: 'Database error',
                }),
            });

            await selectRoom(newRoom, state);

            expect(console.error).toHaveBeenCalledWith(
                '❌ Failed to create room:',
                expect.any(Error)
            );
            expect(mockAlert).toHaveBeenCalledWith('Error creating room: Database error');
            expect(state.showRoomDropdown).toBe(false);
        });

        it('should handle network error when creating room', async () => {
            const newRoom = { id: 'new_123', name: 'Garage', isNew: true };

            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            await selectRoom(newRoom, state);

            expect(console.error).toHaveBeenCalledWith(
                '❌ Failed to create room:',
                expect.any(Error)
            );
            expect(mockAlert).toHaveBeenCalledWith('Error creating room: Network error');
        });

        it('should work without refresh callback', async () => {
            const newRoom = { id: 'new_123', name: 'Garage', isNew: true };

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    room: { id: 99, name: 'Garage' },
                }),
            });

            await expect(selectRoom(newRoom, state, null)).resolves.not.toThrow();
        });

        it('should log room creation', async () => {
            const newRoom = { id: 'new_123', name: 'Garage', isNew: true };

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    room: { id: 99, name: 'Garage' },
                }),
            });

            await selectRoom(newRoom, state);

            expect(console.log).toHaveBeenCalledWith('📝 Creating new room:', 'Garage');
            expect(console.log).toHaveBeenCalledWith('✓ Room created successfully:', { id: 99, name: 'Garage' });
        });
    });

    describe('searchLocations', () => {
        beforeEach(() => {
            state.activeRoomId = 1; // Kitchen
        });

        it('should do nothing if no active room', () => {
            state.activeRoomId = null;
            searchLocations('Island', state);

            expect(state.locationSuggestions).toHaveLength(0);
        });

        it('should show all existing locations when query is empty', () => {
            searchLocations('', state);

            expect(state.locationSuggestions).toHaveLength(2);
            expect(state.locationSuggestions[0]).toEqual({ id: 10, name: 'Island', isNew: false });
            expect(state.locationSuggestions[1]).toEqual({ id: 11, name: 'Pantry', isNew: false });
        });

        it('should show all existing locations when query is null', () => {
            searchLocations(null, state);

            expect(state.locationSuggestions).toHaveLength(2);
        });

        it('should show all existing locations when query is whitespace', () => {
            searchLocations('  ', state);

            expect(state.locationSuggestions).toHaveLength(2);
        });

        it('should filter locations and add create-new option', () => {
            searchLocations('pan', state);

            expect(state.locationSuggestions).toHaveLength(2);
            expect(state.locationSuggestions[0].isNew).toBe(true);
            expect(state.locationSuggestions[0].name).toBe('pan');
            expect(state.locationSuggestions[1]).toEqual({ id: 11, name: 'Pantry', isNew: false });
        });

        it('should be case-insensitive in filtering', () => {
            searchLocations('ISLAND', state);

            expect(state.locationSuggestions).toHaveLength(2);
            expect(state.locationSuggestions[0].isNew).toBe(true);
            expect(state.locationSuggestions[1]).toEqual({ id: 10, name: 'Island', isNew: false });
        });

        it('should handle no matching locations', () => {
            searchLocations('Countertop', state);

            expect(state.locationSuggestions).toHaveLength(1);
            expect(state.locationSuggestions[0].isNew).toBe(true);
            expect(state.locationSuggestions[0].name).toBe('Countertop');
        });

        it('should handle room with no children', () => {
            state.activeRoomId = 3; // Living Room (no children)
            searchLocations('Test', state);

            expect(state.locationSuggestions).toHaveLength(1);
            expect(state.locationSuggestions[0].isNew).toBe(true);
        });

        it('should handle room not found in tree', () => {
            state.activeRoomId = 999;
            searchLocations('Test', state);

            expect(state.locationSuggestions).toHaveLength(1);
            expect(state.locationSuggestions[0].isNew).toBe(true);
        });

        it('should generate unique ID for create-new option', () => {
            const beforeTime = Date.now();
            searchLocations('New Location', state);
            const afterTime = Date.now();

            const newLocationId = parseInt(state.locationSuggestions[0].id.replace('new_', ''));
            expect(newLocationId).toBeGreaterThanOrEqual(beforeTime);
            expect(newLocationId).toBeLessThanOrEqual(afterTime);
        });

        it('should log search activity', () => {
            searchLocations('Island', state);

            expect(console.log).toHaveBeenCalledWith('🔍 Searching locations with query:', 'Island');
        });
    });

    describe('selectLocation', () => {
        beforeEach(() => {
            state.activeRoomId = 1; // Kitchen
        });

        it('should select existing location', async () => {
            const location = { id: 10, name: 'Island', isNew: false };
            await selectLocation(location, state);

            expect(state.activeLocationId).toBe(10);
            expect(state.activeLocationName).toBe('Island');
            expect(state.locationSearchQuery).toBe('Island');
            expect(state.showLocationDropdown).toBe(false);
        });

        it('should create new location via API', async () => {
            const newLocation = { id: 'new_123', name: 'Countertop', isNew: true };
            const refreshTree = vi.fn();

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    location: { id: 99, name: 'Countertop' },
                }),
            });

            await selectLocation(newLocation, state, refreshTree);

            expect(mockFetch).toHaveBeenCalledWith(
                '/api/project/123/rooms/1/locations',
                expect.objectContaining({
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': 'mock-csrf-token',
                    },
                    body: JSON.stringify({
                        name: 'Countertop',
                    }),
                })
            );

            expect(state.activeLocationId).toBe(99);
            expect(state.activeLocationName).toBe('Countertop');
            expect(state.locationSearchQuery).toBe('Countertop');
            expect(state.showLocationDropdown).toBe(false);
            expect(refreshTree).toHaveBeenCalled();
        });

        it('should handle API error when creating location', async () => {
            const newLocation = { id: 'new_123', name: 'Countertop', isNew: true };

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: false,
                    error: 'Database error',
                }),
            });

            await selectLocation(newLocation, state);

            expect(console.error).toHaveBeenCalledWith(
                '❌ Failed to create location:',
                expect.any(Error)
            );
            expect(mockAlert).toHaveBeenCalledWith('Error creating location: Database error');
            expect(state.showLocationDropdown).toBe(false);
        });

        it('should handle network error when creating location', async () => {
            const newLocation = { id: 'new_123', name: 'Countertop', isNew: true };

            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            await selectLocation(newLocation, state);

            expect(console.error).toHaveBeenCalledWith(
                '❌ Failed to create location:',
                expect.any(Error)
            );
            expect(mockAlert).toHaveBeenCalledWith('Error creating location: Network error');
        });

        it('should work without refresh callback', async () => {
            const newLocation = { id: 'new_123', name: 'Countertop', isNew: true };

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    location: { id: 99, name: 'Countertop' },
                }),
            });

            await expect(selectLocation(newLocation, state, null)).resolves.not.toThrow();
        });

        it('should log location creation', async () => {
            const newLocation = { id: 'new_123', name: 'Countertop', isNew: true };

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    location: { id: 99, name: 'Countertop' },
                }),
            });

            await selectLocation(newLocation, state);

            expect(console.log).toHaveBeenCalledWith('📝 Creating new location:', 'Countertop');
            expect(console.log).toHaveBeenCalledWith('✓ Location created successfully:', { id: 99, name: 'Countertop' });
        });
    });

    describe('clearRoomSearch', () => {
        it('should clear room search state', () => {
            state.roomSearchQuery = 'Kitchen';
            state.roomSuggestions = [{ id: 1, name: 'Kitchen', isNew: false }];
            state.showRoomDropdown = true;

            clearRoomSearch(state);

            expect(state.roomSearchQuery).toBe('');
            expect(state.roomSuggestions).toEqual([]);
            expect(state.showRoomDropdown).toBe(false);
        });

        it('should work when already cleared', () => {
            clearRoomSearch(state);

            expect(state.roomSearchQuery).toBe('');
            expect(state.roomSuggestions).toEqual([]);
            expect(state.showRoomDropdown).toBe(false);
        });
    });

    describe('clearLocationSearch', () => {
        it('should clear location search state', () => {
            state.locationSearchQuery = 'Island';
            state.locationSuggestions = [{ id: 10, name: 'Island', isNew: false }];
            state.showLocationDropdown = true;

            clearLocationSearch(state);

            expect(state.locationSearchQuery).toBe('');
            expect(state.locationSuggestions).toEqual([]);
            expect(state.showLocationDropdown).toBe(false);
        });

        it('should work when already cleared', () => {
            clearLocationSearch(state);

            expect(state.locationSearchQuery).toBe('');
            expect(state.locationSuggestions).toEqual([]);
            expect(state.showLocationDropdown).toBe(false);
        });
    });

    describe('Edge Cases', () => {
        it('should handle concurrent room searches', () => {
            searchRooms('Kit', state);
            const firstResults = [...state.roomSuggestions];

            searchRooms('Bath', state);
            const secondResults = state.roomSuggestions;

            expect(firstResults).not.toEqual(secondResults);
            expect(secondResults[0].name).toBe('Bath');
        });

        it('should handle concurrent location searches', () => {
            state.activeRoomId = 1;
            searchLocations('Isl', state);
            const firstResults = [...state.locationSuggestions];

            searchLocations('Pan', state);
            const secondResults = state.locationSuggestions;

            expect(firstResults).not.toEqual(secondResults);
            expect(secondResults[0].name).toBe('Pan');
        });

        it('should handle special characters in search query', () => {
            searchRooms('Kitchen & Dining', state);

            expect(state.roomSuggestions[0].name).toBe('Kitchen & Dining');
            expect(state.roomSuggestions[0].isNew).toBe(true);
        });

        it('should handle very long room names', () => {
            const longName = 'A'.repeat(200);
            searchRooms(longName, state);

            expect(state.roomSuggestions[0].name).toBe(longName);
        });

        it('should handle tree with undefined children', () => {
            state.tree = [{ id: 1, name: 'Room Without Children' }];
            state.activeRoomId = 1;
            searchLocations('Test', state);

            expect(state.locationSuggestions).toHaveLength(1);
            expect(state.locationSuggestions[0].isNew).toBe(true);
        });

        it('should handle API response without room/location data', async () => {
            const newRoom = { id: 'new_123', name: 'Test', isNew: true };

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    // Missing room field
                }),
            });

            await selectRoom(newRoom, state);

            expect(mockAlert).toHaveBeenCalledWith('Error creating room: Failed to create room');
        });
    });
});
