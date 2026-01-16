<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Sheet Nesting Service
 *
 * Integrates open source 2D bin packing algorithms to optimize
 * cut piece placement on sheet goods (plywood, MDF, etc.)
 *
 * Uses rectpack algorithm (MaxRects with Best Short Side Fit)
 * implemented in pure PHP for portability.
 */
class SheetNestingService
{
    /**
     * Standard sheet sizes (width × height in inches)
     */
    public const SHEET_SIZES = [
        '4x8' => ['width' => 48, 'height' => 96, 'label' => '4×8 Sheet'],
        '5x5' => ['width' => 60, 'height' => 60, 'label' => '5×5 Sheet'],
        '4x4' => ['width' => 48, 'height' => 48, 'label' => '4×4 Sheet'],
    ];

    /**
     * Blade kerf (saw blade width) in inches
     */
    public const KERF = 0.125; // 1/8" kerf

    /**
     * Nest cut pieces onto sheets
     *
     * @param array $pieces Array of pieces with width, length, qty, material
     * @param string $sheetSize Sheet size key (4x8, 5x5, etc.)
     * @param float $kerf Saw blade kerf in inches
     * @return array Nesting result with sheets, placements, and waste stats
     */
    public function nestPieces(array $pieces, string $sheetSize = '4x8', float $kerf = self::KERF): array
    {
        $sheet = self::SHEET_SIZES[$sheetSize] ?? self::SHEET_SIZES['4x8'];
        $sheetWidth = $sheet['width'];
        $sheetHeight = $sheet['height'];

        // Expand pieces by quantity and add kerf
        $expandedPieces = $this->expandPieces($pieces, $kerf);

        // Sort pieces by area (largest first) for better packing
        usort($expandedPieces, fn($a, $b) => ($b['width'] * $b['height']) <=> ($a['width'] * $a['height']));

        // Run MaxRects bin packing algorithm
        $result = $this->maxRectsPack($expandedPieces, $sheetWidth, $sheetHeight);

        // Calculate statistics
        $stats = $this->calculateStats($result, $sheetWidth, $sheetHeight);

        return [
            'sheet_size' => $sheet,
            'sheets' => $result['sheets'],
            'total_sheets' => count($result['sheets']),
            'stats' => $stats,
            'unplaced' => $result['unplaced'],
        ];
    }

    /**
     * Expand pieces by quantity and add kerf allowance
     */
    protected function expandPieces(array $pieces, float $kerf): array
    {
        $expanded = [];
        $pieceId = 1;

        foreach ($pieces as $piece) {
            $qty = $piece['qty'] ?? 1;
            for ($i = 0; $i < $qty; $i++) {
                $expanded[] = [
                    'id' => $pieceId++,
                    'part' => $piece['part'] ?? 'Unknown',
                    'original_width' => $piece['width'],
                    'original_height' => $piece['length'] ?? $piece['height'],
                    'width' => $piece['width'] + $kerf,
                    'height' => ($piece['length'] ?? $piece['height']) + $kerf,
                    'material' => $piece['material'] ?? 'plywood',
                    'thickness' => $piece['thickness'] ?? 0.75,
                    'rotated' => false,
                ];
            }
        }

        return $expanded;
    }

