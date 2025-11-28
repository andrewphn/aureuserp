@php
    use App\Services\ProductionEstimatorService;
    use Carbon\Carbon;
    use Webkul\Support\Models\Company;

    $company = Company::find($companyId);
    $estimate = ProductionEstimatorService::calculate($linearFeet, $companyId);
    $daysNeeded = $estimate ? ceil($estimate['days']) : 0;

    $start = Carbon::parse($startDate);
    $end = Carbon::parse($completionDate);
    $availableDays = $start->diffInWeekdays($end);

    // Calculate capacity percentage
    $capacityPercent = $availableDays > 0 ? round(($daysNeeded / $availableDays) * 100) : 0;

    // Determine status
    if ($capacityPercent <= 100) {
        $status = 'green';
        $statusLabel = 'Comfortable';
        $statusDescription = 'Timeline has buffer for unexpected delays';
        $bgColor = 'bg-success-50 dark:bg-success-500/10';
        $borderColor = 'border-success-200 dark:border-success-500/20';
        $textColor = 'text-success-700 dark:text-success-300';
    } elseif ($capacityPercent <= 125) {
        $status = 'amber';
        $statusLabel = 'Tight';
        $statusDescription = 'Achievable with focused effort';
        $bgColor = 'bg-warning-50 dark:bg-warning-500/10';
        $borderColor = 'border-warning-200 dark:border-warning-500/20';
        $textColor = 'text-warning-700 dark:text-warning-300';
    } elseif ($capacityPercent <= 150) {
        $status = 'red';
        $statusLabel = 'Overtime Required';
        $statusDescription = 'Will need overtime or additional resources';
        $bgColor = 'bg-danger-50 dark:bg-danger-500/10';
        $borderColor = 'border-danger-200 dark:border-danger-500/20';
        $textColor = 'text-danger-700 dark:text-danger-300';
    } else {
        $status = 'black';
        $statusLabel = 'Not Feasible';
        $statusDescription = 'Timeline needs adjustment';
        $bgColor = 'bg-gray-800 dark:bg-gray-900';
        $borderColor = 'border-gray-700';
        $textColor = 'text-white';
    }
@endphp

<div class="rounded-lg {{ $bgColor }} p-4 border {{ $borderColor }}">
    <div class="flex items-center gap-3 mb-3">
        @if($status === 'green')
            <x-heroicon-o-check-circle class="h-6 w-6 text-success-500" />
        @elseif($status === 'amber')
            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-warning-500" />
        @elseif($status === 'red')
            <x-heroicon-o-clock class="h-6 w-6 text-danger-500" />
        @else
            <x-heroicon-o-x-circle class="h-6 w-6 text-gray-100" />
        @endif
        <div>
            <p class="font-semibold {{ $textColor }}">{{ $statusLabel }}</p>
            <p class="text-xs {{ $textColor }} opacity-75">{{ $statusDescription }}</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 text-center">
        <div>
            <p class="text-lg font-bold {{ $textColor }}">{{ $daysNeeded }}</p>
            <p class="text-xs {{ $textColor }} opacity-75">Days Needed</p>
        </div>
        <div>
            <p class="text-lg font-bold {{ $textColor }}">{{ $availableDays }}</p>
            <p class="text-xs {{ $textColor }} opacity-75">Days Available</p>
        </div>
        <div>
            <p class="text-lg font-bold {{ $textColor }}">{{ $capacityPercent }}%</p>
            <p class="text-xs {{ $textColor }} opacity-75">Capacity</p>
        </div>
    </div>

    @if($company && $company->shop_capacity_per_day)
        <p class="mt-3 text-xs text-center {{ $textColor }} opacity-60">
            Based on {{ $company->shop_capacity_per_day }} LF/day shop capacity
        </p>
    @endif
</div>
