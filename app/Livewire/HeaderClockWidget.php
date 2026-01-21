<?php

namespace App\Livewire;

use App\Services\ClockingService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Header Clock Widget
 *
 * Displays a clock in/out button in the Filament admin header.
 * States: not_clocked_in → working → on_lunch → working → clocked_out
 */
class HeaderClockWidget extends Component
{
    // Clock state
    public string $state = 'not_clocked_in'; // not_clocked_in, working, on_lunch
    public ?int $clockedInTimestamp = null;
    public ?string $clockedInAt = null;

    // Lunch state
    public ?int $lunchStartTimestamp = null;
    public int $lunchDuration = 0; // minutes - only set when lunch is actually taken
    public ?int $lunchReturnTimestamp = null;
    public bool $lunchTaken = false; // Track if lunch was actually taken

    // Modal state
    public bool $showModal = false;
    public bool $showLunchDurationPicker = false;
    public int $selectedLunchDuration = 60;

    protected ClockingService $clockingService;

    public function boot(ClockingService $clockingService): void
    {
        $this->clockingService = $clockingService;
    }

    public function mount(): void
    {
        $this->refreshStatus();
    }

    /**
     * Refresh the clock status from the database
     */
    public function refreshStatus(): void
    {
        $userId = auth()->id();
        if (!$userId) {
            return;
        }

        $status = $this->clockingService->getStatus($userId);

        if ($status['is_clocked_in']) {
            // Check if on lunch
            if ($status['is_on_lunch'] ?? false) {
                $this->state = 'on_lunch';
                if ($status['lunch_start_time']) {
                    $today = Carbon::today();
                    $lunchStartTime = Carbon::parse($status['lunch_start_time']);
                    $this->lunchStartTimestamp = $today->setTimeFrom($lunchStartTime)->timestamp;
                }
            } else {
                $this->state = 'working';
            }

            if ($status['clock_in_time']) {
                $today = Carbon::today();
                $clockInTime = Carbon::parse($status['clock_in_time']);
                $this->clockedInTimestamp = $today->setTimeFrom($clockInTime)->timestamp;
                $this->clockedInAt = $status['clock_in_time'];
            }

            // Check if lunch was already taken
            $this->lunchTaken = $status['lunch_taken'] ?? false;
            if ($this->lunchTaken && $status['lunch_start_time'] && $status['lunch_end_time']) {
                $lunchStart = Carbon::parse($status['lunch_start_time']);
                $lunchEnd = Carbon::parse($status['lunch_end_time']);
                $this->lunchDuration = $lunchStart->diffInMinutes($lunchEnd, false);
            }
        } else {
            $this->state = 'not_clocked_in';
            $this->clockedInTimestamp = null;
            $this->clockedInAt = null;
            $this->lunchStartTimestamp = null;
            $this->lunchReturnTimestamp = null;
            $this->lunchTaken = false;
            $this->lunchDuration = 0;
        }
    }

    /**
     * Clock in the current user
     */
    public function clockIn(): void
    {
        $userId = auth()->id();
        if (!$userId) {
            $this->notify('Not logged in', null, 'danger');
            return;
        }

        $result = $this->clockingService->clockIn($userId);

        if ($result['success']) {
            $this->refreshStatus();
            $this->notify('Clocked In', "Started at {$result['clock_in_time']}", 'success');
        } else {
            $this->notify('Clock In Failed', $result['error'] ?? 'Unknown error', 'danger');
        }
    }

    /**
     * Open the action modal (when clicking the timer)
     */
    public function openModal(): void
    {
        $this->showModal = true;
        $this->showLunchDurationPicker = false;
    }

    /**
     * Close the modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->showLunchDurationPicker = false;
    }

    /**
     * Show lunch duration picker
     */
    public function showLunchPicker(): void
    {
        $this->showLunchDurationPicker = true;
    }

