<?php

namespace App\Services;

/**
 * Joint Configurator Service
 *
 * Configures and calculates joint geometry for cabinet construction.
 * Supports multiple joint types at configurable locations.
 *
 * JOINT TYPES:
 * - butt: Square end, parts meet at 90°
 * - miter: 45° angle cut, parts meet at corner
 * - rabbet: L-shaped notch, one part overlaps another
 * - dado: Groove cut into one part to receive another
 * - pocket_screw: Butt joint with pocket screw reinforcement
 * - biscuit: Butt joint with biscuit reinforcement
 * - dowel: Butt joint with dowel reinforcement
 *
 * JOINT LOCATIONS:
 * - stile_to_end_panel: Where face frame stile meets end panel
 * - rail_to_stile: Where face frame rail meets stile
 * - side_to_bottom: Where cabinet side meets bottom
 * - back_to_side: Where back panel meets side
 * - stretcher_to_side: Where stretcher meets side
 */
class JointConfiguratorService
{
    // Joint type constants
    public const JOINT_BUTT = 'butt';
    public const JOINT_MITER = 'miter';
    public const JOINT_RABBET = 'rabbet';
    public const JOINT_DADO = 'dado';
    public const JOINT_POCKET_SCREW = 'pocket_screw';
    public const JOINT_BISCUIT = 'biscuit';
    public const JOINT_DOWEL = 'dowel';

    // Joint location constants
    public const LOC_STILE_TO_END_PANEL = 'stile_to_end_panel';
    public const LOC_RAIL_TO_STILE = 'rail_to_stile';
    public const LOC_SIDE_TO_BOTTOM = 'side_to_bottom';
    public const LOC_BACK_TO_SIDE = 'back_to_side';
    public const LOC_STRETCHER_TO_SIDE = 'stretcher_to_side';
    public const LOC_END_PANEL_TO_CABINET = 'end_panel_to_cabinet';

    /**
     * TCS Default joint configuration
     */
    protected const TCS_DEFAULTS = [
        self::LOC_STILE_TO_END_PANEL => [
            'joint_type' => self::JOINT_MITER,
            'angle' => 45,
            'reveal' => 0,        // No reveal, flush miter
            'notes' => 'Stile outside edge mitered to end panel front edge',
        ],
        self::LOC_RAIL_TO_STILE => [
            'joint_type' => self::JOINT_POCKET_SCREW,
            'angle' => 90,
            'reveal' => 0,
            'notes' => 'Rails pocket screwed into stiles',
        ],
        self::LOC_SIDE_TO_BOTTOM => [
            'joint_type' => self::JOINT_DADO,
            'angle' => 90,
            'dado_depth' => 0.375,  // 3/8" dado
            'notes' => 'Bottom sits in dado cut in sides',
        ],
        self::LOC_BACK_TO_SIDE => [
            'joint_type' => self::JOINT_RABBET,
            'angle' => 90,
            'rabbet_depth' => 0.375,
            'rabbet_width' => 0.75,
            'notes' => 'Back sits in rabbet cut in sides',
        ],
        self::LOC_STRETCHER_TO_SIDE => [
            'joint_type' => self::JOINT_BUTT,
            'angle' => 90,
            'notes' => 'Stretcher butts against or sits on top of sides',
        ],
        self::LOC_END_PANEL_TO_CABINET => [
            'joint_type' => self::JOINT_BUTT,
            'angle' => 90,
            'gap' => 0.25,        // 1/4" gap for installation
            'notes' => 'End panel attached to cabinet side with gap',
        ],
    ];

    /**
     * Current joint configuration (can be customized)
     */
    protected array $jointConfig = [];

    public function __construct()
    {
        $this->jointConfig = self::TCS_DEFAULTS;
    }

    /**
     * Get joint configuration for a specific location.
     */
    public function getJointConfig(string $location): array
    {
        return $this->jointConfig[$location] ?? [
            'joint_type' => self::JOINT_BUTT,
            'angle' => 90,
            'notes' => 'Default butt joint',
        ];
    }

    /**
     * Set joint configuration for a specific location.
     */
    public function setJointConfig(string $location, array $config): self
    {
        $this->jointConfig[$location] = array_merge(
            $this->jointConfig[$location] ?? [],
            $config
        );
        return $this;
    }

