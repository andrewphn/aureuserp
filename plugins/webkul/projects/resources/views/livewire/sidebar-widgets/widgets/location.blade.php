{{-- Location Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</h4>

    @if($this->formattedLocation)
        <div class="flex items-start gap-2">
            <x-heroicon-o-map-pin class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" />
            <div class="text-sm text-gray-900 dark:text-white whitespace-pre-line">
                {{ $this->formattedLocation }}
            </div>
        </div>
    @else
        <p class="text-sm text-gray-400 italic">Not entered</p>
    @endif
</div>
