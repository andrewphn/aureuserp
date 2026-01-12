{{-- Shop Capacity / Production Estimate Widget --}}
{{-- Calculates estimated production time based on LF and project complexity --}}
{{-- Reads work schedule from default calendar in employees_calendars --}}
{{-- Uses hierarchical complexity score when available (cascades from components) --}}
@php
    use Illuminate\Support\Facades\DB;
    use Webkul\Project\Services\ComplexityScoreService;

    $linearFeet = (float) ($this->data['estimated_linear_feet'] ?? 0);
    $specData = $this->data['spec_data'] ?? [];
    $pricingMode = $this->data['pricing_mode'] ?? 'quick';

    // Get default work schedule from employees_calendars
    $defaultCalendar = DB::table('employees_calendars')
        ->where('is_default', true)
        ->where('is_active', true)
        ->first();

    // Calculate LF from spec if in detailed mode
    if ($pricingMode === 'detailed' && !empty($specData)) {
        $linearFeet = 0;
        foreach ($specData as $room) {
            $linearFeet += (float) ($room['linear_feet'] ?? 0);
        }
    }

    // Get pricing selections for complexity calculation
    $cabinetLevel = $this->data['default_cabinet_level'] ?? 2;
    $materialCategory = $this->data['default_material_category'] ?? 'paint_grade';
    $finishOption = $this->data['default_finish_option'] ?? 'unfinished';

    // Check room-level overrides in spec data
    if (!empty($specData)) {
        foreach ($specData as $room) {
            if (!empty($room['cabinet_level'])) $cabinetLevel = $room['cabinet_level'];
            if (!empty($room['material_category'])) $materialCategory = $room['material_category'];
            if (!empty($room['finish_option'])) $finishOption = $room['finish_option'];
            break; // Use first room's settings as default
        }
    }

    // Base hours per linear foot
    $baseHoursPerLF = 2.5;

    // Try to get complexity multiplier from stored project complexity score
    // This is calculated from component-level complexity cascading up
    $projectComplexityScore = $this->getRecord()?->complexity_score ?? null;
    $useStoredComplexity = $projectComplexityScore !== null && $projectComplexityScore > 0;
    $complexityLabel = null;
    $complexityColor = 'gray';

    if ($useStoredComplexity) {
        // Use the stored complexity score from the project
        $complexityService = app(ComplexityScoreService::class);
        $complexityMultiplier = $complexityService->scoreToMultiplier($projectComplexityScore);
        $complexityLabel = $complexityService->scoreToLabel($projectComplexityScore);
        $complexityColor = $complexityService->scoreToColor($projectComplexityScore);

        // Still apply finish multiplier on top (finishing is separate from construction complexity)
        $finishMultiplier = match($finishOption) {
            'unfinished' => 0.7,        // No finishing = fastest
            'prime_only' => 0.85,
            'prime_paint' => 1.0,
            'custom_color' => 1.1,
            'clear_coat' => 0.9,
            'stain_clear' => 1.25,
            'color_match_stain_clear' => 1.4,
            'two_tone' => 1.5,          // Most complex finish
            default => 1.0,
        };

        $totalMultiplier = $complexityMultiplier * $finishMultiplier;
    } else {
        // Fallback: calculate complexity from level/material/finish selections
        $levelMultiplier = match((int) $cabinetLevel) {
            1 => 0.8,   // Basic - simpler construction
            2 => 1.0,   // Standard
            3 => 1.2,   // Enhanced
            4 => 1.4,   // Premium
            5 => 1.6,   // Custom - most complex
            default => 1.0,
        };

        $materialMultiplier = match($materialCategory) {
            'paint_grade' => 1.0,
            'stain_grade' => 1.15,  // More careful work needed
            'premium' => 1.35,      // Premium woods need extra care
            'custom_exotic' => 1.5, // Custom work takes longest
            default => 1.0,
        };

        $finishMultiplier = match($finishOption) {
            'unfinished' => 0.7,        // No finishing = fastest
            'prime_only' => 0.85,
            'prime_paint' => 1.0,
            'custom_color' => 1.1,
            'clear_coat' => 0.9,
            'stain_clear' => 1.25,
            'color_match_stain_clear' => 1.4,
            'two_tone' => 1.5,          // Most complex finish
            default => 1.0,
        };

        $totalMultiplier = $levelMultiplier * $materialMultiplier * $finishMultiplier;
        $complexityMultiplier = $totalMultiplier;
    }

    // Calculate total hours
    $estimatedHours = $linearFeet * $baseHoursPerLF * $totalMultiplier;

    // Get work schedule from default calendar or use fallbacks
    $workingHoursPerDay = $defaultCalendar->hours_per_day ?? 8;
    $fullTimeHours = $defaultCalendar->full_time_required_hours ?? 32;
    $workingDaysPerWeek = $workingHoursPerDay > 0 ? ($fullTimeHours / $workingHoursPerDay) : 4;
    $scheduleName = $defaultCalendar->name ?? 'Standard Schedule';

    // Convert to days and weeks
    $estimatedDays = $estimatedHours > 0 ? ceil($estimatedHours / $workingHoursPerDay) : null;
    $estimatedWeeks = $estimatedDays ? ceil($estimatedDays / $workingDaysPerWeek) : null;

    // Complexity score for display
    $complexityScore = round($totalMultiplier * 10) / 10;
    $displayComplexityScore = $useStoredComplexity ? number_format($projectComplexityScore, 1) : $complexityScore;
