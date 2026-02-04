<?php

namespace App\Http\Controllers;

use App\Services\ClockingService;
use Illuminate\Http\Request;
use Webkul\Employee\Models\Employee;

/**
 * Legacy Clock Kiosk Controller
 *
 * Pure server-side HTML form kiosk for iOS 12 and other browsers
 * that lack ES2015+ Proxy support (required by Livewire 3 / Alpine.js 3).
 *
 * Zero JavaScript — all interactions use <form method="POST"> and
 * <meta http-equiv="refresh"> for auto-redirects.
 */
class LegacyClockController extends Controller
{
    public function __construct(
        protected ClockingService $clockingService
    ) {}

    /**
     * GET /clock-legacy — Employee selection screen
     */
    public function index(Request $request)
    {
        // Clear any stale session state on fresh visit (no ?keep param)
        if (! $request->has('keep')) {
            $request->session()->forget([
                'legacy_clock.user_id',
                'legacy_clock.user_name',
                'legacy_clock.employee_id',
                'legacy_clock.pin',
                'legacy_clock.pin_attempts',
            ]);
        }

        $employees = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->with('user')
            ->orderBy('name')
            ->get()
            ->map(fn ($emp) => [
                'id'          => $emp->user_id,
                'name'        => $emp->name,
                'employee_id' => $emp->id,
                'has_pin'     => ! empty($emp->pin),
            ])
            ->toArray();

        return view('pages.legacy-clock-kiosk', [
            'mode'      => 'select',
            'employees' => $employees,
        ]);
    }

    /**
     * POST /clock-legacy/select — Store selected employee, show PIN form
     */
    public function selectEmployee(Request $request)
    {
        $request->validate([
            'user_id'     => 'required|integer',
            'name'        => 'required|string',
            'employee_id' => 'required|integer',
        ]);

        // Check if employee has a PIN set
        if (config('kiosk.pin_required', true)) {
            $employee = Employee::find($request->employee_id);
            if (empty($employee?->pin)) {
                $employees = $this->loadEmployees();

                return view('pages.legacy-clock-kiosk', [
                    'mode'      => 'select',
                    'employees' => $employees,
                    'error'     => 'No PIN set. Please ask your manager to set up your PIN.',
                ]);
            }
        }

        $request->session()->put('legacy_clock.user_id', (int) $request->user_id);
        $request->session()->put('legacy_clock.user_name', $request->name);
        $request->session()->put('legacy_clock.employee_id', (int) $request->employee_id);
        $request->session()->put('legacy_clock.pin', '');
        $request->session()->put('legacy_clock.pin_attempts', 0);

        return view('pages.legacy-clock-kiosk', [
            'mode'         => 'pin',
            'selectedName' => $request->name,
            'pin'          => '',
            'pinLength'    => config('kiosk.pin_length', 4),
        ]);
    }

    /**
     * POST /clock-legacy/pin — Verify PIN (accumulates digits via session)
     */
    public function verifyPin(Request $request)
    {
        $userId     = $request->session()->get('legacy_clock.user_id');
        $userName   = $request->session()->get('legacy_clock.user_name');
        $employeeId = $request->session()->get('legacy_clock.employee_id');
        $pinSoFar   = $request->session()->get('legacy_clock.pin', '');
        $attempts   = $request->session()->get('legacy_clock.pin_attempts', 0);
        $pinLength  = config('kiosk.pin_length', 4);

        if (! $userId || ! $employeeId) {
            return redirect()->route('clock-legacy');
        }

        // Handle digit addition
        if ($request->has('digit')) {
            $pinSoFar .= $request->digit;
            $request->session()->put('legacy_clock.pin', $pinSoFar);
        }

        // Handle clear
        if ($request->has('clear')) {
            $request->session()->put('legacy_clock.pin', '');

            return view('pages.legacy-clock-kiosk', [
                'mode'         => 'pin',
                'selectedName' => $userName,
                'pin'          => '',
                'pinLength'    => $pinLength,
            ]);
        }

        // Handle backspace
        if ($request->has('backspace')) {
            $pinSoFar = substr($pinSoFar, 0, -1);
            $request->session()->put('legacy_clock.pin', $pinSoFar);

            return view('pages.legacy-clock-kiosk', [
                'mode'         => 'pin',
                'selectedName' => $userName,
                'pin'          => $pinSoFar,
                'pinLength'    => $pinLength,
            ]);
        }

        // Not enough digits yet — re-show PIN screen
        if (strlen($pinSoFar) < $pinLength) {
            return view('pages.legacy-clock-kiosk', [
                'mode'         => 'pin',
                'selectedName' => $userName,
                'pin'          => $pinSoFar,
                'pinLength'    => $pinLength,
            ]);
        }

        // PIN is complete — verify
        $employee  = Employee::find($employeeId);
        $storedPin = trim((string) ($employee->pin ?? ''));
        $enteredPin = trim($pinSoFar);

        if ($storedPin === $enteredPin && ! empty($storedPin)) {
            // PIN correct — reset attempts
            $request->session()->put('legacy_clock.pin_attempts', 0);
            $request->session()->put('legacy_clock.pin', '');

            // Check clock status
            $status = $this->clockingService->getStatus($userId);

            if (! $status['is_clocked_in']) {
                // Auto clock-in
                $result = $this->clockingService->clockIn($userId);

                if ($result['success']) {
                    return view('pages.legacy-clock-kiosk', [
                        'mode'         => 'confirmed',
                        'selectedName' => $userName,
                        'clockedInAt'  => $result['clock_in_time'],
                    ]);
                }

                // Clock-in failed (e.g. already clocked in race condition)
                return view('pages.legacy-clock-kiosk', [
                    'mode'         => 'pin',
                    'selectedName' => $userName,
                    'pin'          => '',
                    'pinLength'    => $pinLength,
                    'error'        => $result['error'] ?? $result['message'] ?? 'Clock-in failed',
                ]);
            }

            // Already clocked in — show clock status
            return view('pages.legacy-clock-kiosk', [
                'mode'         => 'clock',
                'selectedName' => $userName,
                'clockedInAt'  => $status['clock_in_time'],
                'isOnLunch'    => $status['is_on_lunch'] ?? false,
                'lunchTaken'   => $status['lunch_taken'] ?? false,
            ]);
        }

        // PIN incorrect
        $attempts++;
        $request->session()->put('legacy_clock.pin_attempts', $attempts);
        $request->session()->put('legacy_clock.pin', '');

        if ($attempts >= 3) {
            $request->session()->forget([
                'legacy_clock.user_id',
                'legacy_clock.user_name',
                'legacy_clock.employee_id',
                'legacy_clock.pin',
                'legacy_clock.pin_attempts',
            ]);

            return redirect()->route('clock-legacy')
                ->with('error', 'Too many incorrect attempts. Please try again.');
        }

        $remaining = 3 - $attempts;

        return view('pages.legacy-clock-kiosk', [
            'mode'         => 'pin',
            'selectedName' => $userName,
            'pin'          => '',
            'pinLength'    => $pinLength,
            'error'        => "Incorrect PIN. {$remaining} attempts remaining.",
        ]);
    }

