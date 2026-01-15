<?php

namespace App\Services;

use Webkul\Project\Models\CabinetSection;
use Illuminate\Support\Collection;

/**
 * Opening Validator
 *
 * Validates component configurations within cabinet openings.
 *
 * Checks:
 * - Total component height <= opening height
 * - Total component width <= opening width
 * - No overlapping components
 * - Minimum heights respected
 * - Hardware clearances met
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 */
class OpeningValidator
{
    public function __construct(
        private OpeningConfiguratorService $configurator
    ) {}

    /**
     * Validate an entire section's configuration
     */
    public function validateSection(CabinetSection $section): ValidationResult
    {
        $section->load(['drawers', 'shelves', 'doors', 'pullouts', 'falseFronts']);

        $errors = [];
        $warnings = [];

        // Check height overflow
        $heightResult = $this->validateHeight($section);
        $errors = array_merge($errors, $heightResult['errors']);
        $warnings = array_merge($warnings, $heightResult['warnings']);

        // Check width overflow
        $widthResult = $this->validateWidth($section);
        $errors = array_merge($errors, $widthResult['errors']);
        $warnings = array_merge($warnings, $widthResult['warnings']);

        // Check for overlaps
        $overlapResult = $this->validateNoOverlaps($section);
        $errors = array_merge($errors, $overlapResult['errors']);

        // Check minimum heights
        $minHeightResult = $this->validateMinimumHeights($section);
        $errors = array_merge($errors, $minHeightResult['errors']);
        $warnings = array_merge($warnings, $minHeightResult['warnings']);

        // Check component-specific requirements
        $componentResult = $this->validateComponentRequirements($section);
        $errors = array_merge($errors, $componentResult['errors']);
        $warnings = array_merge($warnings, $componentResult['warnings']);

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
            warnings: $warnings,
            section: $section
        );
    }

    /**
     * Validate that components fit within opening height
     */
    public function validateHeight(CabinetSection $section): array
    {
        $openingHeight = $section->opening_height_inches ?? 0;
        $consumedHeight = $section->total_consumed_height_inches ?? 0;

        $errors = [];
        $warnings = [];

        if ($consumedHeight > $openingHeight) {
            $overflow = $consumedHeight - $openingHeight;
            $errors[] = sprintf(
                'Components exceed opening height by %.4f" (%.4f" total in %.4f" opening)',
                $overflow,
                $consumedHeight,
                $openingHeight
            );
        } elseif ($consumedHeight > $openingHeight * 0.95) {
            // Warning if within 5% of limit
            $warnings[] = sprintf(
                'Components use %.1f%% of available height (%.4f" of %.4f")',
                ($consumedHeight / $openingHeight) * 100,
                $consumedHeight,
                $openingHeight
            );
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate that components fit within opening width
     */
    public function validateWidth(CabinetSection $section): array
    {
        $openingWidth = $section->opening_width_inches ?? 0;
        $consumedWidth = $section->total_consumed_width_inches ?? 0;

        $errors = [];
        $warnings = [];

        // Only check if horizontal layout
        if ($section->layout_direction === 'horizontal' && $consumedWidth > $openingWidth) {
            $overflow = $consumedWidth - $openingWidth;
            $errors[] = sprintf(
                'Components exceed opening width by %.4f" (%.4f" total in %.4f" opening)',
                $overflow,
                $consumedWidth,
                $openingWidth
            );
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate no overlapping components
     */
    public function validateNoOverlaps(CabinetSection $section): array
    {
        $errors = [];
        $components = $this->configurator->collectAllComponents($section);

        $positioned = $components->filter(function ($item) {
            return $item['model']->isPositioned();
        });

        $positionedArray = $positioned->values()->all();
        $count = count($positionedArray);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $positionedArray[$i]['model'];
                $b = $positionedArray[$j]['model'];

                // Check vertical overlap
                if ($section->layout_direction !== 'horizontal') {
                    if ($a->overlapsVertically($b)) {
                        $errors[] = sprintf(
                            '%s #%d overlaps with %s #%d vertically',
                            ucfirst($positionedArray[$i]['type']),
                            $a->id,
                            ucfirst($positionedArray[$j]['type']),
                            $b->id
                        );
                    }
                }

                // Check horizontal overlap
                if ($section->layout_direction === 'horizontal') {
                    if ($a->overlapsHorizontally($b)) {
                        $errors[] = sprintf(
                            '%s #%d overlaps with %s #%d horizontally',
                            ucfirst($positionedArray[$i]['type']),
                            $a->id,
                            ucfirst($positionedArray[$j]['type']),
                            $b->id
                        );
                    }
                }
            }
        }

        return ['errors' => $errors];
    }

    /**
     * Validate minimum height requirements
     */
    public function validateMinimumHeights(CabinetSection $section): array
    {
        $errors = [];
        $warnings = [];

        // Check drawer minimum heights
        foreach ($section->drawers as $drawer) {
            $height = $drawer->front_height_inches ?? 0;
            if ($height > 0 && $height < OpeningConfiguratorService::MIN_DRAWER_FRONT_HEIGHT_INCHES) {
                $errors[] = sprintf(
                    'Drawer %s has height %.4f" which is below minimum %.4f"',
                    $drawer->drawer_name ?? '#' . $drawer->id,
                    $height,
                    OpeningConfiguratorService::MIN_DRAWER_FRONT_HEIGHT_INCHES
                );
            }
        }

        // Check shelf opening clearances
        foreach ($section->shelves as $shelf) {
            // If shelf is adjustable, the opening needs minimum clearance
            if ($shelf->shelf_type === 'adjustable') {
                $openingClearance = $shelf->opening_height_inches ?? OpeningConfiguratorService::MIN_SHELF_OPENING_HEIGHT_INCHES;
                if ($openingClearance < OpeningConfiguratorService::MIN_SHELF_OPENING_HEIGHT_INCHES) {
                    $warnings[] = sprintf(
                        'Shelf %s opening clearance %.4f" is below recommended %.4f"',
                        $shelf->shelf_name ?? '#' . $shelf->id,
                        $openingClearance,
                        OpeningConfiguratorService::MIN_SHELF_OPENING_HEIGHT_INCHES
                    );
                }
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate component-specific requirements
     */
    public function validateComponentRequirements(CabinetSection $section): array
    {
        $errors = [];
        $warnings = [];

        // Drawer bank should have consistent widths
        if ($section->section_type === 'drawer_bank' && $section->drawers->count() > 1) {
            $widths = $section->drawers->pluck('front_width_inches')->filter()->unique();
            if ($widths->count() > 1) {
                $warnings[] = sprintf(
                    'Drawer bank has inconsistent drawer widths: %s',
                    $widths->map(fn($w) => $w . '"')->implode(', ')
                );
            }
        }

        // Door sections should have matching heights for pairs
        if ($section->section_type === 'door' && $section->doors->count() === 2) {
            $heights = $section->doors->pluck('height_inches')->filter()->unique();
            if ($heights->count() > 1) {
                $warnings[] = 'Door pair has mismatched heights';
            }
        }

        // Mixed sections need proper ordering
        if ($section->section_type === 'mixed') {
            // Drawers should typically be above doors
            $hasDrawersAboveDoors = false;
            foreach ($section->drawers as $drawer) {
                foreach ($section->doors as $door) {
                    if (($drawer->sort_order ?? 0) > ($door->sort_order ?? 0)) {
                        $hasDrawersAboveDoors = true;
                        break 2;
                    }
                }
            }

            if (!$hasDrawersAboveDoors && $section->drawers->count() > 0 && $section->doors->count() > 0) {
                $warnings[] = 'Mixed section: Drawers are typically positioned above doors';
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Quick check if section fits
     */
    public function fits(CabinetSection $section): bool
    {
        return $this->validateSection($section)->isValid();
    }

    /**
     * Check if a new component would fit
     */
    public function wouldFit(CabinetSection $section, string $type, float $height): bool
    {
        return $this->configurator->canFitComponent($section, $type, $height);
    }
}

/**
 * Validation Result DTO
 */
class ValidationResult
{
    public function __construct(
        public bool $valid,
        public array $errors,
        public array $warnings,
        public ?CabinetSection $section = null
    ) {}

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    public function getSummary(): string
    {
        if ($this->valid && !$this->hasWarnings()) {
            return 'Configuration is valid';
        }

        $parts = [];

        if (!$this->valid) {
            $parts[] = count($this->errors) . ' error(s)';
        }

        if ($this->hasWarnings()) {
            $parts[] = count($this->warnings) . ' warning(s)';
        }

        return implode(', ', $parts);
    }

    /**
     * Check if there's a height overflow error
     */
    public function hasHeightOverflow(): bool
    {
        foreach ($this->errors as $error) {
            if (stripos($error, 'height overflow') !== false ||
                stripos($error, 'exceed opening height') !== false ||
                stripos($error, 'exceeds opening height') !== false ||
                stripos($error, 'exceeds available') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if there's a width overflow error
     */
    public function hasWidthOverflow(): bool
    {
        foreach ($this->errors as $error) {
            if (stripos($error, 'width overflow') !== false ||
                stripos($error, 'exceed opening width') !== false ||
                stripos($error, 'exceeds opening width') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if there are overlapping components
     */
    public function hasOverlaps(): bool
    {
        foreach ($this->errors as $error) {
            if (stripos($error, 'overlap') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the overflow amount if present in errors
     */
    public function getOverflowAmount(): ?float
    {
        foreach ($this->errors as $error) {
            if (preg_match('/exceed.*by\s+([0-9.]+)"/', $error, $matches)) {
                return (float) $matches[1];
            }
            if (preg_match('/overflow[^\d]*([0-9.]+)/', $error, $matches)) {
                return (float) $matches[1];
            }
            if (preg_match('/by\s+([0-9.]+)"/', $error, $matches)) {
                return (float) $matches[1];
            }
        }
        return null;
    }

    /**
     * Get height overflow amount (alias for getOverflowAmount when height overflow exists)
     */
    public function getHeightOverflow(): float
    {
        if ($this->hasHeightOverflow()) {
            return $this->getOverflowAmount() ?? 0.0;
        }
        return 0.0;
    }

    /**
     * Get width overflow amount
     */
    public function getWidthOverflow(): float
    {
        if ($this->hasWidthOverflow()) {
            return $this->getOverflowAmount() ?? 0.0;
        }
        return 0.0;
    }

    /**
     * Get overlapping component pairs
     */
    public function getOverlappingComponents(): array
    {
        $overlaps = [];
        foreach ($this->errors as $error) {
            if (stripos($error, 'overlap') !== false) {
                $overlaps[] = $error;
            }
        }
        return $overlaps;
    }

    /**
     * Check if there's a minimum height violation
     */
    public function hasMinimumHeightViolation(): bool
    {
        foreach ($this->errors as $error) {
            if (stripos($error, 'minimum') !== false && stripos($error, 'height') !== false) {
                return true;
            }
            if (stripos($error, 'below') !== false && stripos($error, 'minimum') !== false) {
                return true;
            }
        }
        foreach ($this->warnings as $warning) {
            if (stripos($warning, 'minimum') !== false && stripos($warning, 'height') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warning messages
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
