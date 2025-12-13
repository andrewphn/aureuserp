<?php

namespace App\Livewire;

use App\Services\ClockingService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

/**
 * Time Clock Kiosk - Shop Floor Interface
 *
 * Touch-friendly interface for employees to clock in/out
 * from a shared tablet in the shop.
 *
 * Features:
 * - Employee PIN entry (4 digits)
 * - Clock in/out with single tap
 * - Lunch duration selection
 * - Project selection on clock out
 * - Today's attendance summary
 */
class TimeClockKiosk extends Component
{
    // Mode: 'select' (choose employee), 'clock' (clock in/out)
    public string $mode = 'select';

    // Currently selected user
    public ?int $selectedUserId = null;
    public ?string $selectedUserName = null;

    // Clock state
    public bool $isClockedIn = false;
    public ?string $clockedInAt = null;

    // Form inputs
    public string $pin = '';
    public int $breakDurationMinutes = 60;
    public ?int $selectedProjectId = null;

    // Status message
    public string $statusMessage = '';
    public string $statusType = 'info'; // 'success', 'error', 'info'

    // Available employees for selection
    public array $employees = [];

    // Available projects
    public array $projects = [];

    // Today's attendance summary
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
    public function selectEmployee(int $userId, string $name): void
    {
        $this->selectedUserId = $userId;
        $this->selectedUserName = $name;
        $this->mode = 'clock';

        // Check current clock status
        $status = $this->clockingService->getStatus($userId);
        $this->isClockedIn = $status['is_clocked_in'];
        $this->clockedInAt = $status['clock_in_time'];

        // Reset form fields
        $this->pin = '';
        $this->breakDurationMinutes = 60;
        $this->selectedProjectId = null;
        $this->statusMessage = '';
    }

    /**
     * Go back to employee selection
     */
    public function backToSelect(): void
    {
        $this->mode = 'select';
        $this->selectedUserId = null;
        $this->selectedUserName = null;
        $this->pin = '';
        $this->statusMessage = '';
        $this->loadTodayAttendance();
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

        $result = $this->clockingService->clockOut(
            $this->selectedUserId,
            $this->breakDurationMinutes,
            $this->selectedProjectId
        );

        if ($result['success']) {
            $this->isClockedIn = false;
            $hoursWorked = $result['hours_worked'] ?? 0;
            $this->setStatus(
                sprintf("Clocked out! Worked %.1f hours today.", $hoursWorked),
                'success'
            );
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

    public function render(): View
    {
        return view('livewire.time-clock-kiosk');
    }
}
