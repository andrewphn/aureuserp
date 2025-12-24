<div class="rounded-lg bg-primary-50 dark:bg-primary-500/10 p-4 border border-primary-200 dark:border-primary-500/20">
    <div class="grid grid-cols-{{ isset($roomCount) ? '4' : '3' }} gap-4">
        @if(isset($roomCount))
        {{-- Room Count (only in room-by-room mode) --}}
        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Rooms</p>
            <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $roomCount }}</p>
            <p class="text-xs text-gray-400">total</p>
        </div>
        @endif

        {{-- Linear Feet --}}
        <div class="text-center {{ isset($roomCount) ? '' : '' }}">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Linear Feet</p>
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($linearFeet, 1) }}</p>
            <p class="text-xs text-gray-400">LF</p>
        </div>

        {{-- Quick Estimate --}}
        <div class="text-center border-x border-primary-200 dark:border-primary-500/20">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Estimate</p>
            <p class="text-2xl font-bold text-success-600 dark:text-success-400">${{ number_format($quickEstimate) }}</p>
            <p class="text-xs text-gray-400">@ ${{ number_format($baseRate) }}/LF avg</p>
        </div>

        {{-- Production Time --}}
        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Production</p>
            <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $productionTime }}</p>
            <p class="text-xs text-gray-400">estimated</p>
        </div>
    </div>

    <p class="mt-3 text-xs text-center text-gray-500 dark:text-gray-400 italic">
        @if(isset($roomCount))
            Calculated from room-specific pricing options. Each room uses its own cabinet level, material, and finish.
        @else
            Based on selected pricing options. Adjust cabinet level, material, and finish above.
        @endif
    </p>
</div>
