{{-- Reusable Context Menu Component (Right-Click Menu) --}}
@props([
    'record',
    'hasBlockers' => false,
    'unreadCount' => 0,
    'editUrl' => null,
    'viewUrl' => null,
    'type' => 'project',  // 'project' or 'task'
])

<div
    x-show="showMenu"
    x-cloak
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    :style="`position: fixed; top: ${menuY}px; left: ${menuX}px; z-index: 9999;`"
    class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 min-w-[200px]"
>
    {{-- Quick Actions --}}
    <button
        @click.stop="showMenu = false; $wire.openQuickActions('{{ $record->getKey() }}')"
        class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
    >
        <x-heroicon-m-bolt class="h-4 w-4 text-primary-500" />
        Quick Actions
    </button>

    {{-- Edit --}}
    @if($editUrl)
        <a
            href="{{ $editUrl }}"
            @click.stop="showMenu = false"
            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-pencil-square class="h-4 w-4 text-primary-500" />
            Edit {{ ucfirst($type) }}
        </a>
    @endif

    {{-- Messages --}}
    <button
        @click.stop="showMenu = false; $wire.openChatter('{{ $record->getKey() }}')"
        class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
    >
        <x-heroicon-m-chat-bubble-left-right class="h-4 w-4 text-primary-500" />
        Messages
        @if($unreadCount > 0)
            <span class="ml-auto bg-primary-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ $unreadCount }}</span>
        @endif
    </button>

    {{-- View Full Page --}}
    @if($viewUrl)
        <a
            href="{{ $viewUrl }}"
            @click.stop="showMenu = false"
            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4 text-primary-500" />
            View Full Page
        </a>
    @endif

    {{-- Divider (only for projects with block toggle) --}}
    @if($type === 'project')
        <hr class="my-1 border-gray-200 dark:border-gray-700" />

        {{-- Mark Blocked / Unblock --}}
        <button
            @click.stop="showMenu = false; $wire.toggleProjectBlocked('{{ $record->getKey() }}')"
            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm {{ $hasBlockers ? 'text-success-600' : 'text-purple-600' }}"
        >
            @if($hasBlockers)
                <x-heroicon-m-check-circle class="h-4 w-4" />
                Unblock Project
            @else
                <x-heroicon-m-no-symbol class="h-4 w-4" />
                Mark as Blocked
            @endif
        </button>
    @endif
</div>