    /**
     * POST /clock-legacy/clock-out — Initiate clock-out (shows lunch selection if needed)
     */
    public function clockOut(Request $request)
    {
        $userId   = $request->session()->get('legacy_clock.user_id');
        $userName = $request->session()->get('legacy_clock.user_name');

        if (! $userId) {
            return redirect()->route('clock-legacy');
        }

        $status = $this->clockingService->getStatus($userId);
        $lunchTaken = $status['lunch_taken'] ?? false;

        if (! $lunchTaken) {
            // No lunch logged — show lunch duration selection
            return view('pages.legacy-clock-kiosk', [
                'mode'         => 'clockout-lunch',
                'selectedName' => $userName,
                'clockedInAt'  => $status['clock_in_time'],
            ]);
        }

        // Lunch already taken — clock out directly
        $result = $this->clockingService->clockOut($userId, 0);

        return $this->showSummary($request, $result, $userName, 0);
    }

    /**
     * POST /clock-legacy/lunch-clock-out — Set lunch duration and clock out
     */
    public function setLunchAndClockOut(Request $request)
    {
        $request->validate([
            'minutes' => 'required|integer|min:0|max:480',
        ]);

        $userId   = $request->session()->get('legacy_clock.user_id');
        $userName = $request->session()->get('legacy_clock.user_name');

        if (! $userId) {
            return redirect()->route('clock-legacy');
        }

        $minutes = (int) $request->minutes;
        $result  = $this->clockingService->clockOut($userId, $minutes);

        return $this->showSummary($request, $result, $userName, $minutes);
    }

    /**
     * GET /clock-legacy/back — Clear session and return to employee list
     */
    public function backToSelect(Request $request)
    {
        $request->session()->forget([
            'legacy_clock.user_id',
            'legacy_clock.user_name',
            'legacy_clock.employee_id',
            'legacy_clock.pin',
            'legacy_clock.pin_attempts',
        ]);

        return redirect()->route('clock-legacy');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function loadEmployees(): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->with('user')
            ->orderBy('name')
            ->get()
            ->map(fn ($emp) => [
                'id'          => $emp->user_id,
                'name'        => $emp->name,
                'employee_id' => $emp->id,
                'has_pin'     => ! empty($emp->pin),
            ])
            ->toArray();
    }

    private function showSummary(Request $request, array $result, string $userName, int $lunchMinutes)
    {
        // Clear session state
        $request->session()->forget([
            'legacy_clock.user_id',
            'legacy_clock.user_name',
            'legacy_clock.employee_id',
            'legacy_clock.pin',
            'legacy_clock.pin_attempts',
        ]);

        if ($result['success']) {
            return view('pages.legacy-clock-kiosk', [
                'mode'             => 'summary',
                'selectedName'     => $userName,
                'clockInTime'      => $result['clock_in_time'] ?? null,
                'clockOutTime'     => $result['clock_out_time'] ?? null,
                'hoursWorked'      => $result['hours_worked'] ?? 0,
                'lunchMinutes'     => $lunchMinutes > 0 ? $lunchMinutes : null,
                'projectName'      => $result['project'] ?? null,
            ]);
        }

        // Clock-out failed — redirect with error
        return redirect()->route('clock-legacy')
            ->with('error', $result['error'] ?? $result['message'] ?? 'Clock-out failed.');
    }
}
