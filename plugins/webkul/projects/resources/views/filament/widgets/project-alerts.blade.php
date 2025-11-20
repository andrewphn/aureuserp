<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bell-alert class="w-5 h-5" />
                <span>Project Alerts & Action Items</span>
            </div>
        </x-slot>

        <div class="space-y-3">
            @forelse ($this->getAlerts() as $alert)
                <div class="flex items-start gap-3 p-3 rounded-lg {{
                    $alert['type'] === 'danger' ? 'bg-danger-50 dark:bg-danger-950' :
                    ($alert['type'] === 'warning' ? 'bg-warning-50 dark:bg-warning-950' :
                    ($alert['type'] === 'success' ? 'bg-success-50 dark:bg-success-950' :
                    'bg-gray-50 dark:bg-gray-950'))
                }}">
                    <div class="flex-shrink-0 mt-0.5">
                        @svg($alert['icon'], 'w-5 h-5 ' . (
                            $alert['type'] === 'danger' ? 'text-danger-600 dark:text-danger-400' :
                            ($alert['type'] === 'warning' ? 'text-warning-600 dark:text-warning-400' :
                            ($alert['type'] === 'success' ? 'text-success-600 dark:text-success-400' :
                            'text-gray-600 dark:text-gray-400'))
                        ))
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium {{
                            $alert['type'] === 'danger' ? 'text-danger-800 dark:text-danger-200' :
                            ($alert['type'] === 'warning' ? 'text-warning-800 dark:text-warning-200' :
                            ($alert['type'] === 'success' ? 'text-success-800 dark:text-success-200' :
                            'text-gray-800 dark:text-gray-200'))
                        }}">
                            {{ $alert['message'] }}
                        </p>
                        <p class="mt-1 text-xs {{
                            $alert['type'] === 'danger' ? 'text-danger-600 dark:text-danger-400' :
                            ($alert['type'] === 'warning' ? 'text-warning-600 dark:text-warning-400' :
                            ($alert['type'] === 'success' ? 'text-success-600 dark:text-success-400' :
                            'text-gray-600 dark:text-gray-400'))
                        }}">
                            {{ $alert['action'] }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="flex items-center gap-3 p-3 rounded-lg bg-success-50 dark:bg-success-950">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-success-600 dark:text-success-400" />
                    <p class="text-sm font-medium text-success-800 dark:text-success-200">
                        No pending action items
                    </p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
