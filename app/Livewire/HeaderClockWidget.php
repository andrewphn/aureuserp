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
            $this->state = 'working';
            if ($status['clock_in_time']) {
                $today = Carbon::today();
                $clockInTime = Carbon::parse($status['clock_in_time']);
                $this->clockedInTimestamp = $today->setTimeFrom($clockInTime)->timestamp;
                $this->clockedInAt = $status['clock_in_time'];
            }
        } else {
            $this->state = 'not_clocked_in';
            $this->clockedInTimestamp = null;
            $this->clockedInAt = null;
        }

        // Reset lunch state on refresh (lunch is session-only)
        $this->lunchStartTimestamp = null;
        $this->lunchReturnTimestamp = null;
        $this->lunchTaken = false;
        $this->lunchDuration = 0;
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
     * Set lunch duration and start lunch
     */
    public function startLunch(int $minutes): void
    {
        $this->lunchDuration = $minutes;
        $this->selectedLunchDuration = $minutes;
        $this->lunchStartTimestamp = time();
        $this->lunchReturnTimestamp = time() + ($minutes * 60);
        $this->lunchTaken = true; // Mark that lunch was taken
        $this->state = 'on_lunch';
        $this->closeModal();

        $this->notify('On Lunch', "Back in {$minutes} minutes", 'warning');
    }

    /**
     * Return from lunch early
     */
    public function returnFromLunch(): void
    {
        // Calculate actual lunch time taken
        if ($this->lunchStartTimestamp) {
            $actualMinutes = (int) ceil((time() - $this->lunchStartTimestamp) / 60);
            $this->lunchDuration = max(1, $actualMinutes);
        }

        $this->state = 'working';
        $this->lunchStartTimestamp = null;
        $this->lunchReturnTimestamp = null;
        $this->closeModal();

        $this->notify('Back to Work', "Lunch: {$this->lunchDuration} minutes", 'success');
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

        // Only use break time if lunch was actually taken
        $breakMinutes = $this->lunchTaken ? $this->lunchDuration : 0;

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
            $this->notify('Clocked Out', sprintf("%.1f hours today", $hoursWorked), 'success');
        } else {
            $this->notify('Clock Out Failed', $result['error'] ?? 'Unknown error', 'danger');
        }
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