    /**
     * Set joint type for a specific location.
     */
    public function setJointType(string $location, string $jointType): self
    {
        $this->jointConfig[$location]['joint_type'] = $jointType;
        return $this;
    }

    /**
     * Get all joint configurations.
     */
    public function getAllJointConfigs(): array
    {
        return $this->jointConfig;
    }

    /**
     * Reset to TCS defaults.
     */
    public function resetToDefaults(): self
    {
        $this->jointConfig = self::TCS_DEFAULTS;
        return $this;
    }

    /**
     * Load joint configuration from array (e.g., from database or specs).
     */
    public function loadFromArray(array $config): self
    {
        foreach ($config as $location => $jointConfig) {
            $this->setJointConfig($location, $jointConfig);
        }
        return $this;
    }

    // ========================================
    // JOINT GEOMETRY CALCULATIONS
    // ========================================

    /**
     * Calculate miter joint geometry.
     *
     * For stile-to-end-panel miter:
     * - Stile's outside edge is cut at 45° from front to back
     * - End panel's front edge is cut at 45° from inside to outside
     * - They meet at the outer front corner
     *
     * @param float $thickness1 First part thickness
     * @param float $thickness2 Second part thickness
     * @param float $angle Miter angle (default 45°)
     * @return array Miter geometry data
     */
    public function calculateMiterGeometry(float $thickness1, float $thickness2, float $angle = 45): array
    {
        $angleRad = deg2rad($angle);

        // For a 45° miter, the cut length equals the thickness
        $cutLength1 = $thickness1 / sin($angleRad);
        $cutLength2 = $thickness2 / sin($angleRad);

        return [
            'angle' => $angle,
            'angle_rad' => $angleRad,
            'part1' => [
                'thickness' => $thickness1,
                'cut_length' => $cutLength1,
                'cut_offset' => $thickness1,  // How far the miter extends
            ],
            'part2' => [
                'thickness' => $thickness2,
                'cut_length' => $cutLength2,
                'cut_offset' => $thickness2,
            ],
            'joint_line' => [
                'length' => max($cutLength1, $cutLength2),
                'angle' => $angle,
            ],
        ];
    }

    /**
     * Calculate stile-to-end-panel miter positions.
     *
     * @param array $stilePosition Stile position [x, y, z]
     * @param array $stileDimensions Stile dimensions [w, h, d]
     * @param array $endPanelPosition End panel position [x, y, z]
     * @param array $endPanelDimensions End panel dimensions [w, h, d]
     * @param string $side 'left' or 'right'
     * @return array Miter joint geometry
     */
    public function calculateStileEndPanelMiter(
        array $stilePosition,
        array $stileDimensions,
        array $endPanelPosition,
        array $endPanelDimensions,
        string $side = 'left'
    ): array {
        $config = $this->getJointConfig(self::LOC_STILE_TO_END_PANEL);
        $angle = $config['angle'] ?? 45;

        // Calculate miter geometry
        $miterGeom = $this->calculateMiterGeometry(
            $stileDimensions['d'],      // Stile depth (face frame thickness)
            $endPanelDimensions['w'],   // End panel thickness
            $angle
        );

        // Miter line position (vertical line at corner)
        if ($side === 'left') {
            $miterX = $stilePosition['x'];  // Outside edge of left stile
        } else {
            $miterX = $stilePosition['x'] + $stileDimensions['w'];  // Outside edge of right stile
        }

        return [
            'joint_type' => $config['joint_type'],
            'angle' => $angle,
            'side' => $side,
            'miter_line' => [
                'x' => $miterX,
                'y_start' => 0,  // Front face (Z=0 in our system)
                'z_start' => $stilePosition['y'],  // Bottom of stile
                'z_end' => $stilePosition['y'] + $stileDimensions['h'],  // Top of stile
            ],
            'stile_cut' => [
                'edge' => $side === 'left' ? 'outside' : 'outside',
                'direction' => 'front_to_back',
                'angle' => $angle,
                'depth' => $stileDimensions['d'],
            ],
            'end_panel_cut' => [
                'edge' => 'front',
                'direction' => $side === 'left' ? 'inside_to_outside' : 'outside_to_inside',
                'angle' => $angle,
                'depth' => $endPanelDimensions['w'],
            ],
            'geometry' => $miterGeom,
            'notes' => $config['notes'] ?? '',
        ];
    }

