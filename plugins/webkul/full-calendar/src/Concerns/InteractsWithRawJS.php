<?php

namespace Webkul\FullCalendar\Concerns;

/**
 * Interacts With Raw JS trait
 *
 */
trait InteractsWithRawJS
{
    /**
     * Event Class Names
     *
     * @return string
     */
    public function eventClassNames(): string
    {
        return <<<'JS'
            null
        JS;
    }

    /**
     * Event Content
     *
     * @return string
     */
    public function eventContent(): string
    {
        return <<<'JS'
            null
        JS;
    }

    /**
     * Event Did Mount
     *
     * @return string
     */
    public function eventDidMount(): string
    {
        return <<<'JS'
            null
        JS;
    }

    /**
     * Event Will Unmount
     *
     * @return string
     */
    public function eventWillUnmount(): string
    {
        return <<<'JS'
            null
        JS;
    }
}
