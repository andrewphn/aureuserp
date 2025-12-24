@php
    $path = $path ?? [];
@endphp

<div class="flex items-center gap-2 text-sm py-2 px-3 bg-gray-50 dark:bg-gray-800 rounded-lg mb-4">
    <span class="text-gray-500 dark:text-gray-400 font-medium">Path:</span>

    @if(empty($path))
        <span class="text-gray-400 dark:text-gray-500 italic">Select a room to begin...</span>
    @else
        @foreach($path as $index => $item)
            @if($index > 0)
                <x-heroicon-s-chevron-right class="w-4 h-4 text-gray-400" />
            @endif

            <button
                type="button"
                wire:click="navigateToBreadcrumb({{ $index }})"
                class="text-primary-600 dark:text-primary-400 hover:underline font-medium cursor-pointer"
            >
                {{ $item['label'] }}
            </button>
        @endforeach
    @endif
</div>
