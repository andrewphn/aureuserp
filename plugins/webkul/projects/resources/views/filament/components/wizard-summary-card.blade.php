@php
    use Webkul\Partner\Models\Partner;
    use Webkul\Support\Models\Company;
    use Webkul\Project\Enums\LeadSource;
    use Webkul\Project\Enums\BudgetRange;
    use App\Services\ProductionEstimatorService;

    // Get related data
    $company = !empty($data('company_id')) ? Company::find($data('company_id')) : null;
    $partner = !empty($data('partner_id')) ? Partner::find($data('partner_id')) : null;

    $projectNumber = $data('project_number') ?: 'Auto-generated';
    $projectName = $data('name') ?: 'Auto-generated';
    $projectType = ucfirst($data('project_type') ?? 'Not set');
    $leadSource = $data('lead_source') ? LeadSource::label($data('lead_source')) : 'Not set';
    $budgetRange = $data('budget_range') ? BudgetRange::label($data('budget_range')) : 'Not set';
    $linearFeet = $data('estimated_linear_feet') ?: 'Not set';
    $complexityScore = $data('complexity_score') ?: 'Not set';
    $startDate = $data('start_date') ? \Carbon\Carbon::parse($data('start_date'))->format('M j, Y') : 'Not set';
    $completionDate = $data('desired_completion_date') ? \Carbon\Carbon::parse($data('desired_completion_date'))->format('M j, Y') : 'Not set';

    // Calculate estimate
    $estimate = null;
    if ($linearFeet !== 'Not set' && $company) {
        $estimate = ProductionEstimatorService::calculate($linearFeet, $company->id);
    }
@endphp

<div class="space-y-4">
    {{-- Header --}}
    <div class="rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 p-4 text-white">
        <div class="flex items-center gap-3">
            <x-heroicon-o-building-office class="h-8 w-8" />
            <div>
                <p class="text-lg font-bold">{{ $projectNumber }}</p>
                <p class="text-sm opacity-90">{{ $projectName }}</p>
            </div>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-2 gap-4">
        {{-- Customer & Company --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Customer & Company</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user class="h-4 w-4 text-gray-400" />
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $partner?->name ?? 'Not selected' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-building-office-2 class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $company?->name ?? 'Not selected' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-megaphone class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $leadSource }}</span>
                </div>
            </div>
        </div>

        {{-- Project Type & Scope --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Type & Scope</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-home class="h-4 w-4 text-gray-400" />
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $projectType }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrows-right-left class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ is_numeric($linearFeet) ? number_format($linearFeet, 1) . ' LF' : $linearFeet }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-currency-dollar class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $budgetRange }}</span>
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Timeline</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-play class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-600 dark:text-gray-300">Start: {{ $startDate }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-flag class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-600 dark:text-gray-300">Completion: {{ $completionDate }}</span>
                </div>
                @if($estimate)
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clock class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-600 dark:text-gray-300">Est. {{ $estimate['formatted'] }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Address --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Location</p>
            <div class="flex items-start gap-2">
                <x-heroicon-o-map-pin class="h-4 w-4 text-gray-400 mt-0.5" />
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    @if($data('project_address.street1'))
                        <p>{{ $data('project_address.street1') }}</p>
                        @if($data('project_address.city') || $data('project_address.zip'))
                            <p>{{ $data('project_address.city') }}{{ $data('project_address.city') && $data('project_address.zip') ? ', ' : '' }}{{ $data('project_address.zip') }}</p>
                        @endif
                    @else
                        <p>No address set</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Estimate Box --}}
    @if($estimate && is_numeric($linearFeet))
        @php
            $baseRate = 348;
            $quickEstimate = $linearFeet * $baseRate;
        @endphp
        <div class="rounded-lg bg-success-50 dark:bg-success-500/10 border border-success-200 dark:border-success-500/20 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-success-700 dark:text-success-300 uppercase tracking-wide">Estimated Value</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400">${{ number_format($quickEstimate) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-success-600 dark:text-success-400">{{ number_format($linearFeet, 1) }} LF @ ${{ $baseRate }}/LF</p>
                    <p class="text-xs text-success-600 dark:text-success-400 opacity-75">Production: {{ $estimate['formatted'] }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Description Preview --}}
    @if($data('description'))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Notes</p>
            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-300">
                {!! $data('description') !!}
            </div>
        </div>
    @endif
</div>