    /**
     * Start lunch break (database-backed)
     */
    public function startLunch(int $minutes = 60): void
    {
        $userId = auth()->id();
        if (!$userId) {
            $this->notify('Not logged in', null, 'danger');
            return;
        }

        $result = $this->clockingService->startLunch($userId);

        if ($result['success']) {
            $this->selectedLunchDuration = $minutes;
            $this->lunchStartTimestamp = time();
            $this->lunchReturnTimestamp = time() + ($minutes * 60);
            $this->state = 'on_lunch';
            $this->closeModal();

            $this->notify('On Lunch', "Started at {$result['lunch_start_time']}", 'warning');
        } else {
            $this->notify('Lunch Failed', $result['message'] ?? 'Unknown error', 'danger');
        }
    }

    /**
     * Return from lunch (database-backed)
     */
    public function returnFromLunch(): void
    {
        $userId = auth()->id();
        if (!$userId) {
            $this->notify('Not logged in', null, 'danger');
            return;
        }

        $result = $this->clockingService->endLunch($userId);

        if ($result['success']) {
            $this->lunchDuration = $result['lunch_duration_minutes'] ?? 0;
            $this->lunchTaken = true;
            $this->state = 'working';
            $this->lunchStartTimestamp = null;
            $this->lunchReturnTimestamp = null;
            $this->closeModal();

            $this->notify('Back to Work', "Lunch: {$this->lunchDuration} minutes", 'success');
        } else {
            $this->notify('Return Failed', $result['message'] ?? 'Unknown error', 'danger');
        }
    }

    /**
     * Clock out the current user
     */
    public function clockOut(): void
    {
        $userId = auth()->id();
        if (!$userId) {
            $this->notify('Not logged in', null, 'danger');
            return;
        }

        // Get current status to check if lunch was tracked in database
        $status = $this->clockingService->getStatus($userId);

        // Use database-tracked lunch duration if available, otherwise use session value
        if ($status['lunch_taken'] ?? false) {
            // Lunch was tracked in database - the break_duration_minutes is already set
            // Pass the duration that was recorded when endLunch was called
            $breakMinutes = $this->lunchDuration;
        } else {
            // No lunch tracked - use 0
            $breakMinutes = 0;
        }

        $result = $this->clockingService->clockOut($userId, $breakMinutes);

        if ($result['success']) {
            $this->state = 'not_clocked_in';
            $this->clockedInTimestamp = null;
            $this->clockedInAt = null;
            $this->lunchStartTimestamp = null;
            $this->lunchReturnTimestamp = null;
            $this->lunchDuration = 0;
            $this->lunchTaken = false;
            $this->closeModal();

            $hoursWorked = $result['hours_worked'] ?? 0;
            $this->notify('Clocked Out', sprintf("%s today", $this->formatHours($hoursWorked)), 'success');
        } else {
            $this->notify('Clock Out Failed', $result['error'] ?? 'Unknown error', 'danger');
        }
    }

    /**
     * Format decimal hours to hours and minutes display
     * Example: 8.5 -> "8h 30m", 8.0 -> "8h"
     */
    protected function formatHours($hours): string
    {
        // Handle null, empty, or non-numeric values
        if ($hours === null || $hours === '' || !is_numeric($hours)) {
            return '0h';
        }

        $hours = (float) $hours;

        if ($hours === 0.0) {
            return '0h';
        }

        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);

        if ($minutes > 0) {
            return "{$wholeHours}h {$minutes}m";
        }

        return "{$wholeHours}h";
    }

    /**
     * Send a notification with 3 second duration
     */
    protected function notify(string $title, ?string $body, string $status): void
    {
        $notification = Notification::make()
            ->title($title)
            ->duration(3000); // 3 seconds

        if ($body) {
            $notification->body($body);
        }

        match ($status) {
            'success' => $notification->success(),
            'danger' => $notification->danger(),
            'warning' => $notification->warning(),
            default => $notification->info(),
        };

        $notification->send();
    }

    public function render(): View
    {
        return view('livewire.header-clock-widget');
    }
}
