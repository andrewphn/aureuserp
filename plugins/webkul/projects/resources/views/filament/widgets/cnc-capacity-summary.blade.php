<x-filament-widgets::widget>
    @php
        $stats = $this->getCapacityStats();
        $materials = $this->getMaterialBreakdown();
        $operators = $this->getOperatorStats();
        $utilization = $this->getUtilizationStats();
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-500" />
                CNC Daily Board Feet Capacity Summary
            </div>
        </x-slot>

        <x-slot name="description">
            Last 30 days production metrics
        </x-slot>

        @if($stats['working_days'] > 0)
            {{-- Main Capacity Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 px-4 text-left font-semibold text-gray-600 dark:text-gray-400">Metric</th>
                            <th class="py-2 px-4 text-center font-semibold text-gray-600 dark:text-gray-400">Sheets</th>
                            <th class="py-2 px-4 text-center font-semibold text-gray-600 dark:text-gray-400">Board Feet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr>
                            <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">Average</td>
                            <td class="py-3 px-4 text-center text-gray-700 dark:text-gray-300">{{ $stats['average_sheets_per_day'] }}/day</td>
                            <td class="py-3 px-4 text-center text-gray-700 dark:text-gray-300">~{{ $stats['average_bf_per_day'] }} BF/day</td>
                        </tr>
                        <tr class="bg-green-50 dark:bg-green-900/20">
                            <td class="py-3 px-4 font-medium text-green-700 dark:text-green-400">Peak day</td>
                            <td class="py-3 px-4 text-center text-green-700 dark:text-green-400 font-bold">{{ $stats['peak_sheets'] }}/day</td>
                            <td class="py-3 px-4 text-center text-green-700 dark:text-green-400 font-bold">~{{ $stats['peak_bf'] }} BF/day</td>
                        </tr>
                        <tr class="bg-amber-50 dark:bg-amber-900/20">
                            <td class="py-3 px-4 font-medium text-amber-700 dark:text-amber-400">Slow day</td>
                            <td class="py-3 px-4 text-center text-amber-700 dark:text-amber-400">{{ $stats['slow_sheets'] }}/day</td>
                            <td class="py-3 px-4 text-center text-amber-700 dark:text-amber-400">~{{ $stats['slow_bf'] }} BF/day</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                            <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">Period Total</td>
                            <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white">{{ $stats['total_sheets'] }} sheets</td>
                            <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white">{{ $stats['total_bf'] }} BF</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-4">
                <span>{{ $stats['working_days'] }} working days</span>
                @if($stats['peak_date'])
                    <span>Peak: {{ \Carbon\Carbon::parse($stats['peak_date'])->format('M j') }}</span>
                @endif
                @if($stats['slow_date'])
                    <span>Slow: {{ \Carbon\Carbon::parse($stats['slow_date'])->format('M j') }}</span>
                @endif
            </div>

            {{-- Utilization Summary --}}
            @if($utilization['total_programs'] > 0)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nesting Utilization</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $utilization['average_utilization'] }}%</div>
                            <div class="text-xs text-gray-500">Avg Utilization</div>
                        </div>
                        <div class="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $utilization['best_utilization'] }}%</div>
                            <div class="text-xs text-gray-500">Best</div>
                        </div>
                        <div class="text-center p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                            <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $utilization['total_waste_sqft'] }} sqft</div>
                            <div class="text-xs text-gray-500">Total Waste</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Material Breakdown --}}
            @if(!empty($materials))
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">By Material</h4>
                    <div class="space-y-2">
                        @foreach($materials as $mat)
                            <div class="flex items-center justify-between text-sm">
                                <span class="inline-flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium
                                        @switch($mat['material_code'])
                                            @case('FL') bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 @break
                                            @case('PreFin') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 @break
                                            @case('RiftWOPly') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 @break
                                            @case('MDF_RiftWO') bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 @break
                                            @case('Medex') bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-300 @break
                                            @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endswitch
                                    ">{{ $mat['material_code'] }}</span>
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ $mat['sheets'] }} sheets / {{ $mat['bf'] }} BF
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Operator Stats --}}
            @if(!empty($operators))
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">By Operator</h4>
                    <div class="space-y-2">
                        @foreach($operators as $op)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-900 dark:text-white">{{ $op['operator_name'] }}</span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ $op['sheets_completed'] }} sheets ({{ $op['total_hours'] }}h)
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-3 opacity-50" />
                <p class="text-lg">No CNC production data yet</p>
                <p class="text-sm mt-1">Complete CNC parts to see capacity metrics here.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
