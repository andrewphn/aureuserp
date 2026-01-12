{{-- Running Checkout Summary Widget --}}
@php
    $specData = $data['spec_data'] ?? [];
    $pricingMode = $data['pricing_mode'] ?? 'quick';
    $rooms = $data['rooms'] ?? [];

    // Calculate totals based on pricing mode
    $totalLF = 0;
    $roomCount = 0;
    $cabinetCount = 0;
    $locationCount = 0;

    if ($pricingMode === 'detailed' && !empty($specData)) {
        foreach ($specData as $room) {
            $roomCount++;
            $totalLF += (float) ($room['linear_feet'] ?? 0);
            foreach ($room['children'] ?? [] as $location) {
                $locationCount++;
                foreach ($location['children'] ?? [] as $run) {
                    $cabinetCount += count($run['children'] ?? []);
                }
            }
        }
    } elseif ($pricingMode === 'rooms' && !empty($rooms)) {
        $roomCount = count($rooms);
        foreach ($rooms as $room) {
            $totalLF += (float) ($room['linear_feet'] ?? 0);
        }
    } else {
        // Quick estimate mode
        $totalLF = (float) ($data['estimated_linear_feet'] ?? 0);
    }

    // Calculate estimate
    $pricePerLF = $this->pricePerLinearFoot ?? 350;
    $totalEstimate = $totalLF * $pricePerLF;
@endphp

<div class="space-y-2">
    <div class="flex items-center justify-between">
        <h4 class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Checkout Summary
        </h4>
        <x-filament::badge size="xs" color="primary" class="!text-[9px]">
            {{ ucfirst($pricingMode) }}
        </x-filament::badge>
    </div>

    {{-- Main Stats Grid --}}
    <div class="grid grid-cols-2 gap-1.5">
        {{-- Linear Feet --}}
        <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-2 text-center">
            <div class="text-lg font-bold text-primary-600 dark:text-primary-400 tabular-nums leading-tight">
                {{ number_format($totalLF, 1) }}
            </div>
            <div class="text-[9px] text-gray-500 dark:text-gray-400 uppercase">Linear Feet</div>
        </div>

        {{-- Rooms/Items Count --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-2 text-center">
            <div class="text-lg font-bold text-gray-700 dark:text-gray-300 tabular-nums leading-tight">
                @if($pricingMode === 'detailed' || $pricingMode === 'rooms')
                    {{ $roomCount }}
                @else
                    &mdash;
                @endif
            </div>
            <div class="text-[9px] text-gray-500 dark:text-gray-400 uppercase">Rooms</div>
        </div>
    </div>

    {{-- Detailed Mode Stats --}}
    @if($pricingMode === 'detailed' && ($locationCount > 0 || $cabinetCount > 0))
        <div class="grid grid-cols-2 gap-1.5">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-1.5 text-center">
                <div class="text-sm font-semibold text-gray-600 dark:text-gray-400 tabular-nums">{{ $locationCount }}</div>
                <div class="text-[9px] text-gray-500 dark:text-gray-400 uppercase">Locations</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-1.5 text-center">
                <div class="text-sm font-semibold text-gray-600 dark:text-gray-400 tabular-nums">{{ $cabinetCount }}</div>
                <div class="text-[9px] text-gray-500 dark:text-gray-400 uppercase">Cabinets</div>
            </div>
        </div>
    @endif

    {{-- Running Total Estimate --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
        <div class="flex items-center justify-between">
            <span class="text-[11px] text-gray-600 dark:text-gray-400">Estimate</span>
            <span class="text-lg font-bold text-success-600 dark:text-success-400 tabular-nums">
                ${{ number_format($totalEstimate, 0) }}
            </span>
        </div>
        <p class="text-[9px] text-gray-400 dark:text-gray-500 text-right mt-0.5">
            @ ${{ number_format($pricePerLF, 0) }}/LF
        </p>
    </div>

    {{-- Quick Add Buttons for Detailed Mode --}}
    @if($pricingMode === 'detailed')
        <div class="flex gap-1.5 pt-0.5">
            <x-filament::button
                size="xs"
                color="primary"
                outlined
                wire:click="$dispatch('spec-add-room')"
                class="flex-1 !text-[10px] !py-1"
            >
                + Room
            </x-filament::button>
            <x-filament::button
                size="xs"
                color="gray"
                outlined
                wire:click="$dispatch('spec-add-cabinet')"
                class="flex-1 !text-[10px] !py-1"
            >
                + Cabinet
            </x-filament::button>
        </div>
    @endif
</div>
