<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Project\Models\Project;
use Webkul\Support\Models\Company;

class ProductionEstimate extends Model
{
    use HasChatter, HasLogActivity;

    protected $table = 'projects_production_estimates';

    protected $fillable = [
        'project_id',
        'company_id',
        'linear_feet',
        'estimated_hours',
        'estimated_days',
        'estimated_weeks',
        'estimated_months',
        'shop_capacity_per_day',
        'shop_capacity_per_hour',
        'working_hours_per_day',
        'working_days_per_week',
        'working_days_per_month',
        'company_acronym',
        'formatted_estimate',
        'is_current',
    ];

    protected $casts = [
        'linear_feet' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'estimated_days' => 'decimal:2',
        'estimated_weeks' => 'decimal:2',
        'estimated_months' => 'decimal:2',
        'shop_capacity_per_day' => 'decimal:2',
        'shop_capacity_per_hour' => 'decimal:2',
        'working_hours_per_day' => 'integer',
        'working_days_per_week' => 'integer',
        'working_days_per_month' => 'integer',
        'is_current' => 'boolean',
    ];

    /**
     * Get the project that owns this estimate
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the company this estimate is for
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Create a production estimate from calculator result
     */
    public static function createFromEstimate(int $projectId, int $companyId, float $linearFeet, array $estimate): self
    {
        // Mark all previous estimates for this project as not current
        static::where('project_id', $projectId)->update(['is_current' => false]);

        $productionEstimate = static::create([
            'project_id' => $projectId,
            'company_id' => $companyId,
            'linear_feet' => $linearFeet,
            'estimated_hours' => $estimate['hours'],
            'estimated_days' => $estimate['days'],
            'estimated_weeks' => $estimate['weeks'],
            'estimated_months' => $estimate['months'],
            'shop_capacity_per_day' => $estimate['shop_capacity_per_day'],
            'shop_capacity_per_hour' => $estimate['shop_capacity_per_hour'],
            'working_hours_per_day' => $estimate['working_hours_per_day'],
            'working_days_per_week' => $estimate['working_days_per_week'],
            'working_days_per_month' => $estimate['working_days_per_month'],
            'company_acronym' => $estimate['details']['company_acronym'] ?? null,
            'formatted_estimate' => $estimate['formatted'],
            'is_current' => true,
        ]);

        // Log activity for the project
        // TODO: Fix activity helper - currently causing "Call to undefined function App\Models\activity()" error
        // $project = Project::find($projectId);
        // if ($project) {
        //     activity()
        //         ->performedOn($project)
        //         ->withProperties([
        //             'linear_feet' => $linearFeet,
        //             'estimate' => $estimate['formatted'],
        //             'hours' => $estimate['hours'],
        //             'days' => $estimate['days'],
        //             'weeks' => $estimate['weeks'],
        //             'months' => $estimate['months'],
        //         ])
        //         ->log("Production estimate updated: {$linearFeet} LF → {$estimate['formatted']}");
        // }

        return $productionEstimate;
    }

    /**
     * Get the current estimate for a project
     */
    public static function getCurrentForProject(int $projectId): ?self
    {
        return static::where('project_id', $projectId)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Get all estimates for a project (history)
     */
    public static function getHistoryForProject(int $projectId)
    {
        return static::where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
