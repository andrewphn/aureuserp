<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneTemplate extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_milestone_templates';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'production_stage',
        'is_critical',
        'description',
        'relative_days',
        'sort_order',
        'is_active',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'is_critical' => 'boolean',
        'is_active'   => 'boolean',
        'relative_days' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter templates by production stage
     */
    public function scopeByStage($query, string $stage)
    {
        return $query->where('production_stage', $stage);
    }

    /**
     * Scope to get critical templates
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }
}
