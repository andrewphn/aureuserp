<?php

namespace Webkul\Project\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Entity Builder Calculations
 *
 * Tests the calculation logic used in the Entity Builder sidebar component
 * on the PDF Review page. These are pure PHP unit tests that don't require
 * database or Laravel framework.
 */
class EntityBuilderCalculationsTest extends TestCase
{
    /**
     * Helper method to calculate total rooms from rooms array
     */
    private function calculateTotalRooms(array $rooms): int
    {
        return count($rooms);
    }

    /**
     * Helper method to calculate total cabinet runs across all rooms
     */
    private function calculateTotalRuns(array $rooms): int
    {
        return collect($rooms)->sum(fn($r) => count($r['cabinet_runs'] ?? []));
    }

    /**
     * Helper method to calculate total linear feet across all cabinet runs
     */
    private function calculateTotalLinearFeet(array $rooms): float
    {
        return collect($rooms)->sum(function($r) {
            return collect($r['cabinet_runs'] ?? [])->sum(fn($run) => (float)($run['linear_feet'] ?? 0));
        });
    }

    /**
     * Helper method to calculate tier breakdown
     */
    private function calculateTierTotals(array $rooms): array
    {
        $tierTotals = [];
        foreach ($rooms as $room) {
            foreach ($room['cabinet_runs'] ?? [] as $run) {
                $level = $run['cabinet_level'] ?? '2';
                $lf = (float)($run['linear_feet'] ?? 0);
                $tierTotals[$level] = ($tierTotals[$level] ?? 0) + $lf;
            }
        }
        ksort($tierTotals);
        return $tierTotals;
    }

    /**
     * Helper method to calculate room linear feet
     */
    private function calculateRoomLinearFeet(array $room): float
    {
        return collect($room['cabinet_runs'] ?? [])->sum(fn($run) => (float)($run['linear_feet'] ?? 0));
    }

    /** @test */
    public function it_calculates_zero_for_empty_rooms_array()
    {
        $rooms = [];

        $this->assertEquals(0, $this->calculateTotalRooms($rooms));
        $this->assertEquals(0, $this->calculateTotalRuns($rooms));
        $this->assertEquals(0.0, $this->calculateTotalLinearFeet($rooms));
        $this->assertEquals([], $this->calculateTierTotals($rooms));
    }

    /** @test */
    public function it_calculates_room_count_correctly()
    {
        $rooms = [
            ['room_name' => 'Kitchen', 'cabinet_runs' => []],
            ['room_name' => 'Bathroom', 'cabinet_runs' => []],
            ['room_name' => 'Bedroom', 'cabinet_runs' => []],
        ];

        $this->assertEquals(3, $this->calculateTotalRooms($rooms));
    }

