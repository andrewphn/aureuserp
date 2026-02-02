<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Project\Services\CncCapacityAnalyticsService;

/**
 * CNC Capacity API Controller
 *
 * Provides REST API endpoints for CNC production capacity analysis.
 * Returns board feet production data calculated from VCarve sheet data.
 *
 * Usage:
 *   GET /api/v1/cnc/capacity                    - Full capacity report (last 90 days)
 *   GET /api/v1/cnc/capacity?start=2025-09-01&end=2026-01-31 - Custom date range
 *   GET /api/v1/cnc/capacity/today              - Today's production
 *   GET /api/v1/cnc/capacity/summary            - Quick dashboard summary
 *   GET /api/v1/cnc/capacity/materials          - Material breakdown
 *   GET /api/v1/cnc/capacity/peaks              - Top production days
 */
class CncCapacityController extends Controller
{
    protected CncCapacityAnalyticsService $analyticsService;

    public function __construct(CncCapacityAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get full capacity report
     *
     * GET /api/v1/cnc/capacity
     *
     * Query parameters:
     * - start: Start date (YYYY-MM-DD), defaults to 90 days ago
     * - end: End date (YYYY-MM-DD), defaults to today
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date',
        ]);

        $end = $request->has('end')
            ? Carbon::parse($request->input('end'))
            : Carbon::today();

        $start = $request->has('start')
            ? Carbon::parse($request->input('start'))
            : $end->copy()->subDays(90);

        $report = $this->analyticsService->getCapacityReport($start, $end);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get today's production stats
     *
     * GET /api/v1/cnc/capacity/today
     *
     * @return JsonResponse
     */
    public function today(): JsonResponse
    {
        $data = $this->analyticsService->calculateDailyBoardFeet(Carbon::today());

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get dashboard summary stats
     *
     * GET /api/v1/cnc/capacity/summary
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $stats = $this->analyticsService->getDashboardStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get material breakdown
     *
     * GET /api/v1/cnc/capacity/materials
     *
     * Query parameters:
     * - start: Start date (YYYY-MM-DD)
     * - end: End date (YYYY-MM-DD)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function materials(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date',
        ]);

        $start = $request->has('start')
            ? Carbon::parse($request->input('start'))
            : null;

        $end = $request->has('end')
            ? Carbon::parse($request->input('end'))
            : null;

        $breakdown = $this->analyticsService->getMaterialBreakdown($start, $end);

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }

    /**
     * Get peak production days
     *
     * GET /api/v1/cnc/capacity/peaks
     *
     * Query parameters:
     * - limit: Number of top days to return (default: 10)
     * - since: Only include days after this date
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function peaks(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'since' => 'nullable|date',
        ]);

        $limit = $request->input('limit', 10);
        $since = $request->has('since')
            ? Carbon::parse($request->input('since'))
            : null;

        $peaks = $this->analyticsService->getPeakDays($limit, $since);

        return response()->json([
            'success' => true,
            'data' => [
                'limit' => $limit,
                'since' => $since?->toDateString(),
                'peak_days' => $peaks->toArray(),
            ],
        ]);
    }

    /**
     * Get daily production for a specific date
     *
     * GET /api/v1/cnc/capacity/daily/{date}
     *
     * @param string $date Date in YYYY-MM-DD format
     * @return JsonResponse
     */
    public function daily(string $date): JsonResponse
    {
        try {
            $carbonDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid date format. Use YYYY-MM-DD.',
            ], 400);
        }

        $data = $this->analyticsService->calculateDailyBoardFeet($carbonDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get weekly production trend
     *
     * GET /api/v1/cnc/capacity/weekly
     *
     * Query parameters:
     * - weeks: Number of weeks to include (default: 12)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function weekly(Request $request): JsonResponse
    {
        $weeks = $request->input('weeks', 12);
        $end = Carbon::today();
        $start = $end->copy()->subWeeks($weeks);

        $report = $this->analyticsService->getCapacityReport($start, $end);

        return response()->json([
            'success' => true,
            'data' => [
                'weeks' => $weeks,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'summary' => $report['summary'],
                'weekly_data' => $report['weekly_averages'],
            ],
        ]);
    }

    /**
     * Get monthly production trend
     *
     * GET /api/v1/cnc/capacity/monthly
     *
     * Query parameters:
     * - months: Number of months to include (default: 6)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monthly(Request $request): JsonResponse
    {
        $months = $request->input('months', 6);
        $end = Carbon::today();
        $start = $end->copy()->subMonths($months);

        $report = $this->analyticsService->getCapacityReport($start, $end);

        return response()->json([
            'success' => true,
            'data' => [
                'months' => $months,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'summary' => $report['summary'],
                'monthly_data' => $report['monthly_totals'],
            ],
        ]);
    }
}
