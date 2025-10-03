@php
    $data = $page->form->getRawState();
    $linearFeet = $data['estimated_linear_feet'] ?? null;
    $companyId = $data['company_id'] ?? null;
    $estimate = null;
    if ($linearFeet && $companyId) {
        $estimate = \App\Services\ProductionEstimatorService::calculate($linearFeet, $companyId);
    }

    // Get company name
    $companyName = '—';
    if ($companyId) {
        $company = \Webkul\Support\Models\Company::find($companyId);
        $companyName = $company?->name ?? '—';
    }

    // Get customer name
    $customerName = '—';
    if (!empty($data['partner_id'])) {
        $customer = \Webkul\Partner\Models\Partner::find($data['partner_id']);
        $customerName = $customer?->name ?? '—';
    }

    // Get project type
    $projectType = '—';
    if (!empty($data['project_type'])) {
        $projectType = ucfirst(str_replace('_', ' ', $data['project_type']));
    }
@endphp

<div class="fi-section rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 mt-6" style="position: sticky; bottom: 0; z-index: 40; backdrop-filter: blur(8px); background: linear-gradient(to right, rgb(249, 250, 251), rgb(243, 244, 246)); border-top: 3px solid rgb(59, 130, 246);">
    <div class="fi-section-content p-3">
        <div class="flex items-center justify-between gap-4">
            {{-- Left Column: Double-row info --}}
            <div class="flex flex-col gap-2">
                {{-- Row 1: Company and Customer --}}
                <div class="flex items-center gap-8 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-500 dark:text-gray-400">Company:</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $companyName }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-500 dark:text-gray-400">Customer:</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $customerName }}</span>
                    </div>
                </div>

                {{-- Row 2: Type and Linear Feet --}}
                <div class="flex items-center gap-8 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-500 dark:text-gray-400">Type:</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $projectType }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-500 dark:text-gray-400">Linear Feet:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $linearFeet ? $linearFeet . ' LF' : '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Center: Action Buttons --}}
            <div class="flex items-center gap-3">
                <x-filament::button
                    type="submit"
                    form="form"
                    wire:click="save"
                >
                    Save changes
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    tag="a"
                    :href="$page->getResource()::getUrl('index')"
                >
                    Cancel
                </x-filament::button>
            </div>
        </div>

        @if($estimate && $linearFeet)
        {{-- Production Estimate Metrics --}}
        <div class="flex items-center gap-3 pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border border-amber-200 dark:border-amber-700">
                <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm font-bold text-amber-900 dark:text-amber-100">{{ number_format($estimate['hours'], 1) }}</div>
                <div class="text-xs text-amber-600 dark:text-amber-400">hrs</div>
            </div>

            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-700">
                <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <div class="text-sm font-bold text-blue-900 dark:text-blue-100">{{ number_format($estimate['days'], 1) }}</div>
                <div class="text-xs text-blue-600 dark:text-blue-400">days</div>
            </div>

            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border border-purple-200 dark:border-purple-700">
                <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                <div class="text-sm font-bold text-purple-900 dark:text-purple-100">{{ number_format($estimate['weeks'], 1) }}</div>
                <div class="text-xs text-purple-600 dark:text-purple-400">wks</div>
            </div>

            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-900/20 dark:to-teal-800/20 border border-teal-200 dark:border-teal-700">
                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <div class="text-sm font-bold text-teal-900 dark:text-teal-100">{{ number_format($estimate['months'], 1) }}</div>
                <div class="text-xs text-teal-600 dark:text-teal-400">mos</div>
            </div>

            <div class="pl-3 border-l border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Est. Time</div>
                <div class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $estimate['formatted'] }}</div>
            </div>
        </div>
        @endif
    </div>
</div>
