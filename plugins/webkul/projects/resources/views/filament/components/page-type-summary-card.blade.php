{{--
    Page Type Summary Card
    Shows a compact summary of data entered for each page type
    Displayed after user selects a page type in the PDF review wizard
--}}

@php
    $primaryPurpose = $primaryPurpose ?? null;
    $hasData = false;

    // Determine if there's any meaningful data entered
    switch ($primaryPurpose) {
        case 'cover':
            $hasData = !empty($coverAddress) || !empty($coverDesigner) || !empty($scopeEstimate) || !empty($pageLabel);
            break;
        case 'floor_plan':
            $hasData = !empty($roomsOnPage) || !empty($pageLabel);
            break;
        case 'elevations':
            $hasData = !empty($linearFeet) || !empty($pageLabel) || !empty($roomName);
            break;
        case 'countertops':
            $hasData = !empty($countertopFeatures) || !empty($pageLabel);
            break;
        default:
            $hasData = !empty($pageNotes) || !empty($pageLabel);
    }

    // Calculate totals for scope estimate
    $scopeTotals = [];
    if (!empty($scopeEstimate) && is_array($scopeEstimate)) {
        foreach ($scopeEstimate as $item) {
            $unit = $item['unit'] ?? 'LF';
            $qty = floatval($item['quantity'] ?? 0);
            $scopeTotals[$unit] = ($scopeTotals[$unit] ?? 0) + $qty;
        }
    }
@endphp

<div class="rounded-lg border {{ $hasData ? 'border-primary-200 bg-primary-50 dark:border-primary-800 dark:bg-primary-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50' }} p-3 text-sm relative group">
    {{-- Edit icon overlay (appears on hover) --}}
    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] text-gray-500 dark:text-gray-400 bg-white/80 dark:bg-gray-800/80">
            <x-heroicon-o-pencil class="w-3 h-3" />
            Click Edit Details below
        </span>
    </div>

    {{-- Header with page type badge --}}
    <div class="flex items-center justify-between mb-2">
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium
            {{ match($primaryPurpose) {
                'cover' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                'floor_plan' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
                'elevations' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
                'countertops' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
                'reference' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
            } }}">
            @switch($primaryPurpose)
                @case('cover')
                    <x-heroicon-o-document-text class="w-3 h-3" />
                    Cover Page
                    @break
                @case('floor_plan')
                    <x-heroicon-o-map class="w-3 h-3" />
                    Floor Plan
                    @break
                @case('elevations')
                    <x-heroicon-o-view-columns class="w-3 h-3" />
                    Elevations
                    @break
                @case('countertops')
                    <x-heroicon-o-square-3-stack-3d class="w-3 h-3" />
                    Countertops
                    @break
                @case('reference')
                    <x-heroicon-o-photo class="w-3 h-3" />
                    Reference
                    @break
                @default
                    <x-heroicon-o-ellipsis-horizontal class="w-3 h-3" />
                    Other
            @endswitch
        </span>

        @if($hasData)
            <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                Data entered
            </span>
        @else
            <span class="text-xs text-gray-400 dark:text-gray-500">
                Click "Edit Details" below
            </span>
        @endif
    </div>

    {{-- Content summary based on page type --}}
    @if($hasData)
        <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-300">
            @switch($primaryPurpose)
                @case('cover')
                    @if(!empty($pageLabel))
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Project:</span>
                            <span class="font-medium">{{ $pageLabel }}</span>
                        </div>
                    @endif

                    @if(!empty($coverAddress))
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Address:</span>
                            <span>{{ $coverAddress }}</span>
                        </div>
                    @endif

                    @if(!empty($coverDesigner))
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Designer:</span>
                            <span>{{ $coverDesigner }}</span>
                        </div>
                    @endif

                    @if(!empty($scopeTotals))
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Estimate:</span>
                            <span class="flex flex-wrap gap-2">
                                @foreach($scopeTotals as $unit => $total)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 font-medium">
                                        {{ number_format($total, 1) }} {{ $unit }}
                                    </span>
                                @endforeach
                            </span>
                        </div>
                    @endif

                    @if(!empty($roomsMentioned) && count($roomsMentioned) > 0)
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Rooms:</span>
                            <span>{{ implode(', ', array_slice($roomsMentioned, 0, 3)) }}{{ count($roomsMentioned) > 3 ? ' +' . (count($roomsMentioned) - 3) . ' more' : '' }}</span>
                        </div>
                    @endif
                    @break

                @case('floor_plan')
                    @if(!empty($pageLabel))
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Floor:</span>
                            <span class="font-medium">{{ $pageLabel }}</span>
                        </div>
                    @endif

                    @if(!empty($roomsOnPage) && count($roomsOnPage) > 0)
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Rooms:</span>
                            <span class="flex flex-wrap gap-1">
                                @foreach($roomsOnPage as $room)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-[10px]">
                                        {{ $room }}
                                    </span>
                                @endforeach
                            </span>
                        </div>
                    @endif
                    @break

                @case('elevations')
                    @if(!empty($pageLabel))
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Location:</span>
                            <span class="font-medium">{{ $pageLabel }}</span>
                        </div>
                    @endif

                    <div class="flex items-center gap-3">
                        @if(!empty($linearFeet))
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 font-medium">
                                {{ number_format($linearFeet, 1) }} LF
                            </span>
                        @endif

                        @if(!empty($pricingTier))
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                Level {{ $pricingTier }}
                            </span>
                        @endif

                        @if(!empty($roomName))
                            <span class="text-gray-500">in {{ $roomName }}</span>
                        @endif
                    </div>

                    @if($hasHardware || $hasMaterial)
                        <div class="flex items-center gap-2 mt-1">
                            @if($hasHardware)
                                <span class="inline-flex items-center gap-1 text-[10px] text-gray-500">
                                    <x-heroicon-o-wrench-screwdriver class="w-3 h-3" />
                                    Hardware
                                </span>
                            @endif
                            @if($hasMaterial)
                                <span class="inline-flex items-center gap-1 text-[10px] text-gray-500">
                                    <x-heroicon-o-document-text class="w-3 h-3" />
                                    Materials
                                </span>
                            @endif
                        </div>
                    @endif
                    @break

                @case('countertops')
                    @if(!empty($pageLabel))
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Area:</span>
                            <span class="font-medium">{{ $pageLabel }}</span>
                        </div>
                    @endif

                    @if(!empty($countertopFeatures) && count($countertopFeatures) > 0)
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Shows:</span>
                            <span class="flex flex-wrap gap-1">
                                @foreach($countertopFeatures as $feature)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-[10px]">
                                        {{ str_replace('_', ' ', ucfirst($feature)) }}
                                    </span>
                                @endforeach
                            </span>
                        </div>
                    @endif
                    @break

                @default
                    @if(!empty($pageLabel))
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Label:</span>
                            <span>{{ $pageLabel }}</span>
                        </div>
                    @endif
            @endswitch

            @if(!empty($pageNotes))
                <div class="flex items-start gap-2 mt-1 pt-1 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-gray-400 dark:text-gray-500 w-16 flex-shrink-0">Notes:</span>
                    <span class="text-gray-500 dark:text-gray-400 italic truncate">{{ Str::limit($pageNotes, 50) }}</span>
                </div>
            @endif
        </div>
    @endif
</div>
