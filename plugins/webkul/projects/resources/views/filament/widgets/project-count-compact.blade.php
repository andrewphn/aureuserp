@php
    $stats = $this->getStats();
    $stages = [
        'discovery' => ['label' => 'Discovery', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
        'design' => ['label' => 'Design', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
        'sourcing' => ['label' => 'Sourcing', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
        'production' => ['label' => 'Production', 'color' => '#10b981', 'bg' => '#d1fae5'],
        'delivery' => ['label' => 'Delivery', 'color' => '#14b8a6', 'bg' => '#ccfbf1'],
    ];
@endphp

<x-filament-widgets::widget>
    <div class="flex items-center gap-6 px-4 py-2 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        {{-- Total Active --}}
        <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">active</span>
        </div>

        <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

        {{-- Stage Breakdown Pills --}}
        <div class="flex items-center gap-2">
            @foreach($stages as $key => $stage)
                @if(($stats['by_stage'][$key] ?? 0) > 0)
                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                        style="background-color: {{ $stage['bg'] }}; color: {{ $stage['color'] }};"
                    >
                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $stage['color'] }};"></span>
                        {{ $stats['by_stage'][$key] }} {{ $stage['label'] }}
                    </span>
                @endif
            @endforeach
        </div>

        <div class="flex-1"></div>

        {{-- Quick Stats --}}
        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            <span>{{ $stats['this_month'] }} this month</span>
            @if($stats['archived'] > 0)
                <span>{{ $stats['archived'] }} archived</span>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
