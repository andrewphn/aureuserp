<?php

namespace App\Livewire;

use App\Services\ClockingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

/**
 * Time Clock Kiosk - Shop Floor Interface
 *
 * Touch-friendly interface for employees to clock in/out
 * from a shared tablet in the shop.
 *
 * Security Features:
 * - IP restriction (middleware)
 * - Employee PIN verification (4 digits)
 *
 * Features:
 * - Clock in/out with single tap
 * - Lunch duration selection
 * - Project selection on clock out
 * - Today's attendance summary
 */
class TimeClockKiosk extends Component
{
    // Mode: 'select' (choose employee), 'pin' (enter PIN), 'clock' (clock in/out), 'confirmed' (clock in confirmation)
    public string $mode = 'select';

    // Currently selected user
    public ?int $selectedUserId = null;
    public ?string $selectedUserName = null;
    public ?int $selectedEmployeeId = null;

    // PIN verification
    public string $pin = '';
    public bool $pinVerified = false;
    public int $pinAttempts = 0;
    public const MAX_PIN_ATTEMPTS = 3;

    // Clock state
    public bool $isClockedIn = false;
    public ?string $clockedInAt = null;

    // Lunch state
    public bool $isOnLunch = false;
    public bool $lunchTaken = false;
    public ?string $lunchStartTime = null;
    public ?string $lunchEndTime = null;

    // Form inputs
    public int $breakDurationMinutes = 60;
    public ?int $selectedProjectId = null;

    // Status message
    public string $statusMessage = '';
    public string $statusType = 'info'; // 'success', 'error', 'info'

    // Available employees for selection (locked to prevent checksum issues)
    #[Locked]
    public array $employees = [];

    // Available projects (locked to prevent checksum issues)
    #[Locked]
    public array $projects = [];

    // Today's attendance summary (not locked - dynamic data that changes)
    public array $todayAttendance = [];

    protected ClockingService $clockingService;

    public function boot(ClockingService $clockingService): void
    {
        $this->clockingService = $clockingService;
    }

    public function mount(): void
    {
        $this->loadEmployees();
        $this->loadProjects();
        $this->loadTodayAttendance();
    }

    /**
     * Check if PIN is required
     */
    public function isPinRequired(): bool
    {
        return config('kiosk.pin_required', true);
    }

    /**
     * Get PIN length requirement
     */
    public function getPinLength(): int
    {
        return config('kiosk.pin_length', 4);
    }

    /**
     * Load active employees for kiosk selection
     */
    protected function loadEmployees(): void
    {
        $this->employees = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->with('user')
            ->orderBy('name')
            ->get()
            ->map(fn ($emp) => [
                'id' => $emp->user_id,
                'name' => $emp->name,
                'employee_id' => $emp->id,
                'has_pin' => !empty($emp->pin),
            ])
            ->toArray();
    }

