{{-- Timeline Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timeline</h4>

    <div class="space-y-1.5">
        @if($data['start_date'] ?? null)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Start</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($data['start_date'])->format('M j, Y') }}
                </span>
            </div>
        @endif

        @if($data['desired_completion_date'] ?? null)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Target Completion</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($data['desired_completion_date'])->format('M j, Y') }}
                </span>
            </div>
        @endif

        @if($data['delivery_date'] ?? null)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Delivery</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($data['delivery_date'])->format('M j, Y') }}
                </span>
            </div>
        @endif

        @if(!($data['start_date'] ?? null) && !($data['desired_completion_date'] ?? null) && !($data['delivery_date'] ?? null))
            <p class="text-sm text-gray-400 italic">Not set</p>
        @endif
    </div>
</div>
