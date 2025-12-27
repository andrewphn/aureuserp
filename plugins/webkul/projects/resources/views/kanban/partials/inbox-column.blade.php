{{-- INBOX COLUMN (Leads / New Inquiries) - Only show in projects mode --}}
@if($currentViewMode === 'projects')
<div class="flex-shrink-0 h-full min-h-0">
    {{-- Collapsed State - Icon with count --}}
    <div
        x-show="!inboxOpen"
        @click="toggleInbox()"
        x-data="{ isDark: document.documentElement.classList.contains('dark') }"
        x-init="new MutationObserver(() => isDark = document.documentElement.classList.contains('dark')).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })"
        class="w-12 h-full cursor-pointer flex flex-col items-center pt-3 gap-1 transition-all duration-150 rounded-lg border-2 hover:opacity-80"
        :style="isDark ? 'background-color: #1f2937; border-color: #4b5563;' : 'background-color: #fff; border-color: #111827;'"
        title="Open Inbox ({{ $inboxCount }} inquiries)"
    >
        {{-- Count badge at top --}}
        <span
            class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold"
            style="background-color: {{ $newInboxCount > 0 ? '#ef4444' : '#6b7280' }}; color: #fff;"
        >
            {{ $inboxCount }}
        </span>
        {{-- Inbox icon --}}
        <div :style="isDark ? 'color: #9ca3af;' : 'color: #6b7280;'">
            <x-heroicon-o-inbox class="w-5 h-5" />
        </div>
    </div>

    {{-- Expanded Inbox Panel --}}
    <div
        x-show="inboxOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="flex flex-col h-full min-h-0"
        style="width: 280px; min-width: 280px; max-width: 280px;"
    >
        @php
            $inboxLinearFeet = collect($leads ?? [])->sum('estimated_linear_feet');
        @endphp

        {{-- Header - Black outlined, matches column header height --}}
        <div
            x-data="{ isDark: document.documentElement.classList.contains('dark') }"
            x-init="new MutationObserver(() => isDark = document.documentElement.classList.contains('dark')).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })"
            class="flex items-center justify-between px-4 py-2 rounded-t-lg transition-all duration-150 border-2 border-b-0 min-h-[52px]"
            :style="isDark ? 'background-color: #1f2937; border-color: #4b5563;' : 'background-color: #fff; border-color: #111827;'"
        >
            <div class="flex flex-col">
                <h3 class="font-medium text-sm flex items-center gap-1.5" :style="isDark ? 'color: #fff;' : 'color: #111827;'">
                    <span>Inbox</span>
                    <span :style="isDark ? 'color: #6b7280;' : 'color: #9ca3af;'">/</span>
                    <span :style="isDark ? 'color: #d1d5db;' : 'color: #374151;'">{{ $inboxCount }}</span>
                </h3>
                <span class="text-xs">
                    @if($inboxLinearFeet > 0)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            {{ number_format($inboxLinearFeet, 1) }} LF
                        </span>
                    @else
                        &nbsp;
                    @endif
                </span>
            </div>
            <div class="flex items-center gap-1">
                {{-- Add Lead Button --}}
                <a
                    href="{{ route('filament.admin.resources.leads.create') }}"
                    class="rounded p-1 transition-all duration-100"
                    :style="isDark ? 'color: #9ca3af;' : 'color: #6b7280;'"
                    title="Add new lead"
                >
                    <x-heroicon-m-plus class="w-4 h-4" />
                </a>
                {{-- Collapse Button --}}
                <button
                    @click="toggleInbox()"
                    class="rounded p-1 transition-all duration-100"
                    :style="isDark ? 'color: #9ca3af;' : 'color: #6b7280;'"
                    title="Collapse inbox"
                >
                    <x-heroicon-m-chevron-double-left class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Lead Cards - Black outlined container --}}
        <div
            x-data="{ isDark: document.documentElement.classList.contains('dark') }"
            x-init="new MutationObserver(() => isDark = document.documentElement.classList.contains('dark')).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })"
            class="flex-1 flex flex-col gap-2 p-2 overflow-y-auto min-h-0 border-2 border-t-0"
            :style="isDark ? 'background-color: rgba(31, 41, 55, 0.5); border-color: #4b5563; scrollbar-width: thin; scrollbar-color: transparent transparent;' : 'background-color: #fff; border-color: #111827; scrollbar-width: thin; scrollbar-color: transparent transparent;'"
            onmouseenter="this.style.scrollbarColor = 'rgba(156,163,175,0.3) transparent'"
            onmouseleave="this.style.scrollbarColor = 'transparent transparent'"
        >
            @forelse($leads ?? [] as $lead)
                @include('webkul-project::kanban.cards.lead-card', ['lead' => $lead])
            @empty
                {{-- Empty State --}}
                <x-filament::section class="flex-1">
                    <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 py-8">
                        <x-filament::icon
                            icon="heroicon-o-inbox"
                            class="h-8 w-8 mb-2 opacity-40"
                        />
                        <p class="text-xs">No leads</p>
                    </div>
                </x-filament::section>
            @endforelse
        </div>
    </div>
</div>
@endif