    /** @test */
    public function it_calculates_cabinet_run_count_correctly()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base Cabinets', 'linear_feet' => '10', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall Cabinets', 'linear_feet' => '8', 'cabinet_level' => '3'],
                ],
            ],
            [
                'room_name' => 'Bathroom',
                'cabinet_runs' => [
                    ['run_name' => 'Vanity', 'linear_feet' => '5', 'cabinet_level' => '2'],
                ],
            ],
        ];

        $this->assertEquals(3, $this->calculateTotalRuns($rooms));
    }

    /** @test */
    public function it_calculates_total_linear_feet_correctly()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base Cabinets', 'linear_feet' => '10.5', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall Cabinets', 'linear_feet' => '8.25', 'cabinet_level' => '3'],
                ],
            ],
            [
                'room_name' => 'Bathroom',
                'cabinet_runs' => [
                    ['run_name' => 'Vanity', 'linear_feet' => '4.75', 'cabinet_level' => '2'],
                ],
            ],
        ];

        $this->assertEquals(23.5, $this->calculateTotalLinearFeet($rooms));
    }

    /** @test */
    public function it_calculates_tier_breakdown_correctly()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base Cabinets', 'linear_feet' => '10', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall Cabinets', 'linear_feet' => '8', 'cabinet_level' => '3'],
                    ['run_name' => 'Premium Cabinets', 'linear_feet' => '5', 'cabinet_level' => '4'],
                ],
            ],
            [
                'room_name' => 'Bathroom',
                'cabinet_runs' => [
                    ['run_name' => 'Vanity', 'linear_feet' => '6', 'cabinet_level' => '2'],
                ],
            ],
        ];

        $tierTotals = $this->calculateTierTotals($rooms);

        $this->assertEquals([
            '2' => 16.0, // 10 + 6
            '3' => 8.0,
            '4' => 5.0,
        ], $tierTotals);
    }

    /** @test */
    public function it_defaults_missing_cabinet_level_to_tier_2()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base Cabinets', 'linear_feet' => '10'], // No cabinet_level
                ],
            ],
        ];

        $tierTotals = $this->calculateTierTotals($rooms);

        $this->assertEquals(['2' => 10.0], $tierTotals);
    }

    /** @test */
    public function it_handles_string_linear_feet_values()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'linear_feet' => '10.5', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall', 'linear_feet' => '8', 'cabinet_level' => '2'], // No decimal
                ],
            ],
        ];

        $this->assertEquals(18.5, $this->calculateTotalLinearFeet($rooms));
    }

    /** @test */
    public function it_handles_null_linear_feet_values()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'linear_feet' => null, 'cabinet_level' => '2'],
                    ['run_name' => 'Wall', 'linear_feet' => '8', 'cabinet_level' => '2'],
                ],
            ],
        ];

        $this->assertEquals(8.0, $this->calculateTotalLinearFeet($rooms));
    }

    /** @test */
    public function it_handles_missing_linear_feet_key()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'cabinet_level' => '2'], // No linear_feet key
                    ['run_name' => 'Wall', 'linear_feet' => '8', 'cabinet_level' => '2'],
                ],
            ],
        ];

        $this->assertEquals(8.0, $this->calculateTotalLinearFeet($rooms));
    }

    /** @test */
    public function it_calculates_per_room_linear_feet()
    {
        $room = [
            'room_name' => 'Kitchen',
            'cabinet_runs' => [
                ['run_name' => 'Base Cabinets', 'linear_feet' => '12.5', 'cabinet_level' => '2'],
                ['run_name' => 'Wall Cabinets', 'linear_feet' => '10.0', 'cabinet_level' => '3'],
                ['run_name' => 'Island', 'linear_feet' => '6.5', 'cabinet_level' => '4'],
            ],
        ];

        $this->assertEquals(29.0, $this->calculateRoomLinearFeet($room));
    }

    /** @test */
    public function it_handles_empty_cabinet_runs_array()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [],
            ],
        ];

        $this->assertEquals(1, $this->calculateTotalRooms($rooms));
        $this->assertEquals(0, $this->calculateTotalRuns($rooms));
        $this->assertEquals(0.0, $this->calculateTotalLinearFeet($rooms));
    }

    /** @test */
    public function it_handles_missing_cabinet_runs_key()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                // No cabinet_runs key
            ],
        ];

        $this->assertEquals(1, $this->calculateTotalRooms($rooms));
        $this->assertEquals(0, $this->calculateTotalRuns($rooms));
        $this->assertEquals(0.0, $this->calculateTotalLinearFeet($rooms));
    }

    /** @test */
    public function it_sorts_tier_totals_by_tier_number()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Premium', 'linear_feet' => '5', 'cabinet_level' => '5'],
                    ['run_name' => 'Basic', 'linear_feet' => '10', 'cabinet_level' => '1'],
                    ['run_name' => 'Standard', 'linear_feet' => '8', 'cabinet_level' => '3'],
                ],
            ],
        ];

        $tierTotals = $this->calculateTierTotals($rooms);

        // Keys should be sorted: 1, 3, 5
        $this->assertEquals(['1', '3', '5'], array_keys($tierTotals));
    }

    /** @test */
    public function it_handles_decimal_precision()
    {
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Run 1', 'linear_feet' => '10.333', 'cabinet_level' => '2'],
                    ['run_name' => 'Run 2', 'linear_feet' => '5.667', 'cabinet_level' => '2'],
                ],
            ],
        ];

        $this->assertEquals(16.0, $this->calculateTotalLinearFeet($rooms));
    }

    /** @test */
    public function it_handles_large_project_with_many_rooms()
    {
        $rooms = [];
        for ($i = 1; $i <= 20; $i++) {
            $rooms[] = [
                'room_name' => "Room $i",
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'linear_feet' => '10', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall', 'linear_feet' => '8', 'cabinet_level' => '3'],
                ],
            ];
        }

        $this->assertEquals(20, $this->calculateTotalRooms($rooms));
        $this->assertEquals(40, $this->calculateTotalRuns($rooms)); // 20 rooms * 2 runs
        $this->assertEquals(360.0, $this->calculateTotalLinearFeet($rooms)); // 20 * (10 + 8)
    }

    /** @test */
    public function it_validates_friendship_lane_sample_data()
    {
        // This matches the 25 Friendship Lane sample project
        $rooms = [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Sink Wall', 'linear_feet' => '12.5', 'cabinet_level' => '3'],
                    ['run_name' => 'Fridge Wall', 'linear_feet' => '8.0', 'cabinet_level' => '3'],
                    ['run_name' => 'Pantry', 'linear_feet' => '6.5', 'cabinet_level' => '2'],
                    ['run_name' => 'Island', 'linear_feet' => '10.0', 'cabinet_level' => '4'],
                ],
            ],
        ];

        $this->assertEquals(1, $this->calculateTotalRooms($rooms));
        $this->assertEquals(4, $this->calculateTotalRuns($rooms));
        $this->assertEquals(37.0, $this->calculateTotalLinearFeet($rooms));

        $tierTotals = $this->calculateTierTotals($rooms);
        $this->assertEquals([
            '2' => 6.5,  // Pantry
            '3' => 20.5, // Sink Wall + Fridge Wall
            '4' => 10.0, // Island
        ], $tierTotals);
    }
}
