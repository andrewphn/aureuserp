{{-- Budget Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Budget Range</h4>

    @if($this->budgetRangeLabel)
        <div class="flex items-center gap-2">
            <x-heroicon-o-currency-dollar class="h-4 w-4 text-gray-400 flex-shrink-0" />
            <span class="text-sm font-medium text-gray-900 dark:text-white">
                {{ $this->budgetRangeLabel }}
            </span>
        </div>
    @else
        <p class="text-sm text-gray-400 italic">Not set</p>
    @endif
</div>
