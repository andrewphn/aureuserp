<?php

namespace Webkul\Project\Observers;

use Webkul\Project\Models\HardwareRequirement;
use Webkul\Project\Models\CabinetRun;
use Illuminate\Support\Facades\Log;

/**
 * Hardware Requirement Observer
 *
 * Automatically syncs hardware counts on CabinetRun when hardware requirements
 * are created, updated, or deleted. This keeps the denormalized count columns
 * (hinges_count, slides_count, shelf_pins_count, pullouts_count) in sync with
 * the normalized hardware_requirements table.
 */
class HardwareRequirementObserver
{
    /**
     * Handle the HardwareRequirement "created" event.
     */
    public function created(HardwareRequirement $hardwareRequirement): void
    {
        $this->syncCabinetRunTotals($hardwareRequirement);
    }

    /**
     * Handle the HardwareRequirement "updated" event.
     */
    public function updated(HardwareRequirement $hardwareRequirement): void
    {
        // Check if quantity_required or hardware_type changed
        if ($hardwareRequirement->wasChanged(['quantity_required', 'hardware_type', 'cabinet_run_id'])) {
            $this->syncCabinetRunTotals($hardwareRequirement);

            // If cabinet_run_id changed, also update the old cabinet run
            if ($hardwareRequirement->wasChanged('cabinet_run_id')) {
                $oldCabinetRunId = $hardwareRequirement->getOriginal('cabinet_run_id');
                if ($oldCabinetRunId) {
                    $oldCabinetRun = CabinetRun::find($oldCabinetRunId);
                    if ($oldCabinetRun) {
                        $oldCabinetRun->recalculateHardwareTotals();
                    }
                }
            }
        }
    }

    /**
     * Handle the HardwareRequirement "deleted" event.
     */
    public function deleted(HardwareRequirement $hardwareRequirement): void
    {
        $this->syncCabinetRunTotals($hardwareRequirement);
    }

    /**
     * Handle the HardwareRequirement "restored" event.
     */
    public function restored(HardwareRequirement $hardwareRequirement): void
    {
        $this->syncCabinetRunTotals($hardwareRequirement);
    }

    /**
     * Handle the HardwareRequirement "force deleted" event.
     */
    public function forceDeleted(HardwareRequirement $hardwareRequirement): void
    {
        $this->syncCabinetRunTotals($hardwareRequirement);
    }

    /**
     * Sync the hardware totals on the related CabinetRun
     */
    protected function syncCabinetRunTotals(HardwareRequirement $hardwareRequirement): void
    {
        if (!$hardwareRequirement->cabinet_run_id) {
            return;
        }

        try {
            $cabinetRun = CabinetRun::find($hardwareRequirement->cabinet_run_id);

            if ($cabinetRun) {
                $cabinetRun->recalculateHardwareTotals();

                Log::debug('Hardware totals recalculated', [
                    'cabinet_run_id' => $cabinetRun->id,
                    'hinges_count' => $cabinetRun->hinges_count,
                    'slides_count' => $cabinetRun->slides_count,
                    'shelf_pins_count' => $cabinetRun->shelf_pins_count,
                    'pullouts_count' => $cabinetRun->pullouts_count,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync cabinet run hardware totals', [
                'hardware_requirement_id' => $hardwareRequirement->id,
                'cabinet_run_id' => $hardwareRequirement->cabinet_run_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
