{{-- Reusable Card Metadata Row Component --}}
@props([
    'dueDate' => null,           // Carbon date instance
    'linearFeet' => null,        // Number
    'hours' => null,             // ['effective' => x, 'allocated' => y]
    'daysLeft' => null,          // Integer (can be negative)
    'isOverdue' => false,
    'showDays' => true,
    'showDate' => true,
    'statusLabel' => null,       // If set, hides daysLeft display
])

<div class="flex items-center justify-between mt-3 text-xs">
    {{-- Left: Due date --}}
    <div class="flex items-center gap-1 text-gray-500">
        @if($showDate && $dueDate)
            <x-filament::icon
                icon="heroicon-m-calendar"
                class="h-3.5 w-3.5 text-gray-400"
            />
            <span class="font-medium">{{ $dueDate->format('M j') }}</span>
        @endif
    </div>

    {{-- Right: Key metrics --}}
    <div class="flex items-center gap-2 text-gray-500">
        {{-- Linear Feet (for projects) --}}
        @if($linearFeet)
            <span class="font-medium">{{ number_format($linearFeet, 0) }} LF</span>
        @endif

        {{-- Hours (for tasks) --}}
        @if($hours && ($hours['allocated'] ?? null))
            <span class="font-medium">
                {{ number_format($hours['effective'] ?? 0, 1) }}/{{ number_format($hours['allocated'], 1) }}h
            </span>
        @endif

        {{-- Days indicator --}}
        @if($showDays)
            @if($daysLeft !== null && !$statusLabel)
                <span class="font-medium">{{ $daysLeft }}d</span>
            @elseif($isOverdue && $daysLeft !== null)
                <span class="font-bold" style="color: #dc2626;">{{ abs($daysLeft) }}d late</span>
            @endif
        @endif
    </div>
</div>
