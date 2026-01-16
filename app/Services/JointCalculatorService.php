<?php

namespace App\Services;

/**
 * Joint Calculator Service
 *
 * Universal collision detection and joint calculations for cabinet parts.
 * Handles miter joints, butt joints, and any joint type where two parts meet.
 *
 * COORDINATE SYSTEM (inherited from CabinetXYZService):
 * - Origin: Front-Bottom-Left corner of cabinet BOX
 * - X: Left → Right (positive)
 * - Y: Bottom → Top (positive) - Y=0 is BOTTOM of box
 * - Z: Front → Back (positive)
 *
 * Joint Types:
 * - miter: 45° angled cut where two parts meet
 * - butt: Square cut where one part butts against another
 * - dado: Groove cut for panel insertion
 * - rabbet: Step cut for panel overlap
 */
class JointCalculatorService
{
    /**
     * Detect collision between two parts in 3D space.
     *
     * @param array $partA Part A with position (x, y, z) and dimensions (w, h, d)
     * @param array $partB Part B with position (x, y, z) and dimensions (w, h, d)
     * @return array|null Collision zone bounds or null if no collision
     */
    public function detectCollision(array $partA, array $partB): ?array
    {
        $posA = $partA['position'];
        $dimA = $partA['dimensions'];
        $posB = $partB['position'];
        $dimB = $partB['dimensions'];

        // Calculate bounds for each axis
        $collisionXMin = max($posA['x'], $posB['x']);
        $collisionXMax = min($posA['x'] + $dimA['w'], $posB['x'] + $dimB['w']);
        $collisionYMin = max($posA['y'], $posB['y']);
        $collisionYMax = min($posA['y'] + $dimA['h'], $posB['y'] + $dimB['h']);
        $collisionZMin = max($posA['z'], $posB['z']);
        $collisionZMax = min($posA['z'] + $dimA['d'], $posB['z'] + $dimB['d']);

        // Check if there's actual overlap in all three dimensions
        if ($collisionXMin >= $collisionXMax ||
            $collisionYMin >= $collisionYMax ||
            $collisionZMin >= $collisionZMax) {
            return null;
        }

        return [
            'x_min' => $collisionXMin,
            'x_max' => $collisionXMax,
            'y_min' => $collisionYMin,
            'y_max' => $collisionYMax,
            'z_min' => $collisionZMin,
            'z_max' => $collisionZMax,
            'width' => $collisionXMax - $collisionXMin,
            'height' => $collisionYMax - $collisionYMin,
            'depth' => $collisionZMax - $collisionZMin,
        ];
    }

    /**
     * Detect collision in XZ plane only (for miter joints that run full height).
     *
     * @param array $partA Part A with position and dimensions
     * @param array $partB Part B with position and dimensions
     * @return array|null Collision zone in XZ plane or null
     */
    public function detectCollisionXZ(array $partA, array $partB): ?array
    {
        $posA = $partA['position'];
        $dimA = $partA['dimensions'];
        $posB = $partB['position'];
        $dimB = $partB['dimensions'];

        $collisionXMin = max($posA['x'], $posB['x']);
        $collisionXMax = min($posA['x'] + $dimA['w'], $posB['x'] + $dimB['w']);
        $collisionZMin = max($posA['z'], $posB['z']);
        $collisionZMax = min($posA['z'] + $dimA['d'], $posB['z'] + $dimB['d']);

        if ($collisionXMin >= $collisionXMax || $collisionZMin >= $collisionZMax) {
            return null;
        }

        return [
            'x_min' => $collisionXMin,
            'x_max' => $collisionXMax,
            'z_min' => $collisionZMin,
            'z_max' => $collisionZMax,
            'width' => $collisionXMax - $collisionXMin,
            'depth' => $collisionZMax - $collisionZMin,
        ];
    }

