<div class="rounded-lg bg-primary-50 dark:bg-primary-500/10 p-4 border border-primary-200 dark:border-primary-500/20">
    <div class="grid grid-cols-3 gap-4">
        {{-- Linear Feet --}}
        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Linear Feet</p>
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($linearFeet, 1) }}</p>
            <p class="text-xs text-gray-400">LF</p>
        </div>

        {{-- Quick Estimate --}}
        <div class="text-center border-x border-primary-200 dark:border-primary-500/20">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Estimate</p>
            <p class="text-2xl font-bold text-success-600 dark:text-success-400">${{ number_format($quickEstimate) }}</p>
            <p class="text-xs text-gray-400">@ ${{ number_format($baseRate) }}/LF</p>
        </div>

        {{-- Production Time --}}
        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Production</p>
            <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $productionTime }}</p>
            <p class="text-xs text-gray-400">estimated</p>
        </div>
    </div>

    <p class="mt-3 text-xs text-center text-gray-500 dark:text-gray-400 italic">
        Based on Level 3 cabinetry with stain grade finish. Actual pricing varies by specifications.
    </p>
</div>
