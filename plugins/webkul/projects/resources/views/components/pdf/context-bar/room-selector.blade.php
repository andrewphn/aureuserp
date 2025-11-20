{{-- Room Autocomplete Selector --}}
<div class="relative flex-1 max-w-xs min-w-[200px]">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Room</label>
    <input
        type="text"
        x-model="roomSearchQuery"
        @input="searchRooms($event.target.value)"
        @focus="showRoomDropdown = true"
        @click.away="showRoomDropdown = false"
        placeholder="Type to search or create..."
        class="w-full px-3 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 ring-primary-600 shadow-sm"
    />

    <!-- Room Suggestions Dropdown -->
    <div
        x-show="showRoomDropdown && roomSuggestions.length > 0"
        class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
    >
        <template x-for="room in roomSuggestions" :key="room.id">
            <div
                @click="selectRoom(room)"
                class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer flex items-center gap-2"
            >
                <span x-show="!room.isNew" class="text-green-600">✓</span>
                <span x-show="room.isNew" class="text-blue-600 font-bold">+</span>
                <span x-text="room.name" class="text-sm"></span>
                <span x-show="!room.isNew" class="text-xs text-gray-500 ml-auto">Existing</span>
                <span x-show="room.isNew" class="text-xs text-blue-600 ml-auto">Create New</span>
            </div>
        </template>
    </div>
</div>
