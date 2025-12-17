<?php

namespace Webkul\Project\Enums;

use Filament\Support\Contracts;

/**
 * Task State enumeration
 *
 */
enum TaskState: string implements Contracts\HasColor, Contracts\HasIcon, Contracts\HasLabel
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case CHANGE_REQUESTED = 'change_requested';
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';
    case DONE = 'done';

    public function getLabel(): string
    {
        return self::options()[$this->value];
    }

    public function getIcon(): ?string
    {
        return self::icons()[$this->value] ?? null;
    }

    public function getColor(): ?string
    {
        return self::colors()[$this->value] ?? null;
    }

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::PENDING->value          => __('webkul-project::enums/task-state.pending'),
            self::IN_PROGRESS->value      => __('webkul-project::enums/task-state.in-progress'),
            self::CHANGE_REQUESTED->value => __('webkul-project::enums/task-state.change-requested'),
            self::APPROVED->value         => __('webkul-project::enums/task-state.approved'),
            self::CANCELLED->value        => __('webkul-project::enums/task-state.cancelled'),
            self::DONE->value             => __('webkul-project::enums/task-state.done'),
        ];
    }

    /**
     * Icons
     *
     * @return array
     */
    public static function icons(): array
    {
        return [
            self::PENDING->value          => 'heroicon-o-clock',
            self::IN_PROGRESS->value      => 'heroicon-m-play-circle',
            self::CHANGE_REQUESTED->value => 'heroicon-s-exclamation-circle',
            self::APPROVED->value         => 'heroicon-o-check-circle',
            self::CANCELLED->value        => 'heroicon-s-x-circle',
            self::DONE->value             => 'heroicon-c-check-circle',
        ];
    }

    /**
     * Colors
     *
     * @return array
     */
    public static function colors(): array
    {
        return [
            self::PENDING->value          => 'info',
            self::IN_PROGRESS->value      => 'gray',
            self::CHANGE_REQUESTED->value => 'warning',
            self::APPROVED->value         => 'success',
            self::CANCELLED->value        => 'danger',
            self::DONE->value             => 'success',
        ];
    }
}
