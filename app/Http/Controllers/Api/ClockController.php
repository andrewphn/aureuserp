<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClockingService;
use App\Services\TimesheetExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Clock API Controller
 *
 * Handles time clock API endpoints for:
 * - Clock in/out
 * - Status checks
 * - Manual entries
 * - Weekly summaries
 * - Attendance reports
 */
class ClockController extends Controller
{
    public function __construct(
        protected ClockingService $clockingService,
        protected TimesheetExportService $exportService
    ) {}

    /**
     * Get current clock status for authenticated user
     *
     * GET /api/clock/status
     */
    public function status(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $status = $this->clockingService->getStatus($userId);

        return response()->json([
            'success' => true,
            ...$status,
        ]);
    }

    /**
     * Clock in the authenticated user
     *
     * POST /api/clock/in
     */
    public function clockIn(Request $request): JsonResponse
    {
        $request->validate([
            'work_location_id' => 'nullable|integer|exists:employees_work_locations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = $request->user()->id;
        $result = $this->clockingService->clockIn(
            $userId,
            $request->input('work_location_id'),
            $request->input('notes')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Clock out the authenticated user
     *
     * POST /api/clock/out
     */
    public function clockOut(Request $request): JsonResponse
    {
        $request->validate([
            'break_duration_minutes' => 'nullable|integer|min:0|max:180',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = $request->user()->id;
        $result = $this->clockingService->clockOut(
            $userId,
            $request->input('break_duration_minutes', 60),
            $request->input('project_id'),
            $request->input('notes')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Add a manual time entry
     *
     * POST /api/clock/manual
     */
    public function addManualEntry(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'clock_in_time' => 'required|date_format:H:i',
            'clock_out_time' => 'required|date_format:H:i|after:clock_in_time',
            'break_duration_minutes' => 'nullable|integer|min:0|max:180',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = $request->user()->id;
        $result = $this->clockingService->addManualEntry(
            $userId,
            $request->input('date'),
            $request->input('clock_in_time'),
            $request->input('clock_out_time'),
            $request->input('break_duration_minutes', 60),
            $request->input('project_id'),
            $request->input('notes')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get weekly summary for authenticated user
     *
     * GET /api/clock/weekly
     */
    public function weeklySummary(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => 'nullable|date',
        ]);

        $userId = $request->user()->id;
        $weekStart = $request->input('week_start')
            ? \Carbon\Carbon::parse($request->input('week_start'))
            : null;

        $summary = $this->clockingService->getWeeklySummary($userId, $weekStart);

        return response()->json([
            'success' => true,
            ...$summary,
        ]);
    }

    /**
     * Get today's attendance for all employees (owner only)
     *
     * GET /api/clock/attendance
     */
    public function todayAttendance(Request $request): JsonResponse
    {
        // TODO: Add authorization check for owner/manager role

        $attendance = $this->clockingService->getTodayAttendance();

        return response()->json([
            'success' => true,
            ...$attendance,
        ]);
    }

    /**
     * Approve a manual entry (owner/manager only)
     *
     * POST /api/clock/approve/{entryId}
     */
    public function approveEntry(Request $request, int $entryId): JsonResponse
    {
        // TODO: Add authorization check for owner/manager role

        $approverId = $request->user()->id;
        $result = $this->clockingService->approveEntry($entryId, $approverId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Assign time entry to a project
     *
     * POST /api/clock/assign-project/{entryId}
     */
    public function assignToProject(Request $request, int $entryId): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|integer|exists:projects_projects,id',
        ]);

        $result = $this->clockingService->assignToProject(
            $entryId,
            $request->input('project_id')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get status for a specific user (owner/manager only)
     *
     * GET /api/clock/status/{userId}
     */
    public function getUserStatus(Request $request, int $userId): JsonResponse
    {
        // TODO: Add authorization check for owner/manager role

        $status = $this->clockingService->getStatus($userId);

        return response()->json([
            'success' => true,
            ...$status,
        ]);
    }

    /**
     * Clock in a specific user (kiosk mode)
     *
     * POST /api/clock/kiosk/in
     */
    public function kioskClockIn(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'pin' => 'nullable|string|size:4', // For future PIN validation
            'work_location_id' => 'nullable|integer|exists:employees_work_locations,id',
        ]);

        $result = $this->clockingService->clockIn(
            $request->input('user_id'),
            $request->input('work_location_id')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Clock out a specific user (kiosk mode)
     *
     * POST /api/clock/kiosk/out
     */
    public function kioskClockOut(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'break_duration_minutes' => 'nullable|integer|min:0|max:180',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
        ]);

        $result = $this->clockingService->clockOut(
            $request->input('user_id'),
            $request->input('break_duration_minutes', 60),
            $request->input('project_id')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // =========================================================================
    // EXPORT ENDPOINTS
    // =========================================================================

    /**
     * Export weekly timesheet for authenticated user
     *
     * GET /api/clock/export/weekly
     */
    public function exportWeekly(Request $request): Response|JsonResponse
    {
        $request->validate([
            'week_start' => 'nullable|date',
            'format' => 'nullable|in:html,csv,json',
        ]);

        $userId = $request->user()->id;
        $weekStart = $request->input('week_start')
            ? Carbon::parse($request->input('week_start'))
            : null;
        $format = $request->input('format', 'html');

        if ($format === 'json') {
            $result = $this->exportService->exportWeeklyTimesheet($userId, $weekStart, 'array');
            return response()->json($result);
        }

        $result = $this->exportService->exportWeeklyTimesheet($userId, $weekStart, $format);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        if ($format === 'csv') {
            return response($result['csv'], 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            ]);
        }

        // HTML format - return for display or download
        if ($request->input('download')) {
            return response($result['html'], 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            ]);
        }

        return response($result['html'], 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * Export weekly timesheet for a specific user (owner only)
     *
     * GET /api/clock/export/weekly/{userId}
     */
    public function exportUserWeekly(Request $request, int $userId): Response|JsonResponse
    {
        // TODO: Add authorization check for owner/manager role

        $request->validate([
            'week_start' => 'nullable|date',
            'format' => 'nullable|in:html,csv,json',
        ]);

        $weekStart = $request->input('week_start')
            ? Carbon::parse($request->input('week_start'))
            : null;
        $format = $request->input('format', 'html');

        if ($format === 'json') {
            $result = $this->exportService->exportWeeklyTimesheet($userId, $weekStart, 'array');
            return response()->json($result);
        }

        $result = $this->exportService->exportWeeklyTimesheet($userId, $weekStart, $format);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        if ($format === 'csv') {
            return response($result['csv'], 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            ]);
        }

        return response($result['html'], 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * Export team weekly summary (owner only)
     *
     * GET /api/clock/export/team
     */
    public function exportTeamSummary(Request $request): Response|JsonResponse
    {
        // TODO: Add authorization check for owner/manager role

        $request->validate([
            'week_start' => 'nullable|date',
            'format' => 'nullable|in:csv,json',
        ]);

        $weekStart = $request->input('week_start')
            ? Carbon::parse($request->input('week_start'))
            : null;
        $format = $request->input('format', 'csv');

        if ($format === 'json') {
            $result = $this->exportService->exportTeamWeeklySummary($weekStart, 'array');
            return response()->json($result);
        }

        $result = $this->exportService->exportTeamWeeklySummary($weekStart, 'csv');

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response($result['csv'], 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }

    /**
     * Export payroll report (owner only)
     *
     * GET /api/clock/export/payroll
     */
    public function exportPayroll(Request $request): JsonResponse
    {
        // TODO: Add authorization check for owner/manager role

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $userId = $request->input('user_id');

        $result = $this->exportService->exportPayrollReport($startDate, $endDate, $userId);

        return response()->json($result);
    }
}
