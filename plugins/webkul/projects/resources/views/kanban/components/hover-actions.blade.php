{{-- Reusable Hover Actions Bar Component --}}
@props([
    'record',
    'editUrl' => null,
    'viewUrl' => null,
    'showChatter' => true,
    'compactMode' => false,
])

<div class="flex items-center justify-end gap-1 mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700
            {{ $compactMode ? 'hidden' : 'opacity-0 group-hover:opacity-100' }} transition-opacity">

    @if($showChatter)
        <x-filament::icon-button
            wire:click.stop="openChatter('{{ $record->getKey() }}')"
            icon="heroicon-m-chat-bubble-left-right"
            color="gray"
            size="sm"
            label="Messages"
        />
    @endif

    @if($editUrl)
        <x-filament::icon-button
            tag="a"
            :href="$editUrl"
            wire:click.stop
            icon="heroicon-m-pencil-square"
            color="gray"
            size="sm"
            label="Edit"
        />
    @endif

    @if($viewUrl)
        <x-filament::icon-button
            tag="a"
            :href="$viewUrl"
            wire:click.stop
            icon="heroicon-m-arrow-top-right-on-square"
            color="gray"
            size="sm"
            label="View Full Page"
        />
    @endif
</div>
