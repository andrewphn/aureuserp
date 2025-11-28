<div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-3 mb-3">
        <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center">
            <x-heroicon-o-user class="h-5 w-5 text-primary-600 dark:text-primary-400" />
        </div>
        <div>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $partner->name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Customer since {{ $partner->created_at?->format('M Y') ?? 'Unknown' }}</p>
        </div>
        @if($totalProjects >= 3)
            <span class="ml-auto inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-300">
                <x-heroicon-s-star class="h-3 w-3 mr-1" />
                VIP
            </span>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="text-center py-2 rounded bg-white dark:bg-gray-900">
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalProjects }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Projects</p>
        </div>
        <div class="text-center py-2 rounded bg-white dark:bg-gray-900">
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                @if($totalProjects > 0)
                    Repeat
                @else
                    New
                @endif
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Customer Type</p>
        </div>
    </div>
</div>