@endphp

<div class="space-y-1.5">
    <h4 class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Production Estimate</h4>

    @if($linearFeet > 0 && $estimatedDays)
        <div class="grid grid-cols-2 gap-1.5">
            {{-- Estimated Days --}}
            <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-2 text-center">
                <div class="text-lg font-bold text-primary-600 dark:text-primary-400 tabular-nums leading-tight">
                    {{ $estimatedDays }}
                </div>
                <div class="text-[9px] text-gray-500 dark:text-gray-400 uppercase">Days</div>
            </div>

            {{-- Estimated Hours --}}
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-2 text-center">
                <div class="text-lg font-bold text-gray-700 dark:text-gray-300 tabular-nums leading-tight">
                    {{ number_format($estimatedHours, 0) }}
                </div>
                <div class="text-[9px] text-gray-500 dark:text-gray-400 uppercase">Hours</div>
            </div>
        </div>

        {{-- Breakdown --}}
        <div class="space-y-1 pt-1 text-[10px]">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                <span>Linear Feet</span>
                <span class="font-medium">{{ number_format($linearFeet, 1) }} LF</span>
            </div>
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                <span>Base Rate</span>
                <span class="font-medium">{{ $baseHoursPerLF }} hrs/LF</span>
            </div>
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                <span>Complexity</span>
                @if($useStoredComplexity)
                    <span class="font-medium text-{{ $complexityColor }}-600 dark:text-{{ $complexityColor }}-400" title="{{ $complexityLabel }} ({{ $displayComplexityScore }} pts)">
                        {{ $complexityScore }}x <span class="text-[8px] opacity-75">({{ $complexityLabel }})</span>
                    </span>
                @else
                    <span class="font-medium {{ $complexityScore > 1.3 ? 'text-warning-600' : ($complexityScore > 1.0 ? 'text-primary-600' : 'text-success-600') }}">
                        {{ $complexityScore }}x
                    </span>
                @endif
            </div>
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                <span>Schedule</span>
                <span class="font-medium">{{ (int)$workingDaysPerWeek }}d × {{ (int)$workingHoursPerDay }}h</span>
            </div>
        </div>

        {{-- Weeks estimate for larger projects --}}
        @if($estimatedWeeks > 1)
            <div class="flex items-center justify-between text-[10px] text-gray-600 dark:text-gray-300 pt-1 border-t border-gray-200 dark:border-gray-700">
                <span>Approx. Duration</span>
                <span class="font-semibold">~{{ $estimatedWeeks }} {{ $estimatedWeeks === 1 ? 'week' : 'weeks' }}</span>
            </div>
        @endif
    @else
        <div class="flex items-center gap-1.5 text-[11px] text-gray-400 italic">
            <x-filament::icon icon="heroicon-o-clock" class="h-3.5 w-3.5" />
            <span>Enter linear feet to estimate</span>
        </div>
    @endif
</div>
