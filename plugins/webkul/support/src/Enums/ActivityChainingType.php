<?php

namespace Webkul\Support\Enums;

/**
 * Activity Chaining Type enumeration
 *
 */
enum ActivityChainingType: string
{
    case SUGGEST = 'suggest';

    case TRIGGER = 'trigger';

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::SUGGEST->value => __('support::enums/activity-chaining-type.suggest'),
            self::TRIGGER->value => __('support::enums/activity-chaining-type.trigger'),
        ];
    }
}
