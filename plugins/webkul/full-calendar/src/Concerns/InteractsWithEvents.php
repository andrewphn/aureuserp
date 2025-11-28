<?php

namespace Webkul\FullCalendar\Concerns;

use Carbon\Carbon;
use Webkul\FullCalendar\FullCalendarPlugin;

/**
 * Interacts With Events trait
 *
 */
trait InteractsWithEvents
{
    /**
     * On Event Click
     *
     * @param array $event
     * @return void
     */
    public function onEventClick(array $event): void
    {
        if ($this->getModel()) {
            $this->record = $this->resolveRecord($event['id']);
        }

        $this->mountAction('view', [
            'type'  => 'click',
            'event' => $event,
        ]);
    }

    /**
     * On Event Drop
     *
     * @param array $event
     * @param array $oldEvent
     * @param array $relatedEvents
     * @param array $delta
     * @param ?array $oldResource
     * @param ?array $newResource
     * @return bool
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        if ($this->getModel()) {
            $this->record = $this->resolveRecord($event['id']);
        }

        $this->mountAction('edit', [
            'type'          => 'drop',
            'event'         => $event,
            'oldEvent'      => $oldEvent,
            'relatedEvents' => $relatedEvents,
            'delta'         => $delta,
            'oldResource'   => $oldResource,
            'newResource'   => $newResource,
        ]);

        return false;
    }

    /**
     * On Event Resize
     *
     * @param array $event
     * @param array $oldEvent
     * @param array $relatedEvents
     * @param array $startDelta
     * @param array $endDelta
     * @return bool
     */
    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $startDelta, array $endDelta): bool
    {
        if ($this->getModel()) {
            $this->record = $this->resolveRecord($event['id']);
        }

        $this->mountAction('edit', [
            'type'          => 'resize',
            'event'         => $event,
            'oldEvent'      => $oldEvent,
            'relatedEvents' => $relatedEvents,
            'startDelta'    => $startDelta,
            'endDelta'      => $endDelta,
        ]);

        return false;
    }

    /**
     * On Date Select
     *
     * @param string $start
     * @param ?string $end
     * @param bool $allDay
     * @param ?array $view
     * @param ?array $resource
     * @return void
     */
    public function onDateSelect(string $start, ?string $end, bool $allDay, ?array $view, ?array $resource): void
    {
        [$start, $end] = $this->calculateTimezoneOffset($start, $end, $allDay);

        $this->mountAction('create', [
            'type'     => 'select',
            'start'    => $start,
            'end'      => $end,
            'allDay'   => $allDay,
            'resource' => $resource,
        ]);
    }

    /**
     * Refresh Records
     *
     * @return void
     */
    public function refreshRecords(): void
    {
        $this->records = null;

        $this->dispatch('full-calendar--refresh');
    }

    /**
     * Calculate Timezone Offset
     *
     * @param string $start
     * @param ?string $end
     * @param bool $allDay
     * @return array
     */
    protected function calculateTimezoneOffset(string $start, ?string $end, bool $allDay): array
    {
        $timezone = FullCalendarPlugin::make()->getTimezone();

        $start = Carbon::parse($start, $timezone);

        if ($end) {
            $end = Carbon::parse($end, $timezone);
        }

        if (
            ! is_null($end)
            && $allDay
        ) {
            $end->subDay()->endOfDay();
        }

        return [$start, $end, $allDay];
    }
}
