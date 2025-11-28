<?php

namespace Webkul\FullCalendar\Contracts;

/**
 * Has Events interface
 *
 */
interface HasEvents
{
    /**
     * On Event Click
     *
     * @param array $event
     * @return void
     */
    public function onEventClick(array $event): void;

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
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool;

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
    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $startDelta, array $endDelta): bool;

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
    public function onDateSelect(string $start, ?string $end, bool $allDay, ?array $view, ?array $resource): void;
}
