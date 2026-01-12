{{-- Location Widget --}}
<div class="space-y-1.5">
    <h4 class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Location</h4>

    @if($this->formattedLocation)
        <div class="flex items-start gap-1.5">
            <x-filament::icon icon="heroicon-o-map-pin" class="h-3.5 w-3.5 text-gray-400 mt-0.5 flex-shrink-0" />
            <div class="text-[11px] text-gray-900 dark:text-white whitespace-pre-line leading-tight">
                {{ $this->formattedLocation }}
            </div>
        </div>
    @else
        <p class="text-[11px] text-gray-400 italic">Not entered</p>
    @endif
</div>
