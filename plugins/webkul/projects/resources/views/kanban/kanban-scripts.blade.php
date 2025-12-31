@php
    // Use the filtered boardStatuses (excludes "To Do") that matches the rendered columns
    $boardStatuses = $statuses->reject(fn($s) => $s['title'] === 'To Do');
@endphp
<script>
    // Track dragging state for multi-select
    let isDraggingMultiple = false;
    let draggedCards = [];

    function getSelectedCards() {
        // Get the Alpine component that has the selection state
        const kanbanBoard = document.querySelector('[x-data*="selectedCards"]');
        if (kanbanBoard && kanbanBoard.__x) {
            return kanbanBoard.__x.$data.selectedCards || [];
        }
        // Fallback: check for Alpine.js $data
        if (window.Alpine) {
            const boardEl = document.querySelector('.h-\\[calc\\(100vh-180px\\)\\]');
            if (boardEl && boardEl._x_dataStack) {
                const data = boardEl._x_dataStack[0];
                return data?.selectedCards || [];
            }
        }
        return [];
    }

    function clearSelection() {
        const kanbanBoard = document.querySelector('.h-\\[calc\\(100vh-180px\\)\\]');
        if (kanbanBoard && kanbanBoard._x_dataStack) {
            const data = kanbanBoard._x_dataStack[0];
            if (data) {
                data.selectedCards = [];
                data.lastSelectedId = null;
            }
        }
    }

    function onStart(e) {
        setTimeout(() => document.body.classList.add("grabbing"));

        // Check if dragged item is part of a multi-selection
        const draggedId = e.item.id;
        const selectedCards = getSelectedCards();

        if (selectedCards.length > 1 && selectedCards.includes(draggedId)) {
            // Multi-drag mode
            isDraggingMultiple = true;
            draggedCards = [...selectedCards];

            // Add visual indicator to all selected cards
            draggedCards.forEach(cardId => {
                const card = document.getElementById(cardId);
                if (card && cardId !== draggedId) {
                    card.classList.add('opacity-50', 'scale-95', 'transition-all');
                }
            });

            // Show count badge on dragged item
            const badge = document.createElement('div');
            badge.id = 'multi-drag-badge';
            badge.className = 'absolute -top-2 -right-2 bg-primary-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center z-50';
            badge.textContent = draggedCards.length;
            e.item.style.position = 'relative';
            e.item.appendChild(badge);
        } else {
            isDraggingMultiple = false;
            draggedCards = [draggedId];
        }
    }

    function onEnd(e) {
        document.body.classList.remove("grabbing");

        // Remove multi-drag visual indicators
        if (isDraggingMultiple) {
            draggedCards.forEach(cardId => {
                const card = document.getElementById(cardId);
                if (card) {
                    card.classList.remove('opacity-50', 'scale-95', 'transition-all');
                }
            });

            // Remove count badge
            const badge = document.getElementById('multi-drag-badge');
            if (badge) badge.remove();
        }

        isDraggingMultiple = false;
        draggedCards = [];
    }

    function setData(dataTransfer, el) {
        // Include all selected card IDs if multi-dragging
        const selectedCards = getSelectedCards();
        if (selectedCards.length > 1 && selectedCards.includes(el.id)) {
            dataTransfer.setData('ids', JSON.stringify(selectedCards));
        } else {
            dataTransfer.setData('id', el.id);
        }
    }

    function onAdd(e) {
        const status = e.to.dataset.statusId;
        const fromOrderedIds = [].slice.call(e.from.children).map(child => child.id);
        const toOrderedIds = [].slice.call(e.to.children).map(child => child.id);

        if (isDraggingMultiple && draggedCards.length > 1) {
            // Bulk move all selected cards
            const recordIds = draggedCards.map(id => parseInt(id, 10));

            // Use bulk change stage for multi-select
            Livewire.dispatch('bulk-status-changed', {
                recordIds: recordIds,
                status: parseInt(status, 10),
                fromOrderedIds,
                toOrderedIds
            });

            // Clear selection after bulk move
            clearSelection();
        } else {
            // Single card move
            const recordId = e.item.id;
            Livewire.dispatch('status-changed', {recordId, status, fromOrderedIds, toOrderedIds});
        }
    }

    function onUpdate(e) {
        const recordId = e.item.id
        const status = e.from.dataset.statusId
        const orderedIds = [].slice.call(e.from.children).map(child => child.id)

        Livewire.dispatch('sort-changed', {recordId, status, orderedIds})
    }

    document.addEventListener('livewire:navigated', () => {
        // Use filtered statuses that match the rendered columns (excludes "To Do")
        const statuses = @js($boardStatuses->pluck('id')->values()->toArray());

        statuses.forEach(status => {
            const el = document.querySelector(`[data-status-id='${status}']`);
            if (el) {
                Sortable.create(el, {
                    group: 'filament-kanban',
                    ghostClass: 'opacity-50',
                    animation: 150,

                    onStart,
                    onEnd,
                    onUpdate,
                    setData,
                    onAdd,
                })
            }
        })
    })
</script>
