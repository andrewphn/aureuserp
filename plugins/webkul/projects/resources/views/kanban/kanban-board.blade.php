@php
    // View mode from controller
    $currentViewMode = $viewMode ?? 'projects';

    // Layout settings
    $compactFilters = $layoutSettings['compact_filters'] ?? true;
    $showKpiRow = $layoutSettings['show_kpi_row'] ?? false;
    $showChart = $layoutSettings['show_chart'] ?? false;

    // Exclude "To Do" from workflow stages - leads are the inbox now (only for projects)
    $boardStatuses = $currentViewMode === 'projects'
        ? $statuses->reject(fn($s) => $s['title'] === 'To Do')
        : $statuses;

    // Leads are now the inbox (only shown in projects mode)
    $inboxOpen = $this->leadsInboxOpen ?? true;
    $inboxCount = $leadsCount ?? 0;
    $newInboxCount = $newLeadsCount ?? 0;
@endphp

<x-filament-panels::page class="!p-0">
    {{-- Single Root Wrapper for Livewire --}}
    <div class="kanban-wrapper">
        {{-- Control Bar (extracted to partial for maintainability) --}}
        @include('webkul-project::kanban.partials.control-bar')

    {{-- Main Kanban Board - Full Height --}}
    <div
        x-data="{
            inboxOpen: {{ $inboxOpen ? 'true' : 'false' }},
            hasNewItems: {{ $newInboxCount > 0 ? 'true' : 'false' }},
            toggleInbox() {
                this.inboxOpen = !this.inboxOpen;
                $wire.leadsInboxOpen = this.inboxOpen;
            }
        }"
        class="h-[calc(100vh-180px)]"
    >
        {{-- Single Flex Container for ALL columns (Inbox + Workflow Stages) --}}
        <div
            wire:ignore.self
            class="flex gap-3 h-full min-h-0 overflow-x-auto overflow-y-hidden px-3 py-2"
            style="scrollbar-width: thin;"
        >
            {{-- Inbox Column (extracted to partial for maintainability) --}}
            @include('webkul-project::kanban.partials.inbox-column')

            {{-- Workflow Stage Columns --}}
            @foreach($boardStatuses as $status)
                @include(static::$statusView)
            @endforeach

            <div wire:ignore>
                @include(static::$scriptsView)
            </div>
        </div>
    </div>

    {{-- Edit Record Modal (from package) --}}
    @unless($disableEditModal)
        <x-filament-kanban::edit-record-modal/>
    @endunless

    {{-- Modals (extracted to separate files for maintainability) --}}
    @include('webkul-project::kanban.modals.chatter-modal')
    @include('webkul-project::kanban.modals.quick-actions-modal')
    @include('webkul-project::kanban.modals.lead-detail-modal')

    </div>{{-- Close kanban-wrapper --}}
</x-filament-panels::page>
