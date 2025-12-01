{{-- Rooms Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rooms & Runs</h4>

    <div class="grid grid-cols-2 gap-3">
        {{-- Rooms Count --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
            <div class="text-lg font-bold text-primary-600 dark:text-primary-400">
                {{ $this->roomsCount }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Rooms</div>
        </div>

        {{-- Cabinet Runs Count --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
            <div class="text-lg font-bold text-primary-600 dark:text-primary-400">
                {{ $this->cabinetRunsCount }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Cabinet Runs</div>
        </div>
    </div>
</div>
