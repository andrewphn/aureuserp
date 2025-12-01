{{-- Estimate Widget (Footer Style) --}}
@if($this->quickEstimate)
    <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600 dark:text-gray-400">Quick Estimate</span>
        <span class="text-lg font-bold text-success-600 dark:text-success-400">
            ${{ number_format($this->quickEstimate, 0) }}
        </span>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
        Based on ${{ number_format($pricePerLinearFoot, 0) }}/linear foot
    </p>
@else
    <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600 dark:text-gray-400">Quick Estimate</span>
        <span class="text-sm text-gray-400 italic">Enter linear feet</span>
    </div>
@endif