    /**
     * MaxRects Bin Packing Algorithm (Best Short Side Fit)
     *
     * Based on the algorithm described in:
     * "A Thousand Ways to Pack the Bin" by Jukka Jylänki
     */
    protected function maxRectsPack(array $pieces, float $sheetWidth, float $sheetHeight): array
    {
        $sheets = [];
        $unplaced = [];
        $currentSheet = $this->createNewSheet($sheetWidth, $sheetHeight);
        $sheetIndex = 0;

        foreach ($pieces as $piece) {
            $placed = false;

            // Try to place on current sheet
            $placement = $this->findBestPosition($currentSheet, $piece);

            if ($placement) {
                $currentSheet = $this->placePiece($currentSheet, $piece, $placement);
                $placed = true;
            } else {
                // Try rotating the piece
                $rotatedPiece = $this->rotatePiece($piece);
                $placement = $this->findBestPosition($currentSheet, $rotatedPiece);

                if ($placement) {
                    $currentSheet = $this->placePiece($currentSheet, $rotatedPiece, $placement);
                    $placed = true;
                }
            }

            if (!$placed) {
                // Save current sheet and start a new one
                if (!empty($currentSheet['placements'])) {
                    $sheets[] = $currentSheet;
                    $sheetIndex++;
                }
                $currentSheet = $this->createNewSheet($sheetWidth, $sheetHeight);

                // Try placing on new sheet
                $placement = $this->findBestPosition($currentSheet, $piece);
                if ($placement) {
                    $currentSheet = $this->placePiece($currentSheet, $piece, $placement);
                    $placed = true;
                } else {
                    // Try rotated on new sheet
                    $rotatedPiece = $this->rotatePiece($piece);
                    $placement = $this->findBestPosition($currentSheet, $rotatedPiece);
                    if ($placement) {
                        $currentSheet = $this->placePiece($currentSheet, $rotatedPiece, $placement);
                        $placed = true;
                    }
                }
            }

            if (!$placed) {
                // Piece too large for sheet
                $unplaced[] = $piece;
            }
        }

        // Don't forget the last sheet
        if (!empty($currentSheet['placements'])) {
            $sheets[] = $currentSheet;
        }

        return [
            'sheets' => $sheets,
            'unplaced' => $unplaced,
        ];
    }

