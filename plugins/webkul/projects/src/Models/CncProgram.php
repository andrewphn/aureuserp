<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\User;

/**
 * CNC Program Model
 *
 * Tracks CNC cutting programs for cabinet production including:
 * - VCarve project files and G-code output
 * - Material usage estimates vs actual (from nesting)
 * - Sheet utilization and waste tracking
 *
 * Workflow: LF → BOM (sqft estimate) → CNC Nesting → Actual Sheet Count
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string|null $vcarve_file
 * @property string|null $material_code
 * @property string|null $material_type
 * @property string|null $sheet_size
 * @property int|null $sheet_count
 * @property int|null $sheets_estimated
 * @property float|null $sqft_estimated
 * @property int|null $sheets_actual
 * @property float|null $sqft_actual
 * @property float|null $utilization_percentage
 * @property float|null $waste_sqft
 * @property array|null $nesting_details
 * @property int|null $sheets_variance
 * @property \Carbon\Carbon|null $nested_at
 * @property int|null $nested_by_user_id
 * @property \Carbon\Carbon|null $created_date
 * @property string|null $description
 * @property string $status
 * @property int $creator_id
 */
class CncProgram extends Model
{
    protected $table = 'projects_cnc_programs';

    protected $fillable = [
        'project_id',
        'name',
        'vcarve_file',
        'material_code',
        'material_type',
        'sheet_size',
        'sheet_count',
        'sheets_estimated',
        'sqft_estimated',
        'sheets_actual',
        'sqft_actual',
        'utilization_percentage',
        'waste_sqft',
        'nesting_details',
        'sheets_variance',
        'nested_at',
        'nested_by_user_id',
        'created_date',
        'description',
        'status',
        'creator_id',
    ];

    protected $casts = [
        'created_date' => 'date',
        'nested_at' => 'datetime',
        'nesting_details' => 'array',
        'sqft_estimated' => 'decimal:2',
        'sqft_actual' => 'decimal:2',
        'utilization_percentage' => 'decimal:2',
        'waste_sqft' => 'decimal:2',
    ];

    /**
     * Status options for CNC programs
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_ERROR = 'error';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function nestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nested_by_user_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(CncProgramPart::class, 'cnc_program_id');
    }

    // =========================================================================
    // BOM Estimate Methods
    // =========================================================================

    /**
     * Set the BOM estimate from linear feet calculation
     *
     * @param float $linearFeet Total linear feet for this material
     * @param float $sqftPerLf Square feet per linear foot conversion
     * @param string $sheetSize Sheet dimensions (e.g., "48x96")
     */
    public function setEstimateFromLf(float $linearFeet, float $sqftPerLf, string $sheetSize = '48x96'): void
    {
        $this->sqft_estimated = $linearFeet * $sqftPerLf;
        $this->sheet_size = $sheetSize;

        // Calculate sheet dimensions
        $sheetSqft = $this->getSheetSqft($sheetSize);
        $this->sheets_estimated = (int) ceil($this->sqft_estimated / $sheetSqft);

        $this->save();
    }

    /**
     * Get square footage of a sheet from size string
     */
    protected function getSheetSqft(string $sheetSize): float
    {
        // Parse "48x96" format (inches)
        if (preg_match('/(\d+)x(\d+)/', $sheetSize, $matches)) {
            $width = (int) $matches[1];
            $length = (int) $matches[2];
            return ($width * $length) / 144; // Convert sq inches to sq feet
        }

        // Default 4x8 sheet = 32 sqft
        return 32.0;
    }

    // =========================================================================
    // Nesting Results Methods
    // =========================================================================

    /**
     * Record nesting results from VCarve
     *
     * @param int $sheetsUsed Actual sheets used after nesting
     * @param float $utilizationPct Sheet utilization percentage
     * @param array|null $details Per-sheet breakdown details
     */
    public function recordNestingResults(
        int $sheetsUsed,
        float $utilizationPct,
        ?array $details = null
    ): void {
        $this->sheets_actual = $sheetsUsed;
        $this->utilization_percentage = $utilizationPct;
        $this->nesting_details = $details;
        $this->nested_at = now();
        $this->nested_by_user_id = auth()->id();

        // Calculate actual sqft used
        $sheetSqft = $this->getSheetSqft($this->sheet_size ?? '48x96');
        $this->sqft_actual = $sheetsUsed * $sheetSqft * ($utilizationPct / 100);

        // Calculate waste
        $totalSheetArea = $sheetsUsed * $sheetSqft;
        $this->waste_sqft = $totalSheetArea - $this->sqft_actual;

        // Calculate variance (negative = saved sheets)
        if ($this->sheets_estimated) {
            $this->sheets_variance = $sheetsUsed - $this->sheets_estimated;
        }

        $this->status = self::STATUS_COMPLETE;
        $this->save();
    }

    /**
     * Quick record from VCarve reference sheet data
     *
     * @param array $vcarveData Parsed data from VCarve HTML reference
     */
    public function recordFromVcarveReference(array $vcarveData): void
    {
        $this->recordNestingResults(
            sheetsUsed: $vcarveData['sheet_count'] ?? 1,
            utilizationPct: $vcarveData['utilization'] ?? 0,
            details: $vcarveData
        );
    }

    // =========================================================================
    // Accessors & Helpers
    // =========================================================================

    /**
     * Get sheets saved (positive) or over-used (negative)
     */
    public function getSheetsSavedAttribute(): ?int
    {
        if ($this->sheets_variance === null) {
            return null;
        }
        return -$this->sheets_variance; // Invert: negative variance = saved
    }

    /**
     * Check if estimate was accurate (within 1 sheet)
     */
    public function isEstimateAccurate(): bool
    {
        if ($this->sheets_variance === null) {
            return false;
        }
        return abs($this->sheets_variance) <= 1;
    }

    /**
     * Get efficiency rating
     */
    public function getEfficiencyRating(): string
    {
        if ($this->utilization_percentage === null) {
            return 'unknown';
        }

        return match (true) {
            $this->utilization_percentage >= 85 => 'excellent',
            $this->utilization_percentage >= 75 => 'good',
            $this->utilization_percentage >= 65 => 'fair',
            default => 'poor',
        };
    }

    /**
     * Format summary for display
     */
    public function getNestingSummaryAttribute(): string
    {
        if (!$this->sheets_actual) {
            if ($this->sheets_estimated) {
                return "Estimated: {$this->sheets_estimated} sheets";
            }
            return 'Not nested yet';
        }

        $summary = "{$this->sheets_actual} sheets";

        if ($this->utilization_percentage) {
            $summary .= " @ {$this->utilization_percentage}%";
        }

        if ($this->sheets_variance !== null && $this->sheets_variance !== 0) {
            $diff = abs($this->sheets_variance);
            $direction = $this->sheets_variance > 0 ? 'over' : 'under';
            $summary .= " ({$diff} {$direction} estimate)";
        }

        return $summary;
    }
}
