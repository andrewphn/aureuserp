{{-- Lead Source Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lead Source</h4>

    @if($this->leadSourceLabel)
        <div class="flex items-center gap-2">
            <x-heroicon-o-megaphone class="h-4 w-4 text-gray-400 flex-shrink-0" />
            <span class="text-sm text-gray-900 dark:text-white">{{ $this->leadSourceLabel }}</span>
        </div>
    @else
        <p class="text-sm text-gray-400 italic">Not selected</p>
    @endif
</div>
