{{-- Lead Card Component --}}
@props(['lead'])

<x-filament::section
    compact
    class="cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all"
    x-data
    x-on:click="$wire.openLeadDetails({{ $lead->id }})"
>
    {{-- Lead Header --}}
    <div class="flex items-start justify-between mb-1">
        <span class="font-medium text-gray-900 dark:text-white text-sm truncate flex-1">
            {{ $lead->full_name }}
        </span>
        @if($lead->is_new)
            <x-filament::badge color="success" size="sm">
                New
            </x-filament::badge>
        @endif
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mb-2">
        {{ $lead->email }}
    </p>

    {{-- Lead Info --}}
    @if($lead->project_type || $lead->budget_range)
        <div class="space-y-1.5 text-xs mb-2">
            @if($lead->project_type)
                <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                    <x-filament::icon
                        icon="heroicon-m-briefcase"
                        class="h-3.5 w-3.5 text-gray-400"
                    />
                    <span class="truncate">{{ is_array($lead->project_type) ? implode(', ', $lead->project_type) : $lead->project_type }}</span>
                </div>
            @endif
            @if($lead->budget_range)
                <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                    <x-filament::icon
                        icon="heroicon-m-currency-dollar"
                        class="h-3.5 w-3.5 text-gray-400"
                    />
                    <span class="font-medium text-success-600 dark:text-success-400">
                        @switch($lead->budget_range)
                            @case('under_10k') < $10K @break
                            @case('10k_25k') $10K-$25K @break
                            @case('25k_50k') $25K-$50K @break
                            @case('50k_100k') $50K-$100K @break
                            @case('over_100k') > $100K @break
                            @default {{ $lead->budget_range }}
                        @endswitch
                    </span>
                </div>
            @endif
        </div>
    @endif

    {{-- Footer with source and time --}}
    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
        @if($lead->source)
            <x-filament::badge color="gray" size="sm">
                {{ $lead->source->getLabel() }}
            </x-filament::badge>
        @else
            <span></span>
        @endif
        <span class="text-[10px] text-gray-400">{{ $lead->created_at->diffForHumans(null, true) }}</span>
    </div>
</x-filament::section>
