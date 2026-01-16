<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Support\Traits\HasFormattedDimensions;

/**
 * Faceframe Eloquent model
 *
 * Represents a face frame structure for a cabinet run.
 * One faceframe per cabinet run (1:1 relationship).
 *
 * TCS Standards (Bryan Patton, Jan 2025):
 * "Face frame... typically is an inch and a half or inch of 3 quarter,
 * then you have an 8th inch gap to your door"
 *
 * Hierarchy: Project → Room → Room Location → Cabinet Run → FaceFrame
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $cabinet_run_id
 * @property string|null $face_frame_type
 * @property string|null $face_frame_thickness
 * @property float|null $face_frame_linear_feet
 * @property float|null $stile_width
 * @property float|null $rail_width
 * @property float|null $material_thickness
 * @property string|null $material
 * @property string|null $finish_option
 * @property string|null $joinery_type
 * @property bool $beaded_face_frame
 * @property string|null $overlay_type
 * @property bool $flush_with_carcass
 * @property float|null $overhang_left
 * @property float|null $overhang_right
 * @property float|null $overhang_top
 * @property float|null $overhang_bottom
 * @property string|null $notes
 * @property int|null $creator_id
 * @property-read \Webkul\Project\Models\CabinetRun $cabinetRun
 * @property-read \Webkul\Security\Models\User|null $creator
 */
