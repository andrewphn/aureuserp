<?php

namespace Webkul\Project\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Task Settings class
 *
 */
class TaskSettings extends Settings
{
    public bool $enable_recurring_tasks;

    public bool $enable_task_dependencies;

    public bool $enable_project_stages;

    public bool $enable_milestones;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'task';
    }
}
