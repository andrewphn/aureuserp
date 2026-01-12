@props([
    'type' => 'room', // room, location, run
    'name',
    'subtitle' => null,
    'icon' => null,
    'selected' => false,
    'expanded' => false,
    'hasChildren' => false,
    'depth' => 0,
])

@php
    $typeIcon = match($type) {
        'room' => 'heroicon-o-home',
        'location' => 'heroicon-o-map-pin',
        'run' => 'heroicon-o-arrows-right-left',
        default => 'heroicon-o-folder',
    };

    $bgClasses = $selected
        ? 'bg-primary-50 dark:bg-primary-900/30 ring-1 ring-primary-200 dark:ring-primary-800'
        : 'hover:bg-gray-100 dark:hover:bg-gray-700';

    $textClasses = $selected
        ? 'text-primary-700 dark:text-primary-300'
        : 'text-gray-700 dark:text-gray-200';

    $paddingLeft = match($depth) {
        0 => 'pl-2',
        1 => 'pl-6',
        2 => 'pl-10',
        default => 'pl-2',
    };
@endphp

<div
    {{ $attributes->merge(['class' => "group flex items-center gap-2 py-1.5 pr-2 $paddingLeft rounded-lg cursor-pointer transition-colors $bgClasses"]) }}
>
    {{-- Expand/Collapse Toggle --}}
    @if($hasChildren)
        <button
            type="button"
            class="flex-shrink-0 p-0.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            @click.stop="$dispatch('toggle-expand')"
        >
            <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-gray-400 transition-transform {{ $expanded ? 'rotate-90' : '' }}" />
        </button>
    @else
        <span class="w-4"></span>
    @endif

    {{-- Icon --}}
    <x-dynamic-component
        :component="$icon ?? $typeIcon"
        class="w-4 h-4 flex-shrink-0 {{ $selected ? 'text-primary-500' : 'text-gray-400' }}"
    />

    {{-- Text --}}
    <div class="flex-1 min-w-0">
        <span class="block text-sm font-medium truncate {{ $textClasses }}">
            {{ $name }}
        </span>
        @if($subtitle)
            <span class="block text-[10px] text-gray-500 dark:text-gray-400 truncate">
                {{ $subtitle }}
            </span>
        @endif
    </div>

    {{-- Actions (shown on hover) --}}
    @if($slot->isNotEmpty())
        <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            {{ $slot }}
        </div>
    @endif
</div>
