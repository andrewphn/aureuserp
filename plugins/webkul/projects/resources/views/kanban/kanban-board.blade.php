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
            },

            // Multi-select state
            selectedCards: [],
            lastSelectedId: null,

            // Check if card is selected
            isSelected(id) {
                return this.selectedCards.includes(id);
            },

            // Handle card click with modifier keys
            handleCardClick(id, event) {
                const isCtrlOrCmd = event.ctrlKey || event.metaKey;
                const isShift = event.shiftKey;

                if (isCtrlOrCmd) {
                    // Ctrl/Cmd+Click: Toggle individual selection
                    if (this.isSelected(id)) {
                        this.selectedCards = this.selectedCards.filter(i => i !== id);
                    } else {
                        this.selectedCards.push(id);
                    }
                    this.lastSelectedId = id;
                } else if (isShift && this.lastSelectedId) {
                    // Shift+Click: Range select
                    const allCards = Array.from(document.querySelectorAll('[data-card-id]')).map(el => el.dataset.cardId);
                    const startIdx = allCards.indexOf(this.lastSelectedId.toString());
                    const endIdx = allCards.indexOf(id.toString());

                    if (startIdx !== -1 && endIdx !== -1) {
                        const start = Math.min(startIdx, endIdx);
                        const end = Math.max(startIdx, endIdx);
                        const rangeIds = allCards.slice(start, end + 1);

                        // Add range to selection (union)
                        rangeIds.forEach(rangeId => {
                            if (!this.selectedCards.includes(rangeId)) {
                                this.selectedCards.push(rangeId);
                            }
                        });
                    }
                } else {
                    // Normal click: Clear selection, open quick actions
                    this.selectedCards = [];
                    this.lastSelectedId = id;
                    return true; // Allow default action (open quick actions)
                }

                event.preventDefault();
                event.stopPropagation();
                return false; // Prevent default action
            },

            // Clear all selections
            clearSelection() {
                this.selectedCards = [];
                this.lastSelectedId = null;
            },

            // Select all visible cards
            selectAll() {
                this.selectedCards = Array.from(document.querySelectorAll('[data-card-id]')).map(el => el.dataset.cardId);
            },

            // Get selected count
            get selectedCount() {
                return this.selectedCards.length;
            },

            // Bulk actions
            bulkChangeStage(stageId) {
                if (this.selectedCards.length > 0) {
                    $wire.bulkChangeStage(this.selectedCards, stageId);
                    this.clearSelection();
                }
            },

            bulkMarkBlocked() {
                if (this.selectedCards.length > 0) {
                    $wire.bulkMarkBlocked(this.selectedCards);
                    this.clearSelection();
                }
            },

            bulkUnblock() {
                if (this.selectedCards.length > 0) {
                    $wire.bulkUnblock(this.selectedCards);
                    this.clearSelection();
                }
            }
        }"
        @keydown.escape.window="clearSelection()"
        @keydown.a.window.prevent="if ($event.ctrlKey || $event.metaKey) selectAll()"
        @click.self="clearSelection()"
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

        {{-- Bulk Actions Floating Bar (Multi-Select) --}}
        @include('webkul-project::kanban.components.bulk-actions-bar')
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
