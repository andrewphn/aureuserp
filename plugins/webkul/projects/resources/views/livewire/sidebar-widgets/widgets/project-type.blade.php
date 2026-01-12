{{-- Project Type Widget --}}
<div class="space-y-1.5">
    <h4 class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Project Type</h4>

    @if($this->projectTypeLabel)
        <div class="flex items-center gap-1.5">
            <x-filament::icon icon="heroicon-o-building-office" class="h-3.5 w-3.5 text-gray-400 flex-shrink-0" />
            <span class="text-[11px] font-medium text-gray-900 dark:text-white">{{ $this->projectTypeLabel }}</span>
        </div>
    @else
        <p class="text-[11px] text-gray-400 italic">Not selected</p>
    @endif
</div>
