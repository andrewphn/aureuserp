@php
    $nodeId = $node['id'] ?? $path;
    $hasChildren = !empty($node['children']);
    $isExpanded = $this->isExpanded($nodeId);
    $indent = $level * 20;

    $childType = $this->getChildType($node['type']);
    $childLabel = $this->getChildLabel($node['type']);

    // Type-specific styling
    $typeConfig = match($node['type']) {
        'room' => [
            'icon' => 'heroicon-o-home',
            'iconColor' => 'text-blue-500',
            'bgColor' => 'bg-blue-50/70 dark:bg-blue-900/30',
            'borderColor' => 'border-l-blue-400',
        ],
        'room_location' => [
            'icon' => 'heroicon-o-map-pin',
            'iconColor' => 'text-emerald-500',
            'bgColor' => 'bg-emerald-50/70 dark:bg-emerald-900/30',
            'borderColor' => 'border-l-emerald-400',
        ],
        'cabinet_run' => [
            'icon' => 'heroicon-o-rectangle-group',
            'iconColor' => 'text-purple-500',
            'bgColor' => 'bg-purple-50/70 dark:bg-purple-900/30',
            'borderColor' => 'border-l-purple-400',
        ],
        'cabinet' => [
            'icon' => 'heroicon-o-cube',
            'iconColor' => 'text-orange-500',
            'bgColor' => 'bg-orange-50/70 dark:bg-orange-900/30',
            'borderColor' => 'border-l-orange-400',
        ],
        default => [
            'icon' => 'heroicon-o-square-3-stack-3d',
            'iconColor' => 'text-gray-500',
            'bgColor' => 'bg-gray-50/70 dark:bg-gray-900/30',
            'borderColor' => 'border-l-gray-400',
        ],
    };
@endphp

<div class="spec-node" wire:key="node-{{ $nodeId }}">
    <div
        class="flex items-center gap-2 py-2.5 px-3 hover:{{ $typeConfig['bgColor'] }} rounded-md mx-1 transition-colors duration-150 group relative"
        style="margin-left: {{ $indent }}px"
    >
        {{-- Expand/Collapse Toggle --}}
        <div class="w-5 h-5 flex items-center justify-center flex-shrink-0">
            @if($hasChildren || $node['type'] === 'cabinet_run')
                <button
                    wire:click="toggleExpanded('{{ $nodeId }}')"
                    type="button"
                    class="p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded transition-all duration-150 hover:bg-gray-200 dark:hover:bg-gray-700"
                >
                    <x-heroicon-s-chevron-right class="w-4 h-4 transition-transform duration-150 {{ $isExpanded ? 'rotate-90' : '' }}" />
                </button>
            @endif
        </div>

        {{-- Type Icon --}}
        <x-dynamic-component :component="$typeConfig['icon']" class="w-4 h-4 {{ $typeConfig['iconColor'] }} flex-shrink-0" />

        {{-- Name --}}
        <span class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate min-w-0 flex-1">
            {{ $node['name'] ?: '(Unnamed)' }}
        </span>

        {{-- Badges Container (always visible) --}}
        <div class="flex items-center gap-1.5 flex-shrink-0">
            {{-- Type Label (for cabinets) --}}
            @if($node['type'] === 'cabinet' && !empty($node['cabinet_type']))
                <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:inline">
                    {{ ucfirst($node['cabinet_type']) }}
                </span>
            @endif

            {{-- Quantity (for cabinets) --}}
            @if($node['type'] === 'cabinet' && ($node['quantity'] ?? 1) > 1)
                <span class="text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded font-medium">
                    &times;{{ $node['quantity'] }}
                </span>
            @endif

            {{-- Linear Feet Badge --}}
            @if(isset($node['linear_feet']) && $node['linear_feet'] > 0)
                <span class="text-xs bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded font-semibold whitespace-nowrap">
                    {{ format_linear_feet($node['linear_feet']) }}
                </span>
            @endif

            {{-- Price Badge (for room_location which has cabinet_level) --}}
            @if(isset($node['estimated_price']) && $node['estimated_price'] > 0 && $node['type'] !== 'room')
                <span class="text-xs bg-green-100 dark:bg-green-900/60 text-green-700 dark:text-green-300 px-2 py-0.5 rounded font-semibold whitespace-nowrap hidden sm:inline-flex">
                    ${{ number_format($node['estimated_price'], 0) }}
                </span>
            @endif

            {{-- Cabinet Level Badge (for locations) --}}
            @if($node['type'] === 'room_location' && !empty($node['cabinet_level']))
                <span class="text-xs bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 px-1.5 py-0.5 rounded font-semibold">
                    L{{ $node['cabinet_level'] }}
                </span>
            @endif
        </div>

        {{-- Actions (shown on hover) --}}
        <div class="flex items-center gap-0.5 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-150 ml-1">
            @if($childType)
                @if($node['type'] === 'cabinet_run')
                    {{-- Inline add for cabinets (no modal) --}}
                    <button
                        wire:click="startAddCabinet('{{ $path }}')"
                        type="button"
                        class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 px-2 py-1 rounded-md hover:bg-primary-100 dark:hover:bg-primary-900/40 transition-colors font-medium whitespace-nowrap"
                    >
                        + {{ $childLabel }}
                    </button>
                @else
                    <button
                        wire:click="openCreate('{{ $childType }}', '{{ $path }}')"
                        type="button"
                        class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 px-2 py-1 rounded-md hover:bg-primary-100 dark:hover:bg-primary-900/40 transition-colors font-medium whitespace-nowrap"
                    >
                        + {{ $childLabel }}
                    </button>
                @endif
            @endif
            <button
                wire:click="openEdit('{{ $path }}')"
                type="button"
                class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Edit"
            >
                <x-heroicon-m-pencil class="w-3.5 h-3.5" />
            </button>
            <button
                wire:click="delete('{{ $path }}')"
                wire:confirm="Delete this {{ str_replace('_', ' ', $node['type']) }} and all its children?"
                type="button"
                class="p-1.5 text-gray-400 hover:text-danger-600 dark:hover:text-danger-400 rounded-md hover:bg-danger-50 dark:hover:bg-danger-900/30 transition-colors"
                title="Delete"
            >
                <x-heroicon-m-trash class="w-3.5 h-3.5" />
            </button>
        </div>
    </div>

    {{-- Children --}}
    @if($isExpanded)
        @if($node['type'] === 'cabinet_run')
            {{-- Inline cabinet table for runs (replaces tree nodes) --}}
            <div style="margin-left: {{ $indent + 20 }}px" class="mr-1">
                @include('webkul-project::livewire.partials.inline-cabinet-table', [
                    'cabinets' => $node['children'] ?? [],
                    'runPath' => $path,
                    'runType' => $node['run_type'] ?? 'base',
                ])
            </div>
        @elseif($hasChildren)
            {{-- Standard recursive tree for other node types --}}
            <div class="border-l-2 {{ $typeConfig['borderColor'] }} dark:border-opacity-50 ml-3" style="margin-left: {{ $indent + 12 }}px">
                @foreach($node['children'] as $childIndex => $child)
                    @include('webkul-project::livewire.partials.spec-tree-node', [
                        'node' => $child,
                        'path' => $path . '.children.' . $childIndex,
                        'level' => 0,
                    ])
                @endforeach
            </div>
        @endif
    @endif
</div>