    /**
     * Calculate dado joint geometry.
     */
    public function calculateDadoGeometry(float $panelThickness, float $dadoDepth = 0.375): array
    {
        return [
            'joint_type' => self::JOINT_DADO,
            'dado_width' => $panelThickness,
            'dado_depth' => $dadoDepth,
            'panel_in_dado' => $panelThickness - $dadoDepth,  // How much panel extends past dado
            'notes' => "Dado: {$panelThickness}\" wide x {$dadoDepth}\" deep",
        ];
    }

    /**
     * Calculate rabbet joint geometry.
     */
    public function calculateRabbetGeometry(float $panelThickness, float $rabbetDepth = 0.375, float $rabbetWidth = 0.75): array
    {
        return [
            'joint_type' => self::JOINT_RABBET,
            'rabbet_width' => $rabbetWidth,
            'rabbet_depth' => $rabbetDepth,
            'panel_thickness' => $panelThickness,
            'notes' => "Rabbet: {$rabbetWidth}\" wide x {$rabbetDepth}\" deep",
        ];
    }

    // ========================================
    // 3D GEOMETRY GENERATION FOR MITERED PARTS
    // ========================================

    /**
     * Calculate miter joint profile vertices for two parts meeting at a corner.
     *
     * MITER JOINT GEOMETRY:
     * - Two parts meet at an outside corner
     * - Each part has material removed at 45° angle
     * - The miter line runs diagonally from corner inward
     * - Both parts share the same miter line
     *
     * For stile (vertical) meeting end panel (horizontal) at front-outside corner:
     *
     *     TOP VIEW - LEFT SIDE (looking down at cabinet):
     *
     *          Y (depth/back)
     *          ^
     *          |
     *          |    STILE (face frame)
     *     1.0" |    ┌──────────────┐ stile_inner_x (0.75)
     *          |    │              │
     *          |    │   STILE      │
     *          |    │   1.75" w    │
     *     0.75"|----│----..........│ miter_y (end panel thickness)
     *          |    │   .          │
     *          |    │  .           │ <-- STILE KEEPS THIS
     *          |    │ .  (removed) │
     *          |    │.             │
     *        0 └────*──────────────┼──> X
     *               │              │
     *          EP   │ END PANEL    stile_outer_x (-1.0)
     *          0.75"│ KEEPS THIS   │
     *               │              │
     *          outer_x (-1.0)    inner_x (-0.25)
     *
     * STILE MITER: removes triangle from front-outside corner
     *   - Corner at (outer_x, 0)
     *   - Miter cuts to (outer_x + miter_depth, miter_depth)
     *   - For 45°: miter_depth = end_panel_thickness
     *
     * END PANEL MITER: removes triangle from front-inside corner
     *   - Corner at (inner_x, 0)
     *   - Miter cuts to (outer_x, miter_depth)
     *
     * @param float $stileOuterX X position of stile outside edge
     * @param float $stileInnerX X position of stile inside edge
     * @param float $stileDepth Depth of stile (face frame thickness)
     * @param float $endPanelThickness Thickness of end panel
     * @param float $endPanelDepth Depth of end panel
     * @param string $side 'left' or 'right'
     * @return array Profile vertices for both stile and end panel
     */
    public function calculateMiterProfiles(
        float $stileOuterX,
        float $stileInnerX,
        float $stileDepth,
        float $endPanelThickness,
        float $endPanelDepth,
        string $side
    ): array {
        // Miter depth equals end panel thickness for 45° miter
        $miterDepth = $endPanelThickness;

        if ($side === 'left') {
            // LEFT SIDE: stile outer edge is at smaller X (left)
            // End panel sits to the LEFT of the cabinet
            $endPanelOuterX = $stileOuterX;  // Align outer edges
            $endPanelInnerX = $stileOuterX + $endPanelThickness;  // Inner edge toward cabinet

            // Miter point: where the 45° line ends
            // From stile outer corner (outer_x, 0), go +miterDepth in X, +miterDepth in Y
            $miterPointX = $stileOuterX + $miterDepth;
            $miterPointY = $miterDepth;

            // STILE profile - the miter removes front-outside triangle
            // Profile goes: front-inside -> back-inside -> back-outside -> miter-outside -> miter-front -> close
            $stileProfile = [
                ['x' => $stileInnerX, 'y' => 0],           // P1: Front-inside corner
                ['x' => $stileInnerX, 'y' => $stileDepth], // P2: Back-inside corner
                ['x' => $stileOuterX, 'y' => $stileDepth], // P3: Back-outside corner
                ['x' => $stileOuterX, 'y' => $miterPointY], // P4: Where miter meets outside edge
                ['x' => $miterPointX, 'y' => 0],           // P5: Where miter meets front face
                ['x' => $stileInnerX, 'y' => 0],           // P6: Close to P1
            ];

            // END PANEL profile - the miter removes front-outside triangle
            // Profile goes: miter-outside -> back-outside -> back-inside -> miter-front -> close
            // Note: miterPointX == endPanelInnerX for 45° miter with matching thickness
            $endPanelProfile = [
                ['x' => $endPanelOuterX, 'y' => $miterPointY], // P1: Where miter meets outside edge
                ['x' => $endPanelOuterX, 'y' => $endPanelDepth], // P2: Back-outside corner
                ['x' => $miterPointX, 'y' => $endPanelDepth],   // P3: Back-inside corner
                ['x' => $miterPointX, 'y' => 0],                // P4: Front-inside = miter front point
                ['x' => $endPanelOuterX, 'y' => $miterPointY],  // P5: Close to P1
            ];
        } else {
            // RIGHT SIDE: stile outer edge is at larger X (right)
            // End panel sits to the RIGHT of the cabinet
            $endPanelOuterX = $stileOuterX;  // Align outer edges
            $endPanelInnerX = $stileOuterX - $endPanelThickness;  // Inner edge toward cabinet

            // Miter point: where the 45° line ends
            // From stile outer corner (outer_x, 0), go -miterDepth in X, +miterDepth in Y
            $miterPointX = $stileOuterX - $miterDepth;
            $miterPointY = $miterDepth;

            // STILE profile - the miter removes front-outside triangle
            // Profile goes: front-inside -> miter-front -> miter-outside -> back-outside -> back-inside -> close
            $stileProfile = [
                ['x' => $stileInnerX, 'y' => 0],           // P1: Front-inside corner
                ['x' => $miterPointX, 'y' => 0],           // P2: Where miter meets front face
                ['x' => $stileOuterX, 'y' => $miterPointY], // P3: Where miter meets outside edge
                ['x' => $stileOuterX, 'y' => $stileDepth], // P4: Back-outside corner
                ['x' => $stileInnerX, 'y' => $stileDepth], // P5: Back-inside corner
                ['x' => $stileInnerX, 'y' => 0],           // P6: Close to P1
            ];

            // END PANEL profile - the miter removes front-inside triangle
            // Profile goes: miter-front -> miter-outside -> back-outside -> back-inside -> close
            $endPanelProfile = [
                ['x' => $miterPointX, 'y' => 0],                // P1: Front-inside = miter front point
                ['x' => $endPanelOuterX, 'y' => $miterPointY],  // P2: Where miter meets outside edge
                ['x' => $endPanelOuterX, 'y' => $endPanelDepth], // P3: Back-outside corner
                ['x' => $miterPointX, 'y' => $endPanelDepth],   // P4: Back-inside corner
                ['x' => $miterPointX, 'y' => 0],                // P5: Close to P1
            ];
        }

        return [
            'stile' => [
                'profile' => $stileProfile,
                'outer_x' => $stileOuterX,
                'inner_x' => $stileInnerX,
                'depth' => $stileDepth,
            ],
            'end_panel' => [
                'profile' => $endPanelProfile,
                'outer_x' => $endPanelOuterX,
                'inner_x' => $endPanelInnerX,
                'depth' => $endPanelDepth,
            ],
            'miter' => [
                'corner_stile' => ['x' => $stileOuterX, 'y' => 0],
                'corner_endpanel' => ['x' => $side === 'left' ? $endPanelInnerX : $endPanelInnerX, 'y' => 0],
                'miter_point' => ['x' => $miterPointX, 'y' => $miterPointY],
                'angle' => 45,
                'depth' => $miterDepth,
            ],
        ];
    }

