<?php

namespace Webkul\FullCalendar\Contracts;

/**
 * Has Raw Js interface
 *
 */
interface HasRawJs
{
    /**
     * Event Class Names
     *
     * @return string
     */
    public function eventClassNames(): string;

    /**
     * Event Content
     *
     * @return string
     */
    public function eventContent(): string;

    /**
     * Event Did Mount
     *
     * @return string
     */
    public function eventDidMount(): string;

    /**
     * Event Will Unmount
     *
     * @return string
     */
    public function eventWillUnmount(): string;
}