    /**
     * Create a new empty sheet with one free rectangle
     */
    protected function createNewSheet(float $width, float $height): array
    {
        return [
            'width' => $width,
            'height' => $height,
            'placements' => [],
            'freeRects' => [
                ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height],
            ],
        ];
    }

    /**
     * Find the best position for a piece using Best Short Side Fit (BSSF)
     */
    protected function findBestPosition(array $sheet, array $piece): ?array
    {
        $bestScore = PHP_INT_MAX;
        $bestRect = null;
        $bestIndex = -1;

        foreach ($sheet['freeRects'] as $index => $rect) {
            if ($piece['width'] <= $rect['width'] && $piece['height'] <= $rect['height']) {
                // Calculate short side fit score
                $leftoverHoriz = $rect['width'] - $piece['width'];
                $leftoverVert = $rect['height'] - $piece['height'];
                $shortSideFit = min($leftoverHoriz, $leftoverVert);

                if ($shortSideFit < $bestScore) {
                    $bestScore = $shortSideFit;
                    $bestRect = $rect;
                    $bestIndex = $index;
                }
            }
        }

        if ($bestRect) {
            return [
                'x' => $bestRect['x'],
                'y' => $bestRect['y'],
                'rectIndex' => $bestIndex,
            ];
        }

        return null;
    }

    /**
     * Place a piece and update free rectangles
     */
    protected function placePiece(array $sheet, array $piece, array $placement): array
    {
        // Add placement
        $sheet['placements'][] = [
            'id' => $piece['id'],
            'part' => $piece['part'],
            'x' => $placement['x'],
            'y' => $placement['y'],
            'width' => $piece['width'],
            'height' => $piece['height'],
            'original_width' => $piece['original_width'],
            'original_height' => $piece['original_height'],
            'rotated' => $piece['rotated'],
            'material' => $piece['material'],
            'thickness' => $piece['thickness'],
        ];

        // Split free rectangles around the placed piece
        $sheet['freeRects'] = $this->splitFreeRects(
            $sheet['freeRects'],
            $placement['x'],
            $placement['y'],
            $piece['width'],
            $piece['height']
        );

        return $sheet;
    }

    /**
     * Split free rectangles when a piece is placed
     * Uses the Guillotine split method
     */
    protected function splitFreeRects(array $freeRects, float $x, float $y, float $w, float $h): array
    {
        $newRects = [];

        foreach ($freeRects as $rect) {
            // Check if this rectangle overlaps with the placed piece
            if ($this->rectanglesOverlap($rect, $x, $y, $w, $h)) {
                // Split into up to 4 new rectangles

                // Left rectangle
                if ($x > $rect['x']) {
                    $newRects[] = [
                        'x' => $rect['x'],
                        'y' => $rect['y'],
                        'width' => $x - $rect['x'],
                        'height' => $rect['height'],
                    ];
                }

                // Right rectangle
                if ($x + $w < $rect['x'] + $rect['width']) {
                    $newRects[] = [
                        'x' => $x + $w,
                        'y' => $rect['y'],
                        'width' => ($rect['x'] + $rect['width']) - ($x + $w),
                        'height' => $rect['height'],
                    ];
                }

                // Bottom rectangle
                if ($y > $rect['y']) {
                    $newRects[] = [
                        'x' => $rect['x'],
                        'y' => $rect['y'],
                        'width' => $rect['width'],
                        'height' => $y - $rect['y'],
                    ];
                }

                // Top rectangle
                if ($y + $h < $rect['y'] + $rect['height']) {
                    $newRects[] = [
                        'x' => $rect['x'],
                        'y' => $y + $h,
                        'width' => $rect['width'],
                        'height' => ($rect['y'] + $rect['height']) - ($y + $h),
                    ];
                }
            } else {
                // No overlap, keep the rectangle
                $newRects[] = $rect;
            }
        }

        // Remove rectangles that are fully contained within others
        return $this->pruneFreeRects($newRects);
    }

    /**
     * Check if a rectangle overlaps with a placed piece
     */
    protected function rectanglesOverlap(array $rect, float $x, float $y, float $w, float $h): bool
    {
        return !($rect['x'] >= $x + $w ||
                 $rect['x'] + $rect['width'] <= $x ||
                 $rect['y'] >= $y + $h ||
                 $rect['y'] + $rect['height'] <= $y);
    }

    /**
     * Remove rectangles that are fully contained within others
     */
    protected function pruneFreeRects(array $rects): array
    {
        $pruned = [];

        for ($i = 0; $i < count($rects); $i++) {
            $isContained = false;

            for ($j = 0; $j < count($rects); $j++) {
                if ($i !== $j && $this->isContainedIn($rects[$i], $rects[$j])) {
                    $isContained = true;
                    break;
                }
            }

            if (!$isContained) {
                $pruned[] = $rects[$i];
            }
        }

        return $pruned;
    }

    /**
     * Check if rect A is fully contained within rect B
     */
    protected function isContainedIn(array $a, array $b): bool
    {
        return $a['x'] >= $b['x'] &&
               $a['y'] >= $b['y'] &&
               $a['x'] + $a['width'] <= $b['x'] + $b['width'] &&
               $a['y'] + $a['height'] <= $b['y'] + $b['height'];
    }

    /**
     * Rotate a piece (swap width and height)
     */
    protected function rotatePiece(array $piece): array
    {
        return array_merge($piece, [
            'width' => $piece['height'],
            'height' => $piece['width'],
            'original_width' => $piece['original_height'],
            'original_height' => $piece['original_width'],
            'rotated' => !$piece['rotated'],
        ]);
    }

    /**
     * Calculate nesting statistics
     */
    protected function calculateStats(array $result, float $sheetWidth, float $sheetHeight): array
    {
        $totalSheetArea = 0;
        $totalUsedArea = 0;
        $totalPieces = 0;

        foreach ($result['sheets'] as $sheet) {
            $totalSheetArea += $sheetWidth * $sheetHeight;

            foreach ($sheet['placements'] as $placement) {
                $totalUsedArea += $placement['width'] * $placement['height'];
                $totalPieces++;
            }
        }

        $wasteArea = $totalSheetArea - $totalUsedArea;
        $efficiency = $totalSheetArea > 0 ? ($totalUsedArea / $totalSheetArea) * 100 : 0;

        return [
            'total_sheet_area_sqin' => round($totalSheetArea, 2),
            'total_sheet_area_sqft' => round($totalSheetArea / 144, 2),
            'used_area_sqin' => round($totalUsedArea, 2),
            'used_area_sqft' => round($totalUsedArea / 144, 2),
            'waste_area_sqin' => round($wasteArea, 2),
            'waste_area_sqft' => round($wasteArea / 144, 2),
            'efficiency_percent' => round($efficiency, 1),
            'waste_percent' => round(100 - $efficiency, 1),
            'total_pieces' => $totalPieces,
            'unplaced_pieces' => count($result['unplaced']),
        ];
    }

    /**
     * Generate SVG visualization of a nested sheet
     */
    public function generateSheetSVG(array $sheet, float $scale = 4): string
    {
        $svgWidth = $sheet['width'] * $scale;
        $svgHeight = $sheet['height'] * $scale;

        $svg = <<<SVG
<svg viewBox="0 0 {$svgWidth} {$svgHeight}" xmlns="http://www.w3.org/2000/svg" style="border: 1px solid #ccc; background: #f5f5f5;">
  <!-- Sheet outline -->
  <rect x="0" y="0" width="{$svgWidth}" height="{$svgHeight}" fill="#e8e8e8" stroke="#333" stroke-width="2"/>

  <!-- Grid lines (every 12 inches / 1 foot) -->
SVG;

        // Add grid lines every 12 inches
        for ($x = 12; $x < $sheet['width']; $x += 12) {
            $scaledX = $x * $scale;
            $svg .= "\n  <line x1=\"{$scaledX}\" y1=\"0\" x2=\"{$scaledX}\" y2=\"{$svgHeight}\" stroke=\"#ddd\" stroke-width=\"0.5\"/>";
        }
        for ($y = 12; $y < $sheet['height']; $y += 12) {
            $scaledY = $y * $scale;
            $svg .= "\n  <line x1=\"0\" y1=\"{$scaledY}\" x2=\"{$svgWidth}\" y2=\"{$scaledY}\" stroke=\"#ddd\" stroke-width=\"0.5\"/>";
        }

        // Add placed pieces
        $colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#34495e'];
        $colorIndex = 0;

        foreach ($sheet['placements'] as $placement) {
            $x = $placement['x'] * $scale;
            $y = $placement['y'] * $scale;
            $w = $placement['width'] * $scale;
            $h = $placement['height'] * $scale;
            $color = $colors[$colorIndex % count($colors)];
            $colorIndex++;

            // Piece rectangle
            $svg .= <<<PIECE

  <rect x="{$x}" y="{$y}" width="{$w}" height="{$h}" fill="{$color}" fill-opacity="0.7" stroke="#333" stroke-width="1"/>
PIECE;

            // Part label (centered)
            $cx = $x + $w / 2;
            $cy = $y + $h / 2;
            $label = $placement['part'];
            $dims = number_format($placement['original_width'], 2) . '×' . number_format($placement['original_height'], 2);
            $rotated = $placement['rotated'] ? ' (R)' : '';

            // Only show label if piece is large enough
            if ($w > 40 && $h > 30) {
                $svg .= <<<LABEL

  <text x="{$cx}" y="{$cy}" text-anchor="middle" dominant-baseline="middle" font-size="10" fill="white" font-weight="bold">{$label}{$rotated}</text>
  <text x="{$cx}" y="{($cy + 12)}" text-anchor="middle" dominant-baseline="middle" font-size="8" fill="white">{$dims}</text>
LABEL;
            }
        }

        $svg .= "\n</svg>";

        return $svg;
    }

    /**
     * Nest pieces grouped by material thickness
     *
     * @param array $cutList Full cut list from CabinetMathAuditService
     * @return array Nesting results grouped by material
     */
    public function nestCutList(array $cutList, string $sheetSize = '4x8'): array
    {
        $results = [];

        // Group pieces by thickness
        $byThickness = [];
        foreach ($cutList as $section => $data) {
            foreach ($data['pieces'] ?? [] as $piece) {
                $thickness = $piece['thickness'] ?? 0.75;
                $key = number_format($thickness, 4);
                if (!isset($byThickness[$key])) {
                    $byThickness[$key] = [];
                }
                $byThickness[$key][] = $piece;
            }
        }

        // Nest each thickness group
        foreach ($byThickness as $thickness => $pieces) {
            $thicknessLabel = $this->formatThickness((float)$thickness);
            $results[$thicknessLabel] = $this->nestPieces($pieces, $sheetSize);
        }

        return $results;
    }

    /**
     * Format thickness as fraction for display
     */
    protected function formatThickness(float $thickness): string
    {
        // Use string keys to avoid PHP 8.1+ deprecation warnings
        $fractions = [
            '0.25' => '1/4"',
            '0.2500' => '1/4"',
            '0.5' => '1/2"',
            '0.5000' => '1/2"',
            '0.75' => '3/4"',
            '0.7500' => '3/4"',
            '1.0' => '1"',
            '1.0000' => '1"',
        ];

        $key = number_format($thickness, 4);
        return $fractions[$key] ?? number_format($thickness, 2) . '"';
    }
}
