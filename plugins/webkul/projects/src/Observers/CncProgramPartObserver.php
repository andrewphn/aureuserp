<?php

namespace Webkul\Project\Observers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Jobs\AggregateDailyProductionMetricsJob;
use Webkul\Project\Models\CncProgramPart;

/**
 * CNC Program Part Observer
 *
 * Observes CncProgramPart model changes and triggers production
 * metrics re-aggregation when parts are completed.
 *
 * Uses debouncing (5 minute window) to prevent excessive job dispatches
 * when multiple parts complete in quick succession.
 */
class CncProgramPartObserver
{
    /**
     * Cache key prefix for debouncing
     */
    protected const DEBOUNCE_KEY_PREFIX = 'production_metrics_debounce_';

    /**
     * Debounce window in seconds
     */
    protected const DEBOUNCE_SECONDS = 300; // 5 minutes

    /**
     * Handle the CncProgramPart "updated" event.
     */
    public function updated(CncProgramPart $part): void
    {
        // Only trigger on status change to complete
        if (!$part->wasChanged('status')) {
            return;
        }

        if ($part->status !== CncProgramPart::STATUS_COMPLETE) {
            return;
        }

        $this->scheduleAggregation($part);
    }

    /**
     * Handle the CncProgramPart "created" event.
     *
     * In case a part is created already complete (e.g., import)
     */
    public function created(CncProgramPart $part): void
    {
        if ($part->status !== CncProgramPart::STATUS_COMPLETE) {
            return;
        }

        $this->scheduleAggregation($part);
    }

    /**
     * Schedule aggregation with debouncing
     */
    protected function scheduleAggregation(CncProgramPart $part): void
    {
        $completedAt = $part->completed_at ?? now();
        $date = $completedAt instanceof Carbon ? $completedAt : Carbon::parse($completedAt);
        $dateString = $date->toDateString();

        $cacheKey = self::DEBOUNCE_KEY_PREFIX . $dateString;

        // Check if we've already scheduled aggregation for this date recently
        if (Cache::has($cacheKey)) {
            Log::debug('Production metrics aggregation already scheduled', [
                'date' => $dateString,
                'part_id' => $part->id,
            ]);
            return;
        }

        // Set debounce flag
        Cache::put($cacheKey, true, self::DEBOUNCE_SECONDS);

        // Dispatch delayed job (5 minutes from now)
        AggregateDailyProductionMetricsJob::dispatch(Carbon::parse($dateString))
            ->delay(now()->addSeconds(self::DEBOUNCE_SECONDS));

        Log::info('Scheduled production metrics aggregation', [
            'date' => $dateString,
            'part_id' => $part->id,
            'delay_seconds' => self::DEBOUNCE_SECONDS,
        ]);
    }
}