    /**
     * Calculate universal miter joint for any two colliding parts.
     *
     * Creates a 45° diagonal cut through the collision zone where both parts
     * share the same diagonal line but remove opposite territories.
     *
     * @param array $partA Part A with position, dimensions, and optionally part_name
     * @param array $partB Part B with position, dimensions, and optionally part_name
     * @param string $corner 'left', 'right', or 'auto' (auto-detect based on positions)
     * @return array ['part_a' => miter_cut|null, 'part_b' => miter_cut|null]
     */
    public function calculateMiter(array $partA, array $partB, string $corner = 'auto'): array
    {
        $collision = $this->detectCollisionXZ($partA, $partB);

        if (!$collision) {
            return ['part_a' => null, 'part_b' => null];
        }

        $posA = $partA['position'];
        $dimA = $partA['dimensions'];
        $posB = $partB['position'];
        $dimB = $partB['dimensions'];

        // Determine which part is "front" (smaller average Z) vs "back" (larger average Z)
        $centroidZA = $posA['z'] + ($dimA['d'] / 2);
        $centroidZB = $posB['z'] + ($dimB['d'] / 2);
        $frontPart = $centroidZA < $centroidZB ? 'A' : 'B';

        // Auto-detect corner based on X positions if not specified
        if ($corner === 'auto') {
            $centroidXA = $posA['x'] + ($dimA['w'] / 2);
            $centroidXB = $posB['x'] + ($dimB['w'] / 2);
            $avgCentroidX = ($centroidXA + $centroidXB) / 2;
            // If centroid is on left side of collision, it's a right corner (parts on left)
            // If centroid is on right side of collision, it's a left corner (parts on right)
            $corner = $avgCentroidX < ($collision['x_min'] + $collision['x_max']) / 2 ? 'right' : 'left';
        }

        // Calculate the diagonal vertices based on corner
        // For miter, we need two triangles that share the diagonal
        $diagonalVertices = $this->calculateDiagonalVertices($collision, $corner);

        // Front part removes back territory (so it can extend backward)
        // Back part removes front territory (so it can extend forward)
        if ($frontPart === 'A') {
            $miterA = $this->buildMiterCut(
                $diagonalVertices['back_triangle'],
                $posA['y'],
                $posA['y'] + $dimA['h'],
                'back_territory'
            );
            $miterB = $this->buildMiterCut(
                $diagonalVertices['front_triangle'],
                $posB['y'],
                $posB['y'] + $dimB['h'],
                'front_territory'
            );
        } else {
            $miterA = $this->buildMiterCut(
                $diagonalVertices['front_triangle'],
                $posA['y'],
                $posA['y'] + $dimA['h'],
                'front_territory'
            );
            $miterB = $this->buildMiterCut(
                $diagonalVertices['back_triangle'],
                $posB['y'],
                $posB['y'] + $dimB['h'],
                'back_territory'
            );
        }

        return [
            'part_a' => $miterA,
            'part_b' => $miterB,
            'collision_zone' => $collision,
            'corner' => $corner,
            'front_part' => $frontPart,
        ];
    }

    /**
     * Calculate diagonal vertices for miter joint.
     *
     * @param array $collision Collision zone bounds
     * @param string $corner 'left' or 'right'
     * @return array Front and back triangles with shared diagonal
     */
    protected function calculateDiagonalVertices(array $collision, string $corner): array
    {
        $xMin = $collision['x_min'];
        $xMax = $collision['x_max'];
        $zMin = $collision['z_min'];
        $zMax = $collision['z_max'];

        if ($corner === 'left') {
            // Left corner: diagonal goes from outer-front-left to inner-back-right
            $outerFront = ['x' => $xMin, 'z' => $zMin];
            $innerBack = ['x' => $xMax, 'z' => $zMax];
            $outerBack = ['x' => $xMin, 'z' => $zMax];
            $innerFront = ['x' => $xMax, 'z' => $zMin];
        } else {
            // Right corner: diagonal goes from outer-front-right to inner-back-left
            $outerFront = ['x' => $xMax, 'z' => $zMin];
            $innerBack = ['x' => $xMin, 'z' => $zMax];
            $outerBack = ['x' => $xMax, 'z' => $zMax];
            $innerFront = ['x' => $xMin, 'z' => $zMin];
        }

        return [
            // Front triangle: outer-front -> diagonal-corner -> inner-front
            'front_triangle' => [$outerFront, $innerBack, $innerFront],
            // Back triangle: outer-front -> diagonal-corner -> outer-back
            'back_triangle' => [$outerFront, $innerBack, $outerBack],
        ];
    }

