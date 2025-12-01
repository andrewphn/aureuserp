{{-- Progress Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</h4>

    <div>
        <div class="flex items-center justify-between text-sm mb-1">
            <span class="text-gray-600 dark:text-gray-400">Overall</span>
            <span class="font-medium text-primary-600 dark:text-primary-400">{{ $this->completionPercentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div
                class="bg-primary-600 dark:bg-primary-400 h-2 rounded-full transition-all duration-300"
                style="width: {{ $this->completionPercentage }}%"
            ></div>
        </div>
    </div>
</div>