    /**
     * Load active projects for assignment
     */
    protected function loadProjects(): void
    {
        $this->projects = \Webkul\Project\Models\Project::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->take(50)
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Load today's attendance summary
     */
    protected function loadTodayAttendance(): void
    {
        $attendance = $this->clockingService->getTodayAttendance();
        $this->todayAttendance = $attendance['employees'] ?? [];
    }

    /**
     * Select an employee from the list
     */
    public function selectEmployee(int $userId, string $name, int $employeeId): void
    {
        $this->selectedUserId = $userId;
        $this->selectedUserName = $name;
        $this->selectedEmployeeId = $employeeId;

        // Reset form fields
        $this->pin = '';
        $this->pinVerified = false;
        $this->pinAttempts = 0;
        $this->breakDurationMinutes = 60;
        $this->selectedProjectId = null;
        $this->statusMessage = '';

        // Check if PIN is required
        if ($this->isPinRequired()) {
            $employee = Employee::find($employeeId);

            // If employee has no PIN set, show error
            if (empty($employee?->pin)) {
                $this->setStatus('No PIN set. Please ask your manager to set up your PIN.', 'error');
                $this->mode = 'select';
                return;
            }

            $this->mode = 'pin';
        } else {
            // Skip PIN, go directly to clock mode
            $this->pinVerified = true;
            $this->loadClockStatus();
            $this->mode = 'clock';
        }
    }

    /**
     * Verify employee PIN and auto-clock in if not clocked in
     */
    public function verifyPin(): void
    {
        if (!$this->selectedEmployeeId) {
            $this->setStatus('No employee selected', 'error');
            return;
        }

        $employee = Employee::find($this->selectedEmployeeId);

        if (!$employee) {
            $this->setStatus('Employee not found', 'error');
            return;
        }

        // Check PIN (trim whitespace and compare as strings)
        $storedPin = trim((string) ($employee->pin ?? ''));
        $enteredPin = trim((string) $this->pin);

        if ($storedPin === $enteredPin && !empty($storedPin)) {
            $this->pinVerified = true;
            $this->pinAttempts = 0;
            $this->loadClockStatus();

            // Auto-clock in if not already clocked in
            if (!$this->isClockedIn) {
                $result = $this->clockingService->clockIn($this->selectedUserId);
                if ($result['success']) {
                    $this->isClockedIn = true;
                    $this->clockedInAt = now()->format('g:i A');
                    $this->setStatus("Clocked in at {$this->clockedInAt}", 'success');
                    $this->loadTodayAttendance();
                    // Show confirmation screen, then auto-return after 5 seconds
                    $this->mode = 'confirmed';
                } else {
                    $this->setStatus($result['message'], 'error');
                    $this->mode = 'clock';
                }
            } else {
                // Already clocked in, go to clock mode
                $this->mode = 'clock';
            }
        } else {
            $this->pinAttempts++;
            $this->pin = '';

            if ($this->pinAttempts >= self::MAX_PIN_ATTEMPTS) {
                $this->setStatus('Too many attempts. Please try again later.', 'error');
                $this->mode = 'select';
                $this->selectedUserId = null;
                $this->selectedUserName = null;
                $this->selectedEmployeeId = null;
            } else {
                $remaining = self::MAX_PIN_ATTEMPTS - $this->pinAttempts;
                $this->setStatus("Incorrect PIN. {$remaining} attempts remaining.", 'error');
            }
        }
    }

    /**
     * Add digit to PIN
     */
    public function addPinDigit(string $digit): void
    {
        if (strlen($this->pin) < $this->getPinLength()) {
            $this->pin .= $digit;
        }
    }

    /**
     * Remove last digit from PIN
     */
    public function removePinDigit(): void
    {
        $this->pin = substr($this->pin, 0, -1);
    }

    /**
     * Clear PIN
     */
    public function clearPin(): void
    {
        $this->pin = '';
    }

    /**
     * Load clock status for selected employee
     */
    protected function loadClockStatus(): void
    {
        if ($this->selectedUserId) {
            $status = $this->clockingService->getStatus($this->selectedUserId);
            $this->isClockedIn = $status['is_clocked_in'];
            $this->clockedInAt = $status['clock_in_time'];
            $this->isOnLunch = $status['is_on_lunch'] ?? false;
            $this->lunchTaken = $status['lunch_taken'] ?? false;
            $this->lunchStartTime = $status['lunch_start_time'];
            $this->lunchEndTime = $status['lunch_end_time'];
        }
    }

    /**
     * Go back to employee selection
     */
    public function backToSelect(): void
    {
        // Reset all state
        $this->mode = 'select';
        $this->selectedUserId = null;
        $this->selectedUserName = null;
        $this->selectedEmployeeId = null;
        $this->pin = '';
        $this->pinVerified = false;
        $this->pinAttempts = 0;
        $this->statusMessage = '';
        $this->isClockedIn = false;
        $this->clockedInAt = null;
        $this->isOnLunch = false;
        $this->lunchTaken = false;
        $this->lunchStartTime = null;
        $this->lunchEndTime = null;
        $this->selectedProjectId = null;
        $this->breakDurationMinutes = 60;
        $this->loadTodayAttendance();
    }

    /**
     * Go back to PIN entry
     */
    public function backToPin(): void
    {
        $this->mode = 'pin';
        $this->pin = '';
        $this->pinVerified = false;
        $this->statusMessage = '';
    }

    /**
     * Clock in the selected employee
     */
    public function clockIn(): void
    {
        if (!$this->selectedUserId) {
            $this->setStatus('No employee selected', 'error');
            return;
        }

        if ($this->isPinRequired() && !$this->pinVerified) {
            $this->setStatus('PIN verification required', 'error');
            $this->mode = 'pin';
            return;
        }

        $result = $this->clockingService->clockIn($this->selectedUserId);

        if ($result['success']) {
            $this->isClockedIn = true;
            $this->clockedInAt = now()->format('g:i A');
            $this->setStatus("Clocked in at {$this->clockedInAt}", 'success');
            $this->loadTodayAttendance();
        } else {
            $this->setStatus($result['message'], 'error');
        }
    }

    /**
     * Clock out the selected employee
     */
    public function clockOut(): void
    {
        if (!$this->selectedUserId) {
            $this->setStatus('No employee selected', 'error');
            return;
        }

        if ($this->isPinRequired() && !$this->pinVerified) {
            $this->setStatus('PIN verification required', 'error');
            $this->mode = 'pin';
            return;
        }

        $result = $this->clockingService->clockOut(
            $this->selectedUserId,
            $this->breakDurationMinutes,
            $this->selectedProjectId
        );

        if ($result['success']) {
            $this->isClockedIn = false;
            $hoursWorked = $result['hours_worked'] ?? 0;
            $this->setStatus(
                sprintf("Clocked out! Worked %s today.", $this->formatHours($hoursWorked)),
                'success'
            );
            $this->loadTodayAttendance();
        } else {
            $this->setStatus($result['message'], 'error');
        }
    }

    /**
     * Start lunch break
     */
    public function startLunch(): void
    {
        if (!$this->selectedUserId) {
            $this->setStatus('No employee selected', 'error');
            return;
        }

        if ($this->isPinRequired() && !$this->pinVerified) {
            $this->setStatus('PIN verification required', 'error');
            $this->mode = 'pin';
            return;
        }

        $result = $this->clockingService->startLunch($this->selectedUserId);

        if ($result['success']) {
            $this->isOnLunch = true;
            $this->lunchStartTime = $result['lunch_start_time'];
            $this->setStatus("Lunch started at {$this->lunchStartTime}. Enjoy your break!", 'success');
            $this->loadTodayAttendance();
        } else {
            $this->setStatus($result['message'], 'error');
        }
    }

    /**
     * End lunch break
     */
    public function endLunch(): void
    {
        if (!$this->selectedUserId) {
            $this->setStatus('No employee selected', 'error');
            return;
        }

        if ($this->isPinRequired() && !$this->pinVerified) {
            $this->setStatus('PIN verification required', 'error');
            $this->mode = 'pin';
            return;
        }

        $result = $this->clockingService->endLunch($this->selectedUserId);

        if ($result['success']) {
            $this->isOnLunch = false;
            $this->lunchTaken = true;
            $this->lunchEndTime = $result['lunch_end_time'];
            $this->breakDurationMinutes = $result['lunch_duration_minutes'];
            $this->setStatus($result['message'], 'success');
            $this->loadTodayAttendance();
        } else {
            $this->setStatus($result['message'], 'error');
        }
    }

    /**
     * Set break duration
     */
    public function setBreakDuration(int $minutes): void
    {
        $this->breakDurationMinutes = $minutes;
    }

    /**
     * Set status message
     */
    protected function setStatus(string $message, string $type = 'info'): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    /**
     * Get current time for display
     */
    public function getCurrentTime(): string
    {
        return now()->format('g:i A');
    }

    /**
     * Get current date for display
     */
    public function getCurrentDate(): string
    {
        return now()->format('l, F j, Y');
    }

    /**
     * Check if lunch option should be available (not after 4 PM)
     */
    public function canTakeLunch(): bool
    {
        $currentHour = (int) now()->format('H');
        return $currentHour < 16; // Before 4 PM
    }

    /**
     * Format decimal hours to hours and minutes display
     * Example: 8.5 -> "8h 30m", 8.0 -> "8h"
     */
    public function formatHours($hours): string
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

    public function render(): View
    {
        return view('livewire.time-clock-kiosk');
    }
}