    /**
     * Build a miter cut definition for Rhino export.
     *
     * @param array $triangleXZ Three vertices in XZ plane [['x' => x, 'z' => z], ...]
     * @param float $yStart Start Y position (bottom of cut)
     * @param float $yEnd End Y position (top of cut)
     * @param string $territory Description of what territory is being removed
     * @return array Miter cut definition for Rhino
     */
    protected function buildMiterCut(array $triangleXZ, float $yStart, float $yEnd, string $territory): array
    {
        return [
            'type' => 'triangular_prism',
            'remove_from' => $territory,
            'vertices_xz' => $triangleXZ,
            'y_range' => [
                'start' => $yStart,
                'end' => $yEnd,
            ],
            'miter_angle' => 45,
        ];
    }

    /**
     * Apply miter joints to positions array for specific part pairs.
     *
     * @param array $positions3d Positions array with parts
     * @param array $partPairs Array of part pairs to apply miters to [['a' => 'part_key', 'b' => 'part_key', 'corner' => 'left|right|auto'], ...]
     * @return array Updated positions array with miter_cut added to parts
     */
    public function applyMiterJoints(array &$positions3d, array $partPairs): array
    {
        $results = [];

        foreach ($partPairs as $pair) {
            $partAKey = $pair['a'];
            $partBKey = $pair['b'];
            $corner = $pair['corner'] ?? 'auto';

            if (!isset($positions3d['parts'][$partAKey]) || !isset($positions3d['parts'][$partBKey])) {
                $results[] = [
                    'pair' => [$partAKey, $partBKey],
                    'success' => false,
                    'error' => 'Part not found',
                ];
                continue;
            }

            $partA = $positions3d['parts'][$partAKey];
            $partB = $positions3d['parts'][$partBKey];

            $miterResult = $this->calculateMiter($partA, $partB, $corner);

            if ($miterResult['part_a']) {
                $positions3d['parts'][$partAKey]['miter_cut'] = $miterResult['part_a'];
            }
            if ($miterResult['part_b']) {
                $positions3d['parts'][$partBKey]['miter_cut'] = $miterResult['part_b'];
            }

            $results[] = [
                'pair' => [$partAKey, $partBKey],
                'success' => $miterResult['part_a'] !== null || $miterResult['part_b'] !== null,
                'collision_zone' => $miterResult['collision_zone'] ?? null,
                'corner' => $miterResult['corner'] ?? null,
            ];
        }

        return $results;
    }

    /**
     * Auto-detect all colliding parts and suggest joint pairs.
     *
     * @param array $positions3d Positions array with parts
     * @param array $excludeParts Part keys to exclude from collision detection
     * @return array Array of colliding part pairs
     */
    public function detectAllCollisions(array $positions3d, array $excludeParts = []): array
    {
        $collisions = [];
        $partKeys = array_keys($positions3d['parts'] ?? []);

        for ($i = 0; $i < count($partKeys); $i++) {
            for ($j = $i + 1; $j < count($partKeys); $j++) {
                $keyA = $partKeys[$i];
                $keyB = $partKeys[$j];

                if (in_array($keyA, $excludeParts) || in_array($keyB, $excludeParts)) {
                    continue;
                }

                $partA = $positions3d['parts'][$keyA];
                $partB = $positions3d['parts'][$keyB];

                $collision = $this->detectCollision($partA, $partB);

                if ($collision) {
                    $collisions[] = [
                        'part_a' => $keyA,
                        'part_b' => $keyB,
                        'collision_zone' => $collision,
                        'part_a_name' => $partA['part_name'] ?? $keyA,
                        'part_b_name' => $partB['part_name'] ?? $keyB,
                    ];
                }
            }
        }

        return $collisions;
    }

    /**
     * Get joint recommendations based on part types.
     *
     * @param string $typeA Part type A (e.g., 'face_frame', 'finished_end')
     * @param string $typeB Part type B
     * @return string Recommended joint type ('miter', 'butt', etc.)
     */
    public function recommendJointType(string $typeA, string $typeB): string
    {
        // Sort types for consistent lookup
        $types = [$typeA, $typeB];
        sort($types);
        $key = implode('_', $types);

        $recommendations = [
            'face_frame_finished_end' => 'miter',
            'cabinet_box_face_frame' => 'butt',
            'cabinet_box_cabinet_box' => 'butt',
            'cabinet_box_stretcher' => 'butt',
            'face_frame_face_frame' => 'pocket_screw',
            'face_frame_stretcher' => 'butt',
        ];

        return $recommendations[$key] ?? 'butt';
    }
}