class Faceframe extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity, HasFormattedDimensions;

    protected $table = 'projects_faceframes';

    /**
     * TCS Standard Dimensions (Bryan Patton, Jan 2025)
     * "Face frame... typically is an inch and a half"
     */
    public const STANDARD_STILE_WIDTH = 1.5;
    public const STANDARD_RAIL_WIDTH = 1.5;
    public const WIDE_STILE_WIDTH = 1.75; // "or inch of 3 quarter"

    /**
     * TCS Standard Door Gap
     * "then you have an 8th inch gap to your door"
     */
    public const STANDARD_DOOR_GAP = 0.125; // 1/8"

    /**
     * TCS Standard Thickness
     */
    public const STANDARD_THICKNESS = 0.75;  // 3/4"

    /**
     * TCS Minimum Gap for Rail
     * Rails only added when gap between components >= this value
     * If gap < this, just use reveal gap (no rail)
     */
    public const MIN_RAIL_GAP = 0.75; // 3/4"

    /**
     * Face frame types
     */
    public const TYPES = [
        'standard' => 'Standard Face Frame',
        'false_frame' => 'False Frame',
        'beaded' => 'Beaded Face Frame',
    ];

    /**
     * Joinery types
     */
    public const JOINERY_TYPES = [
        'pocket_hole' => 'Pocket Hole',
        'dowel' => 'Dowel',
        'mortise_tenon' => 'Mortise & Tenon',
        'butt_joint' => 'Butt Joint (Glued)',
    ];

    /**
     * Overlay types
     */
    public const OVERLAY_TYPES = [
        'full_overlay' => 'Full Overlay',
        'partial_overlay' => 'Partial Overlay (Traditional)',
        'inset' => 'Inset',
    ];

    protected $fillable = [
        'cabinet_run_id',
        'face_frame_type',
        'face_frame_thickness',
        'face_frame_linear_feet',
        'stile_width',
        'rail_width',
        'material_thickness',
        'material',
        'finish_option',
        'joinery_type',
        'beaded_face_frame',
        'overlay_type',
        'flush_with_carcass',
        'overhang_left',
        'overhang_right',
        'overhang_top',
        'overhang_bottom',
        'notes',
        'creator_id',
    ];

    protected $casts = [
        'face_frame_linear_feet' => 'decimal:2',
        'stile_width' => 'decimal:3',
        'rail_width' => 'decimal:3',
        'material_thickness' => 'decimal:3',
        'beaded_face_frame' => 'boolean',
        'flush_with_carcass' => 'boolean',
        'overhang_left' => 'decimal:3',
        'overhang_right' => 'decimal:3',
        'overhang_top' => 'decimal:3',
        'overhang_bottom' => 'decimal:3',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'cabinetRun.name' => 'Cabinet Run',
        'face_frame_type' => 'Face Frame Type',
        'face_frame_thickness' => 'Face Frame Thickness',
        'face_frame_linear_feet' => 'Face Frame Linear Feet',
        'stile_width' => 'Stile Width',
        'rail_width' => 'Rail Width',
        'material' => 'Material',
        'finish_option' => 'Finish Option',
        'joinery_type' => 'Joinery Type',
        'overlay_type' => 'Overlay Type',
        'notes' => 'Notes',
    ];

    /**
     * Relationships
     */

    /**
     * Cabinet run this faceframe belongs to
     *
     * @return BelongsTo
     */
    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class, 'cabinet_run_id');
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Check if this is a beaded face frame.
     */
    public function isBeaded(): bool
    {
        return $this->beaded_face_frame;
    }

    /**
     * Check if this is an inset frame.
     */
    public function isInset(): bool
    {
        return $this->overlay_type === 'inset';
    }

    /**
     * Get the formatted type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->face_frame_type] ?? ucfirst(str_replace('_', ' ', $this->face_frame_type ?? 'standard'));
    }

    /**
     * Get the formatted joinery label.
     */
    public function getJoineryLabelAttribute(): string
    {
        return self::JOINERY_TYPES[$this->joinery_type] ?? ucfirst(str_replace('_', ' ', $this->joinery_type ?? 'pocket_hole'));
    }

    /**
     * Get the formatted overlay label.
     */
    public function getOverlayLabelAttribute(): string
    {
        return self::OVERLAY_TYPES[$this->overlay_type] ?? ucfirst(str_replace('_', ' ', $this->overlay_type ?? 'full_overlay'));
    }

    /**
     * Calculate opening width given cabinet width.
     *
     * Opening width = Cabinet width - (2 × stile width)
     *
     * @param float $cabinetWidth Cabinet width in inches
     * @return float Opening width in inches
     */
    public function calculateOpeningWidth(float $cabinetWidth): float
    {
        $stileWidth = $this->stile_width ?? self::STANDARD_STILE_WIDTH;
        return $cabinetWidth - (2 * $stileWidth);
    }

    /**
     * Calculate opening height given cabinet height (above toe kick).
     *
     * Opening height = Cabinet face height - (2 × rail width)
     *
     * @param float $faceHeight Face height (cabinet height minus toe kick) in inches
     * @return float Opening height in inches
     */
    public function calculateOpeningHeight(float $faceHeight): float
    {
        $railWidth = $this->rail_width ?? self::STANDARD_RAIL_WIDTH;
        return $faceHeight - (2 * $railWidth);
    }

    /**
     * Calculate door width for a given opening (accounting for gap).
     *
     * Door width = Opening width - (2 × door gap)
     *
     * TCS Standard: "an 8th inch gap to your door"
     *
     * @param float $openingWidth Opening width in inches
     * @return float Door width in inches
     */
    public function calculateDoorWidth(float $openingWidth): float
    {
        return $openingWidth - (2 * self::STANDARD_DOOR_GAP);
    }

    /**
     * Calculate door height for a given opening (accounting for gap).
     *
     * Door height = Opening height - (2 × door gap)
     *
     * @param float $openingHeight Opening height in inches
     * @return float Door height in inches
     */
    public function calculateDoorHeight(float $openingHeight): float
    {
        return $openingHeight - (2 * self::STANDARD_DOOR_GAP);
    }

    /**
     * Calculate all opening dimensions for a cabinet.
     *
     * @param float $cabinetWidth Cabinet width in inches
     * @param float $cabinetHeight Cabinet height in inches
     * @param float $toeKickHeight Toe kick height (default 4.5")
     * @param int $midStileCount Number of mid stiles (0 = single opening)
     * @return array Opening dimensions
     */
    public function calculateOpenings(
        float $cabinetWidth,
        float $cabinetHeight,
        float $toeKickHeight = 4.5,
        int $midStileCount = 0
    ): array {
        $stileWidth = $this->stile_width ?? self::STANDARD_STILE_WIDTH;
        $railWidth = $this->rail_width ?? self::STANDARD_RAIL_WIDTH;

        // Face frame sits on top of toe kick
        $faceHeight = $cabinetHeight - $toeKickHeight;

        // Total stiles = 2 (left + right) + mid stiles
        $totalStileWidth = (2 + $midStileCount) * $stileWidth;

        // Total rails = 2 (top + bottom)
        $totalRailHeight = 2 * $railWidth;

        // Total opening width (divided by number of openings)
        $totalOpeningWidth = $cabinetWidth - $totalStileWidth;
        $openingCount = $midStileCount + 1;
        $singleOpeningWidth = $totalOpeningWidth / $openingCount;

        // Opening height
        $openingHeight = $faceHeight - $totalRailHeight;

        // Door dimensions (with gap)
        $doorWidth = $this->calculateDoorWidth($singleOpeningWidth);
        $doorHeight = $this->calculateDoorHeight($openingHeight);

        return [
            'cabinet_width' => $cabinetWidth,
            'cabinet_height' => $cabinetHeight,
            'toe_kick_height' => $toeKickHeight,
            'face_height' => $faceHeight,
            'stile_width' => $stileWidth,
            'rail_width' => $railWidth,
            'mid_stile_count' => $midStileCount,
            'opening_count' => $openingCount,
            'total_opening_width' => $totalOpeningWidth,
            'single_opening_width' => $singleOpeningWidth,
            'opening_height' => $openingHeight,
            'door_gap' => self::STANDARD_DOOR_GAP,
            'door_width' => $doorWidth,
            'door_height' => $doorHeight,
        ];
    }

    /**
     * Apply TCS defaults to this face frame.
     */
    public function applyTcsDefaults(): self
    {
        $this->stile_width = $this->stile_width ?? self::STANDARD_STILE_WIDTH;
        $this->rail_width = $this->rail_width ?? self::STANDARD_RAIL_WIDTH;
        $this->material_thickness = $this->material_thickness ?? self::STANDARD_THICKNESS;
        $this->joinery_type = $this->joinery_type ?? 'pocket_hole';
        $this->overlay_type = $this->overlay_type ?? 'full_overlay';
        $this->flush_with_carcass = true;

        return $this;
    }

    /**
     * Check if a rail is needed for a given gap.
     *
     * Depends on overlay type:
     * - Full Overlay: Rails only for gaps >= MIN_RAIL_GAP (3/4")
     * - Inset: Rails always needed (define each opening)
     * - Partial Overlay: Rails always needed (frame each opening)
     *
     * @param float $gapSize Gap between components in inches
     * @param string|null $overlayType Overlay type (null uses instance value)
     * @return bool True if a rail should be added
     */
    public function needsRailForGap(float $gapSize, ?string $overlayType = null): bool
    {
        $overlay = $overlayType ?? $this->overlay_type ?? 'full_overlay';

        // Inset and partial overlay always need rails to define openings
        if ($overlay === 'inset' || $overlay === 'partial_overlay') {
            return true;
        }

        // Full overlay: rails only for large gaps
        return $gapSize >= self::MIN_RAIL_GAP;
    }

    /**
     * Static version for use without instance.
     *
     * @param float $gapSize Gap between components in inches
     * @param string $overlayType Overlay type
     * @return bool True if a rail should be added
     */
    public static function needsRailForGapStatic(float $gapSize, string $overlayType = 'full_overlay'): bool
    {
        if ($overlayType === 'inset' || $overlayType === 'partial_overlay') {
            return true;
        }
        return $gapSize >= self::MIN_RAIL_GAP;
    }

    /**
     * Calculate rails needed between components based on gaps.
     *
     * TCS Rule: Rails are added to compensate for gaps >= MIN_RAIL_GAP.
     * Components (doors, drawers, false fronts) determine where rails go.
     *
     * @param array $components Array of components with 'y_bottom' and 'y_top' positions
     * @return array Array of rail positions [['y' => position, 'gap' => gap_size], ...]
     */
    public function calculateRailPositions(array $components): array
    {
        $rails = [];

        if (count($components) < 2) {
            return $rails;
        }

        // Sort by Y position (top to bottom)
        usort($components, fn($a, $b) => ($b['y_top'] ?? 0) <=> ($a['y_top'] ?? 0));

        $railWidth = $this->rail_width ?? self::STANDARD_RAIL_WIDTH;

        for ($i = 0; $i < count($components) - 1; $i++) {
            $upper = $components[$i];
            $lower = $components[$i + 1];

            $upperBottom = $upper['y_bottom'] ?? ($upper['y_top'] - $upper['height']);
            $lowerTop = $lower['y_top'] ?? ($lower['y_bottom'] + $lower['height']);

            $gap = $upperBottom - $lowerTop;

            if (self::needsRailForGap($gap)) {
                // Center rail in the gap
                $railY = $lowerTop + ($gap - $railWidth) / 2;
                $rails[] = [
                    'y' => $railY,
                    'gap' => $gap,
                    'between' => [
                        $upper['name'] ?? 'upper_' . $i,
                        $lower['name'] ?? 'lower_' . ($i + 1),
                    ],
                ];
            }
        }

        return $rails;
    }

    /**
     * Check if bottom rail is needed based on lowest component position.
     *
     * @param array $components Array of components
     * @param float $bottomY Bottom of face frame opening (usually 0)
     * @return bool True if bottom rail needed
     */
    public function needsBottomRail(array $components, float $bottomY = 0): bool
    {
        if (empty($components)) {
            return true; // No components = need rail
        }

        $lowestY = PHP_FLOAT_MAX;
        foreach ($components as $component) {
            $yBottom = $component['y_bottom'] ?? ($component['y_top'] - $component['height']);
            $lowestY = min($lowestY, $yBottom);
        }

        $gap = $lowestY - $bottomY;
        return self::needsRailForGap($gap);
    }

    /**
     * HasFormattedDimensions trait - use stile_width as width
     */
    protected function getWidthField(): string
    {
        return 'stile_width';
    }

    /**
     * Get cut list data for this face frame
     *
     * Returns all pieces needed to fabricate the face frame:
     * - 2x stiles (left and right vertical pieces)
     * - 2x rails (top and bottom horizontal pieces)
     * - Nx mid rails (horizontal dividers between openings)
     *
     * @param float $cabinetWidth Cabinet width in inches
     * @param float $boxHeight Cabinet box height (without toe kick) in inches
     * @param int $midRailCount Number of mid rails (dividers)
     * @return array Cut list with all face frame pieces
     */
    public function getCutListData(float $cabinetWidth, float $boxHeight, int $midRailCount = 0): array
    {
        $stileWidth = $this->stile_width ?? self::STANDARD_STILE_WIDTH;
        $railWidth = $this->rail_width ?? self::STANDARD_RAIL_WIDTH;
        $thickness = $this->material_thickness ?? self::STANDARD_THICKNESS;

        // Stiles run full height of the box
        $stileLength = $boxHeight;

        // Rails span between stiles
        $railLength = $cabinetWidth - (2 * $stileWidth);

        $pieces = [
            [
                'part' => 'Stiles',
                'qty' => 2,
                'width' => $stileWidth,
                'length' => $stileLength,
                'thickness' => $thickness,
                'material' => $this->material ?? '3/4" Hardwood',
                'notes' => 'Left and Right stiles (full height)',
            ],
            [
                'part' => 'Top Rail',
                'qty' => 1,
                'width' => $railWidth,
                'length' => $railLength,
                'thickness' => $thickness,
                'material' => $this->material ?? '3/4" Hardwood',
                'notes' => 'Top rail (spans between stiles)',
            ],
            [
                'part' => 'Bottom Rail',
                'qty' => 1,
                'width' => $railWidth,
                'length' => $railLength,
                'thickness' => $thickness,
                'material' => $this->material ?? '3/4" Hardwood',
                'notes' => 'Bottom rail (spans between stiles)',
            ],
        ];

        if ($midRailCount > 0) {
            $pieces[] = [
                'part' => 'Mid Rails',
                'qty' => $midRailCount,
                'width' => $railWidth,
                'length' => $railLength,
                'thickness' => $thickness,
                'material' => $this->material ?? '3/4" Hardwood',
                'notes' => 'Horizontal dividers between openings',
            ];
        }

        return [
            'face_frame_type' => $this->face_frame_type ?? 'standard',
            'joinery_type' => $this->joinery_type ?? 'pocket_hole',
            'material' => $this->material ?? '3/4" Hardwood/MDF',
            'calculated_dimensions' => [
                'stile_length' => $stileLength,
                'rail_length' => $railLength,
                'stile_width' => $stileWidth,
                'rail_width' => $railWidth,
            ],
            'pieces' => $pieces,
        ];
    }

    /**
     * Get cut list data attribute (uses cabinet run dimensions if available)
     *
     * @return array|null Cut list data or null if no cabinet run
     */
    public function getCutListDataAttribute(): ?array
    {
        // Try to get dimensions from cabinet run
        $cabinetRun = $this->cabinetRun;

        if (!$cabinetRun) {
            return null;
        }

        // Get first cabinet dimensions as reference
        $cabinet = $cabinetRun->cabinets()->first();

        if (!$cabinet) {
            return null;
        }

        $cabinetWidth = $cabinet->length_inches ?? $cabinet->width_inches ?? 24;
        $toeKickHeight = $cabinet->toe_kick_height_inches ?? 4.5;
        $boxHeight = ($cabinet->height_inches ?? 30) - $toeKickHeight;

        // Count drawers for mid rails (drawer_count - 1 mid rails needed)
        $drawerCount = $cabinet->drawer_count ?? 0;
        $midRailCount = max(0, $drawerCount - 1);

        return $this->getCutListData($cabinetWidth, $boxHeight, $midRailCount);
    }
}
