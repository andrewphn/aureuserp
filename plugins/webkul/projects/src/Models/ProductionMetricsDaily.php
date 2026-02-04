<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Support\Models\Company;

/**
 * Production Metrics Daily Model
 *
 * Stores aggregated daily production metrics from CNC operations.
 * Enables historical trending and capacity planning without
 * expensive real-time calculations.
 *
 * @property int $id
 * @property \Carbon\Carbon $metrics_date
 * @property int|null $company_id
 * @property int $sheets_completed
 * @property int $parts_completed
 * @property float $board_feet
 * @property float $sqft_processed
 * @property float|null $utilization_avg
 * @property float $waste_sqft
 * @property array|null $operator_breakdown
 * @property array|null $material_breakdown
 * @property int $total_run_minutes
 * @property float|null $avg_minutes_per_sheet
 * @property int $programs_completed
 * @property float|null $sheets_per_hour
 * @property float|null $bf_per_hour
 * @property \Carbon\Carbon|null $computed_at
 * @property bool $is_complete
 */
class ProductionMetricsDaily extends Model
{
    protected $table = 'projects_production_metrics_daily';

    protected $fillable = [
        'metrics_date',
        'company_id',
        'sheets_completed',
        'parts_completed',
        'board_feet',
        'sqft_processed',
        'utilization_avg',
        'waste_sqft',
        'operator_breakdown',
        'material_breakdown',
        'total_run_minutes',
        'avg_minutes_per_sheet',
        'programs_completed',
        'sheets_per_hour',
        'bf_per_hour',
        'computed_at',
        'is_complete',
    ];

    protected $casts = [
        'metrics_date' => 'date',
        'board_feet' => 'decimal:2',
        'sqft_processed' => 'decimal:2',
        'utilization_avg' => 'decimal:2',
        'waste_sqft' => 'decimal:2',
        'operator_breakdown' => 'array',
        'material_breakdown' => 'array',
        'avg_minutes_per_sheet' => 'decimal:2',
        'sheets_per_hour' => 'decimal:2',
        'bf_per_hour' => 'decimal:2',
        'computed_at' => 'datetime',
        'is_complete' => 'boolean',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metrics_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to get only complete days
     */
    public function scopeComplete($query)
    {
        return $query->where('is_complete', true);
    }

    /**
     * Scope to get days with production
     */
    public function scopeWithProduction($query)
    {
        return $query->where('sheets_completed', '>', 0);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get total run time in hours
     */
    public function getTotalRunHoursAttribute(): float
    {
        return round($this->total_run_minutes / 60, 2);
    }

    /**
     * Get day of week name
     */
    public function getDayOfWeekAttribute(): string
    {
        return $this->metrics_date->format('l');
    }

    /**
     * Get formatted date for display
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->metrics_date->format('M j, Y');
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Find or create a record for a specific date
     */
    public static function findOrCreateForDate($date, $companyId = null): self
    {
        return static::firstOrCreate(
            [
                'metrics_date' => $date,
                'company_id' => $companyId,
            ],
            [
                'sheets_completed' => 0,
                'parts_completed' => 0,
                'board_feet' => 0,
                'sqft_processed' => 0,
                'waste_sqft' => 0,
                'total_run_minutes' => 0,
                'programs_completed' => 0,
            ]
        );
    }

    /**
     * Get summary statistics for a date range
     */
    public static function getSummaryForRange($startDate, $endDate, $companyId = null): array
    {
        $query = static::dateRange($startDate, $endDate)
            ->withProduction();

        if ($companyId) {
            $query->forCompany($companyId);
        }

        $metrics = $query->get();

        if ($metrics->isEmpty()) {
            return [
                'total_sheets' => 0,
                'total_parts' => 0,
                'total_board_feet' => 0,
                'working_days' => 0,
                'avg_sheets_per_day' => 0,
                'avg_bf_per_day' => 0,
                'avg_utilization' => null,
            ];
        }

        $totalSheets = $metrics->sum('sheets_completed');
        $workingDays = $metrics->count();

        return [
            'total_sheets' => $totalSheets,
            'total_parts' => $metrics->sum('parts_completed'),
            'total_board_feet' => round($metrics->sum('board_feet'), 2),
            'working_days' => $workingDays,
            'avg_sheets_per_day' => round($totalSheets / $workingDays, 1),
            'avg_bf_per_day' => round($metrics->sum('board_feet') / $workingDays, 2),
            'avg_utilization' => $metrics->avg('utilization_avg') !== null ? round($metrics->avg('utilization_avg'), 1) : null,
        ];
    }
}
