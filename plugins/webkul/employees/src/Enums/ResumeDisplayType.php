<?php

namespace Webkul\Employee\Enums;

/**
 * Resume Display Type enumeration
 *
 */
enum ResumeDisplayType: string
{
    case Classic = 'classic';

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::Classic->value => __('employees::enums/resume-display-type.classic'),
        ];
    }
}