    /**
     * Generate 3D points for a mitered stile.
     *
     * @deprecated Use calculateMiterProfiles() instead for accurate geometry
     */
    public function generateMiteredStileProfile(array $dimensions, string $side, float $angle = 45): array
    {
        $w = $dimensions['w'];
        $d = $dimensions['d'];

        if ($side === 'left') {
            return [
                ['x' => 0, 'y' => $d],
                ['x' => $w, 'y' => $d],
                ['x' => $w, 'y' => 0],
                ['x' => $d, 'y' => 0],
                ['x' => 0, 'y' => $d],
            ];
        } else {
            return [
                ['x' => 0, 'y' => 0],
                ['x' => 0, 'y' => $d],
                ['x' => $w, 'y' => $d],
                ['x' => $w - $d, 'y' => 0],
                ['x' => 0, 'y' => 0],
            ];
        }
    }

    /**
     * Generate 3D points for a mitered end panel.
     *
     * @deprecated Use calculateMiterProfiles() instead for accurate geometry
     */
    public function generateMiteredEndPanelProfile(array $dimensions, string $side, float $angle = 45): array
    {
        $w = $dimensions['w'];
        $d = $dimensions['d'];

        if ($side === 'left') {
            return [
                ['x' => 0, 'y' => 0],
                ['x' => 0, 'y' => $d],
                ['x' => $w, 'y' => $d],
                ['x' => $w, 'y' => $w],
                ['x' => 0, 'y' => 0],
            ];
        } else {
            return [
                ['x' => $w, 'y' => 0],
                ['x' => 0, 'y' => $w],
                ['x' => 0, 'y' => $d],
                ['x' => $w, 'y' => $d],
                ['x' => $w, 'y' => 0],
            ];
        }
    }

