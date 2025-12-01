{{-- Scope Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scope</h4>

    <div class="grid grid-cols-2 gap-3">
        {{-- Linear Feet --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
            <div class="text-lg font-bold text-primary-600 dark:text-primary-400">
                {{ $data['estimated_linear_feet'] ?? '—' }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Linear Feet</div>
        </div>

        {{-- Complexity --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
            <div class="text-lg font-bold text-primary-600 dark:text-primary-400">
                {{ $data['complexity_score'] ?? '—' }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Complexity</div>
        </div>
    </div>
</div>
