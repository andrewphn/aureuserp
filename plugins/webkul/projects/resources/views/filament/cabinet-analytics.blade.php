<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Cabinet Analytics Dashboard</h2>

    <div class="space-y-6">
        {{-- Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Cabinets</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format(\Webkul\Project\Models\CabinetSpecification::sum('quantity')) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</h3>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                    ${{ number_format(\Webkul\Project\Models\CabinetSpecification::sum('total_price'), 2) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Linear Feet</h3>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format(\Webkul\Project\Models\CabinetSpecification::avg('linear_feet'), 2) }} LF
                </p>
            </div>
        </div>

        {{-- Size Distribution Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Size Distribution</h3>
            <p class="text-gray-600 dark:text-gray-400">
                View detailed size analytics in the widgets above.
            </p>
        </div>

        {{-- Common Sizes Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Most Common Cabinet Sizes</h3>
            <p class="text-gray-600 dark:text-gray-400">
                View common sizes in the table widget above.
            </p>
        </div>
    </div>
</div>
