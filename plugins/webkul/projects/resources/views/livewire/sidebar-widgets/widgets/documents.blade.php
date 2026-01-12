{{-- Documents Widget --}}
<div class="space-y-1.5">
    <h4 class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Documents</h4>

    @if($this->documentCount > 0)
        <div class="flex items-center gap-1.5">
            <x-filament::icon icon="heroicon-o-document" class="h-3.5 w-3.5 text-gray-400 flex-shrink-0" />
            <span class="text-[11px] font-medium text-gray-900 dark:text-white">
                {{ $this->documentCount }} {{ Str::plural('file', $this->documentCount) }} attached
            </span>
        </div>
    @else
        <p class="text-[11px] text-gray-400 italic">No documents</p>
    @endif
</div>
