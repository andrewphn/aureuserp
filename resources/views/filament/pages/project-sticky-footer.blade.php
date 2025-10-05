@php
    // Try to get form data, fallback to record data for edit pages
    try {
        $data = $page->form->getRawState();
    } catch (\Exception $e) {
        // On edit pages, use the record data directly
        $data = $page->record ? [
            'estimated_linear_feet' => $page->record->estimated_linear_feet,
            'company_id' => $page->record->company_id,
            'partner_id' => $page->record->partner_id,
            'project_type' => $page->record->project_type,
            'project_address' => $page->record->projectAddress ? [
                'street1' => $page->record->projectAddress->street1,
                'city' => $page->record->projectAddress->city,
                'state' => $page->record->projectAddress->state,
            ] : null,
        ] : [];
    }

    $linearFeet = $data['estimated_linear_feet'] ?? null;
    $companyId = $data['company_id'] ?? null;
    $estimate = null;
    if ($linearFeet && $companyId) {
        $estimate = \App\Services\ProductionEstimatorService::calculate($linearFeet, $companyId);
    }

    // Get project number
    $projectNumber = '—';
    if ($page->record && $page->record->project_number) {
        $projectNumber = $page->record->project_number;
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

    // Get project address - prioritize database-stored project address
    $projectAddress = '—';

    // First check if project has a saved address in the database
    if ($page->record && $page->record->addresses()->count() > 0) {
        $address = $page->record->addresses()->where('is_primary', true)->first()
                   ?? $page->record->addresses()->first();

        $addressParts = array_filter([
            $address->street1,
            $address->city,
            $address->state?->name,
        ]);

        if (!empty($addressParts)) {
            $projectAddress = implode(', ', $addressParts);
        }
    }
    // Fallback to form data if no database address exists
    elseif (!empty($data['project_address']['street1'])) {
        $addressParts = [];
        if (!empty($data['project_address']['street1'])) {
            $addressParts[] = $data['project_address']['street1'];
        }
        if (!empty($data['project_address']['city'])) {
            $addressParts[] = $data['project_address']['city'];
        }
        if (!empty($data['project_address']['state'])) {
            $addressParts[] = $data['project_address']['state'];
        }
        $projectAddress = implode(', ', $addressParts);
    }

    // Get project tags grouped by type
    $projectTags = collect();
    $tagsByType = collect();
    if ($page->record) {
        $projectTags = $page->record->tags;
        $tagsByType = $projectTags->groupBy('type');
    }

    // Type labels for display
    $typeLabels = [
        'priority' => 'Priority',
        'health' => 'Health Status',
        'risk' => 'Risk Factors',
        'complexity' => 'Complexity',
        'work_scope' => 'Work Scope',
        'phase_discovery' => 'Discovery Phase',
        'phase_design' => 'Design Phase',
        'phase_sourcing' => 'Sourcing Phase',
        'phase_production' => 'Production Phase',
        'phase_delivery' => 'Delivery Phase',
        'special_status' => 'Special Status',
        'lifecycle' => 'Lifecycle',
    ];
@endphp

<div class="fi-section rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 mt-6" style="position: sticky; bottom: 0; z-index: 40; backdrop-filter: blur(8px); background: linear-gradient(to right, rgb(249, 250, 251), rgb(243, 244, 246)); border-top: 3px solid rgb(59, 130, 246);">
    <div class="fi-section-content p-3">
        <div class="flex items-center justify-between gap-4">
            {{-- Column 1: Project Number, Customer, Project Address, Tags --}}
            <div class="flex flex-col gap-1.5">
                <div class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $projectNumber }}</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $customerName }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $projectAddress }}</div>
                @if($projectTags->count() > 0)
                    <button
                        type="button"
                        x-data="{ open: false }"
                        @click="open = true"
                        class="mt-1 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <span>{{ $projectTags->count() }} {{ $projectTags->count() === 1 ? 'Tag' : 'Tags' }}</span>

                        {{-- Tags Panel Modal --}}
                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                            style="display: none;"
                        >
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
                                {{-- Header --}}
                                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Project Tags</h3>
                                    <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Content --}}
                                <div class="p-6 overflow-y-auto max-h-[calc(80vh-80px)]">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        @foreach($tagsByType as $type => $tags)
                                            <div class="space-y-2">
                                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                                    {{ $typeLabels[$type] ?? ucfirst($type) }}
                                                </h4>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($tags as $tag)
                                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium"
                                                              style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; border: 1.5px solid {{ $tag->color }}60;">
                                                            {{ $tag->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </button>
                @endif
            </div>

            {{-- Column 2: Type, Linear Feet, and Production Estimates --}}
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-gray-500 dark:text-gray-400">Type:</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $projectType }}</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-gray-500 dark:text-gray-400">Linear Feet:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $linearFeet ? $linearFeet . ' LF' : '—' }}</span>
                </div>

                @if($estimate && $linearFeet)
                {{-- Production Estimate Metrics in Column 2 --}}
                <div class="flex items-center gap-2 pt-1">
                    <div class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border border-amber-200 dark:border-amber-700">
                        <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-xs font-bold text-amber-900 dark:text-amber-100">{{ number_format($estimate['hours'], 1) }}</div>
                        <div class="text-[10px] text-amber-600 dark:text-amber-400">hrs</div>
                    </div>

                    <div class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-700">
                        <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div class="text-xs font-bold text-blue-900 dark:text-blue-100">{{ number_format($estimate['days'], 1) }}</div>
                        <div class="text-[10px] text-blue-600 dark:text-blue-400">days</div>
                    </div>

                    <div class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border border-purple-200 dark:border-purple-700">
                        <svg class="w-3 h-3 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <div class="text-xs font-bold text-purple-900 dark:text-purple-100">{{ number_format($estimate['weeks'], 1) }}</div>
                        <div class="text-[10px] text-purple-600 dark:text-purple-400">wks</div>
                    </div>

                    <div class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-900/20 dark:to-teal-800/20 border border-teal-200 dark:border-teal-700">
                        <svg class="w-3 h-3 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div class="text-xs font-bold text-teal-900 dark:text-teal-100">{{ number_format($estimate['months'], 1) }}</div>
                        <div class="text-[10px] text-teal-600 dark:text-teal-400">mos</div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Column 3: Action Buttons --}}
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
    </div>
</div>
