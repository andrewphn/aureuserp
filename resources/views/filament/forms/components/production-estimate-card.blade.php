@if(!$estimate)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
        <div class="text-center text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-xs">Enter linear feet to see estimate</p>
        </div>
    </div>
@else
    @php
        // Get start and desired completion dates from form
        $startDate = $this->data['start_date'] ?? null;
        $desiredDate = $this->data['desired_completion_date'] ?? null;
        $companyId = $this->data['company_id'] ?? null;

        // Calculate project-specific daily rate if dates are provided
        $projectDailyRate = null;
        $projectDays = null;
        $workingDays = null;
        $workingHours = null;
        $rateDifference = null;
        $ratePercentage = null;
        $alertLevel = null;
        $alertMessage = null;
        $alertIcon = null;

        if ($desiredDate && $companyId) {
            // Get company's calendar and working days from database
            $calendar = \DB::selectOne('SELECT id, hours_per_day FROM employees_calendars WHERE company_id = ? AND deleted_at IS NULL LIMIT 1', [$companyId]);

            if ($calendar) {
                // Get working days from calendar_attendances
                $workingDayNames = \DB::select('SELECT DISTINCT LOWER(day_of_week) as day_of_week FROM employees_calendar_attendances WHERE calendar_id = ?', [$calendar->id]);
                $workingDayNames = array_map(fn($row) => $row->day_of_week, $workingDayNames);

                // Map day names to Carbon day of week numbers
                $dayMap = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0];
                $workingDayNumbers = array_map(fn($day) => $dayMap[$day] ?? null, $workingDayNames);
                $workingDayNumbers = array_filter($workingDayNumbers, fn($num) => $num !== null);

                $hoursPerDay = $calendar->hours_per_day ?? 8;
            } else {
                // Fallback to hardcoded Mon-Thu, 8 hours if no calendar found
                $workingDayNumbers = [1, 2, 3, 4]; // Monday-Thursday
                $hoursPerDay = 8;
            }

            // Use start date if provided, otherwise use current date
            $start = $startDate ? \Carbon\Carbon::parse($startDate) : \Carbon\Carbon::now();
            $end = \Carbon\Carbon::parse($desiredDate);

            // Calculate total calendar days
            $projectDays = $start->diffInDays($end);

            if ($projectDays > 0) {
                // Calculate actual working days based on company calendar
                $workingDays = 0;
                $currentDate = $start->copy();

                while ($currentDate->lte($end)) {
                    // Check if current day is in the working days list
                    if (in_array($currentDate->dayOfWeek, $workingDayNumbers)) {
                        $workingDays++;
                    }
                    $currentDate->addDay();
                }

                // Calculate working hours
                $workingHours = $workingDays * $hoursPerDay;

                // Calculate daily rate based on working days
                if ($workingDays > 0) {
                    $projectDailyRate = $linearFeet / $workingDays;
                    $baseRate = $estimate['shop_capacity_per_day'];
                    $rateDifference = $projectDailyRate - $baseRate;
                    $ratePercentage = ($rateDifference / $baseRate) * 100;

                    // Calculate capacity utilization
                    // Base capacity (13.76 LF/day) = 100% = ideal/comfortable workload
                    // Project requires MORE than base = pressure/rush
                    $baseRate = $estimate['shop_capacity_per_day']; // 13.76 LF/day ideal
                    $capacityUtilization = ($projectDailyRate / $baseRate) * 100;

                    // 4-TIER ALERT SYSTEM (based on exceeding ideal capacity)
                    // GREEN: ≤100% capacity (at or below ideal 13.76 LF/day - comfortable)
                    // AMBER: 100-125% capacity (slight pressure, manageable)
                    // RED: 125-150% capacity (extreme pressure, requires overtime)
                    // BLACK: >150% capacity (impossible without major changes)

                    if ($capacityUtilization <= 100) {
                        $alertLevel = 'green';
                        $alertMessage = 'Comfortable Timeline - Within Ideal Capacity';
                        $alertIcon = 'heroicon-o-check-circle';
                    } elseif ($capacityUtilization <= 125) {
                        $alertLevel = 'amber';
                        $alertMessage = 'Slight Pressure - Above Ideal But Manageable';
                        $alertIcon = 'heroicon-o-exclamation-triangle';
                    } elseif ($capacityUtilization <= 150) {
                        $alertLevel = 'red';
                        $alertMessage = 'EXTREME PRESSURE - Requires Overtime/Rush';
                        $alertIcon = 'heroicon-o-fire';
                    } else {
                        $alertLevel = 'black';
                        $alertMessage = 'IMPOSSIBLE - Far Beyond Shop Capacity';
                        $alertIcon = 'heroicon-o-x-circle';
                    }
                }
            }
        }
    @endphp

    <div class="rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 p-6 shadow-lg">
        <!-- Header with capacity info - Enhanced with TCS branding -->
        <div class="mb-6 text-center pb-4 border-b-2 border-gray-200 dark:border-gray-700">
            <div class="inline-flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-[#D4A574]" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <h3 class="text-2xl font-extrabold bg-gradient-to-r from-[#D4A574] to-[#B8935E] bg-clip-text text-transparent">
                    {{ rtrim(rtrim(number_format($linearFeet, 2, '.', ','), '0'), '.') }} LF
                </h3>
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Base Shop Capacity</p>
            <p class="text-lg font-bold text-[#D4A574]">{{ rtrim(rtrim(number_format($estimate['shop_capacity_per_day'], 2, '.', ','), '0'), '.') }} LF/day</p>
        </div>

        @if($projectDailyRate && $alertLevel)
            <!-- 4-TIER COLOR-CODED ALERT SYSTEM -->
            @php
                // Define alert styling based on level using FilamentPHP CSS variables (oklch format)
                $alertStyles = [
                    'green' => [
                        'bgStyle' => 'background-color: var(--success-50); border-color: var(--success-300); box-shadow: 0 0 0 4px color-mix(in oklch, var(--success-500), transparent 80%);',
                        'bgStyleDark' => 'background-color: color-mix(in oklch, var(--success-950), transparent 70%); border-color: var(--success-700); box-shadow: 0 0 0 4px color-mix(in oklch, var(--success-500), transparent 80%);',
                        'borderStyle' => 'border-color: var(--success-300);',
                        'borderStyleDark' => 'border-color: var(--success-700);',
                        'textStyle' => 'color: var(--success-700);',
                        'textStyleDark' => 'color: var(--success-300);',
                        'textBoldStyle' => 'color: var(--success-600);',
                        'textBoldStyleDark' => 'color: var(--success-400);',
                    ],
                    'amber' => [
                        'bgStyle' => 'background-color: var(--warning-50); border-color: var(--warning-300); box-shadow: 0 0 0 4px color-mix(in oklch, var(--warning-500), transparent 80%);',
                        'bgStyleDark' => 'background-color: color-mix(in oklch, var(--warning-950), transparent 70%); border-color: var(--warning-700); box-shadow: 0 0 0 4px color-mix(in oklch, var(--warning-500), transparent 80%);',
                        'borderStyle' => 'border-color: var(--warning-300);',
                        'borderStyleDark' => 'border-color: var(--warning-700);',
                        'textStyle' => 'color: var(--warning-700);',
                        'textStyleDark' => 'color: var(--warning-300);',
                        'textBoldStyle' => 'color: var(--warning-600);',
                        'textBoldStyleDark' => 'color: var(--warning-400);',
                    ],
                    'red' => [
                        'bgStyle' => 'background-color: var(--danger-50); border-color: var(--danger-300); box-shadow: 0 0 0 4px color-mix(in oklch, var(--danger-500), transparent 70%);',
                        'bgStyleDark' => 'background-color: color-mix(in oklch, var(--danger-950), transparent 70%); border-color: var(--danger-700); box-shadow: 0 0 0 4px color-mix(in oklch, var(--danger-500), transparent 70%);',
                        'borderStyle' => 'border-color: var(--danger-300);',
                        'borderStyleDark' => 'border-color: var(--danger-700);',
                        'textStyle' => 'color: var(--danger-700);',
                        'textStyleDark' => 'color: var(--danger-300);',
                        'textBoldStyle' => 'color: var(--danger-600);',
                        'textBoldStyleDark' => 'color: var(--danger-400);',
                    ],
                    'black' => [
                        'bgStyle' => 'background-color: var(--gray-900); border-color: var(--gray-900); box-shadow: 0 0 0 4px color-mix(in oklch, var(--gray-900), transparent 50%);',
                        'bgStyleDark' => 'background-color: var(--gray-950); border-color: var(--gray-950); box-shadow: 0 0 0 4px color-mix(in oklch, var(--gray-950), transparent 50%);',
                        'borderStyle' => 'border-color: var(--gray-900);',
                        'borderStyleDark' => 'border-color: var(--gray-950);',
                        'textStyle' => 'color: white;',
                        'textStyleDark' => 'color: white;',
                        'textBoldStyle' => 'color: white;',
                        'textBoldStyleDark' => 'color: white;',
                    ],
                ];
                $style = $alertStyles[$alertLevel];
            @endphp

            <div class="mb-4 p-4 rounded-lg border-2 dark:hidden" style="{{ $style['bgStyle'] }}">
                <!-- Alert Banner -->
                <div class="flex items-center justify-center gap-2 mb-3 pb-3 border-b" style="{{ $style['borderStyle'] }}">
                    <x-filament::icon
                        :icon="$alertIcon"
                        class="h-6 w-6"
                        style="{{ $style['textBoldStyle'] }}"
                    />
                    <div class="text-sm font-bold uppercase tracking-wide" style="{{ $style['textBoldStyle'] }}">
                        {{ $alertMessage }}
                    </div>
                </div>

                <!-- Timeline Metrics -->
                <div class="text-center">
                    <div class="text-xs font-semibold uppercase tracking-wide mb-1" style="{{ $style['textStyle'] }}">
                        Project Timeline Rate
                    </div>
                    <div class="text-2xl font-bold mb-1" style="{{ $style['textBoldStyle'] }}">
                        {{ rtrim(rtrim(number_format($projectDailyRate, 2, '.', ','), '0'), '.') }} LF/day
                    </div>
                    <div class="text-sm font-semibold mb-2" style="{{ $style['textBoldStyle'] }}">
                        {{ $rateDifference >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($rateDifference, 2, '.', ','), '0'), '.') }} LF/day
                        ({{ $rateDifference >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($ratePercentage, 2, '.', ','), '0'), '.') }}%)
                    </div>
                    <div class="text-xs font-medium mb-2" style="{{ $style['textStyle'] }}">
                        {{ $workingDays }} working days ({{ $workingHours }} hrs) • {{ rtrim(rtrim(number_format($projectDays, 2, '.', ','), '0'), '.') }} calendar days
                    </div>

                    <!-- Capacity Utilization -->
                    <div class="mt-3 pt-3 border-t" style="{{ $style['borderStyle'] }}">
                        <div class="text-xs font-semibold mb-1" style="{{ $style['textStyle'] }}">Capacity Utilization</div>
                        <div class="text-lg font-bold" style="{{ $style['textBoldStyle'] }}">
                            {{ rtrim(rtrim(number_format($capacityUtilization, 1, '.', ','), '0'), '.') }}%
                            <span class="text-xs font-normal">of ideal {{ rtrim(rtrim(number_format($baseRate, 2, '.', ','), '0'), '.') }} LF/day</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Dark mode version -->
            <div class="mb-4 p-4 rounded-lg border-2 hidden dark:block" style="{{ $style['bgStyleDark'] }}">
                <!-- Alert Banner -->
                <div class="flex items-center justify-center gap-2 mb-3 pb-3 border-b" style="{{ $style['borderStyleDark'] }}">
                    <x-filament::icon
                        :icon="$alertIcon"
                        class="h-6 w-6"
                        style="{{ $style['textBoldStyleDark'] }}"
                    />
                    <div class="text-sm font-bold uppercase tracking-wide" style="{{ $style['textBoldStyleDark'] }}">
                        {{ $alertMessage }}
                    </div>
                </div>

                <!-- Timeline Metrics -->
                <div class="text-center">
                    <div class="text-xs font-semibold uppercase tracking-wide mb-1" style="{{ $style['textStyleDark'] }}">
                        Project Timeline Rate
                    </div>
                    <div class="text-2xl font-bold mb-1" style="{{ $style['textBoldStyleDark'] }}">
                        {{ rtrim(rtrim(number_format($projectDailyRate, 2, '.', ','), '0'), '.') }} LF/day
                    </div>
                    <div class="text-sm font-semibold mb-2" style="{{ $style['textBoldStyleDark'] }}">
                        {{ $rateDifference >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($rateDifference, 2, '.', ','), '0'), '.') }} LF/day
                        ({{ $rateDifference >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($ratePercentage, 2, '.', ','), '0'), '.') }}%)
                    </div>
                    <div class="text-xs font-medium mb-2" style="{{ $style['textStyleDark'] }}">
                        {{ $workingDays }} working days ({{ $workingHours }} hrs) • {{ rtrim(rtrim(number_format($projectDays, 2, '.', ','), '0'), '.') }} calendar days
                    </div>

                    <!-- Capacity Utilization -->
                    <div class="mt-3 pt-3 border-t" style="{{ $style['borderStyleDark'] }}">
                        <div class="text-xs font-semibold mb-1" style="{{ $style['textStyleDark'] }}">Capacity Utilization</div>
                        <div class="text-lg font-bold" style="{{ $style['textBoldStyleDark'] }}">
                            {{ rtrim(rtrim(number_format($capacityUtilization, 1, '.', ','), '0'), '.') }}%
                            <span class="text-xs font-normal">of ideal {{ rtrim(rtrim(number_format($baseRate, 2, '.', ','), '0'), '.') }} LF/day</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- TCS Metric Cards - Using reusable x- component -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <!-- Hours - PRIMARY metric (most critical for scheduling) -->
            <x-tcs-metric-card
                label="Production"
                :value="rtrim(rtrim(number_format($estimate['hours'], 1, '.', ','), '0'), '.')"
                unit="Hours"
                icon="heroicon-o-clock"
                gradient="linear-gradient(135deg, #D4A574 0%, #C9995F 100%)"
            />

            <!-- Days - SECONDARY metric (key for timeline planning) -->
            <x-tcs-metric-card
                label="Working"
                :value="rtrim(rtrim(number_format($estimate['days'], 1, '.', ','), '0'), '.')"
                unit="Days"
                icon="heroicon-o-calendar-days"
                gradient="linear-gradient(135deg, #B8935E 0%, #A67F4A 100%)"
            />

            <!-- Weeks - TERTIARY metric (planning reference) -->
            <x-tcs-metric-card
                label="Timeline"
                :value="rtrim(rtrim(number_format($estimate['weeks'], 1, '.', ','), '0'), '.')"
                unit="Weeks"
                icon="heroicon-o-chart-bar"
                gradient="linear-gradient(135deg, #6B4E3D 0%, #5C4033 100%)"
            />

            <!-- Months - CONTEXT metric (long-term view) -->
            <x-tcs-metric-card
                label="Duration"
                :value="rtrim(rtrim(number_format($estimate['months'], 1, '.', ','), '0'), '.')"
                unit="Months"
                icon="heroicon-o-calendar"
                gradient="linear-gradient(135deg, #3D6B20 0%, #2D5016 100%)"
            />
        </div>

        <!-- Enhanced summary footer with TCS branding -->
        <div class="text-center pt-6 pb-2 border-t-2 border-[#D4A574] dark:border-[#B8935E]">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Estimated Production Time</p>
            <p class="text-lg font-bold bg-gradient-to-r from-[#D4A574] to-[#B8935E] bg-clip-text text-transparent">≈ {{ $estimate['formatted'] }}</p>
        </div>
    </div>
@endif
