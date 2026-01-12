{{-- Timeline Widget --}}
<div class="space-y-1.5">
    <h4 class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Timeline</h4>

    <div class="space-y-1">
        @if($data['start_date'] ?? null)
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-play" class="h-3 w-3" />
                    Start
                </span>
                <span class="text-[11px] font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($data['start_date'])->format('M j, Y') }}
                </span>
            </div>
        @endif

        @if($data['desired_completion_date'] ?? null)
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-flag" class="h-3 w-3" />
                    Target
                </span>
                <span class="text-[11px] font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($data['desired_completion_date'])->format('M j, Y') }}
                </span>
            </div>
        @endif

        @if($data['delivery_date'] ?? null)
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-truck" class="h-3 w-3" />
                    Delivery
                </span>
                <span class="text-[11px] font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($data['delivery_date'])->format('M j, Y') }}
                </span>
            </div>
        @endif

        @if(!($data['start_date'] ?? null) && !($data['desired_completion_date'] ?? null) && !($data['delivery_date'] ?? null))
            <p class="text-[11px] text-gray-400 italic">Not set</p>
        @endif
    </div>
</div>
