<?php

namespace Webkul\Project\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Services\ProductionMetricsAggregationService;

/**
 * Aggregate Daily Production Metrics Job
 *
 * Aggregates CNC production data for a specific date into the
 * projects_production_metrics_daily table.
 *
 * Can be triggered by:
 * - CncProgramPartObserver (debounced on part completion)
 * - Scheduled command (daily at 1:00 AM and 6:00 AM)
 * - Manual artisan command
 */
class AggregateDailyProductionMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public array $backoff = [60, 180, 300];

    /**
     * The date to aggregate metrics for
     */
    public Carbon $date;

    /**
     * Company ID (optional, for multi-tenant)
     */
    public ?int $companyId;

    /**
     * Create a new job instance.
     */
    public function __construct(Carbon $date, ?int $companyId = null)
    {
        $this->date = $date;
        $this->companyId = $companyId;
    }

    /**
     * Execute the job.
     */
    public function handle(ProductionMetricsAggregationService $service): void
    {
        Log::info('Starting production metrics aggregation job', [
            'date' => $this->date->toDateString(),
            'company_id' => $this->companyId,
        ]);

        try {
            $metrics = $service->aggregateForDate($this->date, $this->companyId);

            Log::info('Production metrics aggregation completed', [
                'date' => $this->date->toDateString(),
                'sheets_completed' => $metrics->sheets_completed,
                'parts_completed' => $metrics->parts_completed,
                'board_feet' => $metrics->board_feet,
            ]);
        } catch (\Exception $e) {
            Log::error('Production metrics aggregation failed', [
                'date' => $this->date->toDateString(),
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the unique ID for the job.
     * Prevents duplicate jobs for the same date.
     */
    public function uniqueId(): string
    {
        return 'production-metrics-' . $this->date->toDateString() . '-' . ($this->companyId ?? 'all');
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public function uniqueFor(): int
    {
        return 300; // 5 minutes
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Production metrics aggregation job failed permanently', [
            'date' => $this->date->toDateString(),
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
