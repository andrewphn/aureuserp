{{-- Reusable Drawing Tool Button Component --}}
@props([
    'mode',          // 'room', 'location', 'cabinet_run', 'cabinet'
    'icon',          // Heroicon name
    'title',         // Tooltip text
    'requiresRoom' => false,
    'requiresLocation' => false,
])

@php
    $colors = [
        'room' => ['active' => 'warning', 'inactive' => 'gray'],
        'location' => ['active' => 'info', 'inactive' => 'gray'],
        'cabinet_run' => ['active' => 'primary', 'inactive' => 'gray'],
        'cabinet' => ['active' => 'success', 'inactive' => 'gray'],
    ];

    $colorScheme = $colors[$mode] ?? $colors['room'];
@endphp

<button
    @click="setDrawMode('{{ $mode }}')"
    :class="drawMode === '{{ $mode }}' ? 'ring-2 ring-{{ $colorScheme['active'] }}-500 shadow-lg transform scale-105' : ''"
    :style="drawMode === '{{ $mode }}' ? 'background-color: var(--{{ $colorScheme['active'] }}-600); color: white; border-color: var(--{{ $colorScheme['active'] }}-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
    @if($requiresRoom && $requiresLocation)
        :disabled="!canDraw()"
    @elseif($requiresRoom)
        :disabled="!canDrawLocation()"
    @endif
    class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all {{ $requiresRoom || $requiresLocation ? 'disabled:opacity-40 disabled:cursor-not-allowed' : '' }} flex items-center justify-center border dark:bg-gray-700 dark:text-white"
    title="{{ $title }}"
>
    <x-filament::icon icon="{{ $icon }}" class="h-5 w-5" />
</button>
