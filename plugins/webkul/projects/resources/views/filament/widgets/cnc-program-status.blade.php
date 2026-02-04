<x-filament-widgets::widget>
    @php
        $alerts = $this->getAlerts();
        $stats = $this->getStats();
        $info = $this->getProgramInfo();
    @endphp

    <div class="space-y-4">
        {{-- Alerts Section --}}
        @if(count($alerts) > 0)
            <div class="grid gap-2">
                @foreach($alerts as $alert)
                    <div @class([
                        'flex items-center gap-3 p-3 rounded-lg border',
                        'bg-warning-50 border-warning-200 dark:bg-warning-900/20 dark:border-warning-700' => $alert['type'] === 'warning',
                        'bg-danger-50 border-danger-200 dark:bg-danger-900/20 dark:border-danger-700' => $alert['type'] === 'danger',
                        'bg-info-50 border-info-200 dark:bg-info-900/20 dark:border-info-700' => $alert['type'] === 'info',
                        'bg-success-50 border-success-200 dark:bg-success-900/20 dark:border-success-700' => $alert['type'] === 'success',
                    ])>
                        <x-dynamic-component
                            :component="$alert['icon']"
                            @class([
                                'w-5 h-5',
                                'text-warning-600 dark:text-warning-400' => $alert['type'] === 'warning',
                                'text-danger-600 dark:text-danger-400' => $alert['type'] === 'danger',
                                'text-info-600 dark:text-info-400' => $alert['type'] === 'info',
                                'text-success-600 dark:text-success-400' => $alert['type'] === 'success',
                            ])
                        />
                        <div>
                            <div @class([
                                'font-semibold text-sm',
                                'text-warning-700 dark:text-warning-300' => $alert['type'] === 'warning',
                                'text-danger-700 dark:text-danger-300' => $alert['type'] === 'danger',
                                'text-info-700 dark:text-info-300' => $alert['type'] === 'info',
                                'text-success-700 dark:text-success-300' => $alert['type'] === 'success',
                            ])>
                                {{ $alert['title'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $alert['message'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Progress Bar --}}
        @if($stats['total'] > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $stats['progress'] }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div
                        class="h-3 rounded-full transition-all duration-500 {{ $stats['progress'] >= 100 ? 'bg-success-500' : ($stats['progress'] > 0 ? 'bg-primary-500' : 'bg-gray-300') }}"
                        style="width: {{ $stats['progress'] }}%"
                    ></div>
                </div>
                <div class="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $stats['complete'] }}/{{ $stats['total'] }} parts complete</span>
                    @if($stats['running'] > 0)
                        <span class="text-info-600 dark:text-info-400">{{ $stats['running'] }} running</span>
                    @endif
                    @if($stats['pending'] > 0)
                        <span>{{ $stats['pending'] }} pending</span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Program Info Tags --}}
        <div class="flex flex-wrap gap-2">
            @if($info['project'])
                <a href="{{ route('filament.admin.resources.project.projects.view', ['record' => $this->record->project_id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-building-office class="w-4 h-4" />
                    {{ $info['project'] }}
                </a>
            @endif

            @if($info['material'])
                <span @class([
                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium',
                    'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' => $info['material'] === 'FL',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' => $info['material'] === 'PreFin',
                    'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' => $info['material'] === 'RiftWOPly',
                    'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' => $info['material'] === 'MDF_RiftWO',
                    'bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-300' => $info['material'] === 'Medex',
                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => !in_array($info['material'], ['FL', 'PreFin', 'RiftWOPly', 'MDF_RiftWO', 'Medex']),
                ])>
                    <x-heroicon-o-square-3-stack-3d class="w-4 h-4" />
                    {{ $info['material'] }}
                </span>
            @endif

            @if($info['sheet_size'])
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-sm">
                    <x-heroicon-o-rectangle-stack class="w-4 h-4" />
                    {{ $info['sheet_size'] }}
                </span>
            @endif

            @if($info['sheet_count'])
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-sm">
                    <x-heroicon-o-hashtag class="w-4 h-4" />
                    {{ $info['sheet_count'] }} sheets
                </span>
            @endif

            @if($info['utilization'])
                <span @class([
                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium',
                    'bg-success-100 text-success-800 dark:bg-success-900/50 dark:text-success-300' => $info['utilization'] >= 85,
                    'bg-info-100 text-info-800 dark:bg-info-900/50 dark:text-info-300' => $info['utilization'] >= 75 && $info['utilization'] < 85,
                    'bg-warning-100 text-warning-800 dark:bg-warning-900/50 dark:text-warning-300' => $info['utilization'] >= 65 && $info['utilization'] < 75,
                    'bg-danger-100 text-danger-800 dark:bg-danger-900/50 dark:text-danger-300' => $info['utilization'] < 65,
                ])>
                    <x-heroicon-o-chart-pie class="w-4 h-4" />
                    {{ number_format($info['utilization'], 1) }}% utilization
                </span>
            @endif

            <span @class([
                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium',
                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $info['status'] === 'pending',
                'bg-info-100 text-info-800 dark:bg-info-900/50 dark:text-info-300' => $info['status'] === 'in_progress',
                'bg-success-100 text-success-800 dark:bg-success-900/50 dark:text-success-300' => $info['status'] === 'complete',
                'bg-danger-100 text-danger-800 dark:bg-danger-900/50 dark:text-danger-300' => $info['status'] === 'error',
            ])>
                @switch($info['status'])
                    @case('pending')
                        <x-heroicon-o-clock class="w-4 h-4" />
                        @break
                    @case('in_progress')
                        <x-heroicon-o-play class="w-4 h-4" />
                        @break
                    @case('complete')
                        <x-heroicon-o-check-circle class="w-4 h-4" />
                        @break
                    @case('error')
                        <x-heroicon-o-x-circle class="w-4 h-4" />
                        @break
                @endswitch
                {{ ucfirst(str_replace('_', ' ', $info['status'])) }}
            </span>
        </div>
    </div>
</x-filament-widgets::widget>