    /**
     * Get CNC machining instructions for a joint.
     */
    public function getCncInstructions(string $location): array
    {
        $config = $this->getJointConfig($location);
        $jointType = $config['joint_type'];

        return match ($jointType) {
            self::JOINT_MITER => [
                'operation' => 'miter_cut',
                'tool' => '45_degree_chamfer_bit',
                'angle' => $config['angle'] ?? 45,
                'pass_depth' => 0.25,  // Multiple passes
            ],
            self::JOINT_DADO => [
                'operation' => 'dado_cut',
                'tool' => 'straight_bit',
                'width' => $config['dado_width'] ?? 0.75,
                'depth' => $config['dado_depth'] ?? 0.375,
            ],
            self::JOINT_RABBET => [
                'operation' => 'rabbet_cut',
                'tool' => 'rabbet_bit',
                'width' => $config['rabbet_width'] ?? 0.75,
                'depth' => $config['rabbet_depth'] ?? 0.375,
            ],
            self::JOINT_POCKET_SCREW => [
                'operation' => 'pocket_hole',
                'tool' => 'pocket_hole_jig',
                'angle' => 15,  // Standard pocket screw angle
                'spacing' => 6,  // Inches between screws
            ],
            default => [
                'operation' => 'none',
                'notes' => 'Butt joint - no machining required',
            ],
        };
    }

    /**
     * Export joint configuration to array for saving.
     */
    public function toArray(): array
    {
        return [
            'joints' => $this->jointConfig,
            'version' => '1.0',
        ];
    }

    /**
     * Get available joint types.
     */
    public static function getAvailableJointTypes(): array
    {
        return [
            self::JOINT_BUTT => 'Butt Joint (square ends)',
            self::JOINT_MITER => 'Miter Joint (45° angle)',
            self::JOINT_RABBET => 'Rabbet Joint (L-notch)',
            self::JOINT_DADO => 'Dado Joint (groove)',
            self::JOINT_POCKET_SCREW => 'Pocket Screw Joint',
            self::JOINT_BISCUIT => 'Biscuit Joint',
            self::JOINT_DOWEL => 'Dowel Joint',
        ];
    }

    /**
     * Get available joint locations.
     */
    public static function getAvailableLocations(): array
    {
        return [
            self::LOC_STILE_TO_END_PANEL => 'Stile to End Panel',
            self::LOC_RAIL_TO_STILE => 'Rail to Stile',
            self::LOC_SIDE_TO_BOTTOM => 'Side to Bottom',
            self::LOC_BACK_TO_SIDE => 'Back to Side',
            self::LOC_STRETCHER_TO_SIDE => 'Stretcher to Side',
            self::LOC_END_PANEL_TO_CABINET => 'End Panel to Cabinet',
        ];
    }
}
