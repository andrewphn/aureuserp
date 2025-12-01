@php
    // Handle address - it might be a string or an array
    $addressDisplay = '';
    if (is_array($address)) {
        $addressDisplay = trim(implode(', ', array_filter([
            $address['street'] ?? $address['address_line_1'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['zip'] ?? $address['postal_code'] ?? '',
        ])));
    } elseif (is_string($address)) {
        $addressDisplay = $address;
    }
@endphp

<div class="space-y-3">
    {{-- Customer --}}
    <div class="flex items-start gap-2">
        <x-filament::icon
            icon="heroicon-m-user"
            class="w-4 h-4 text-gray-400 mt-0.5 shrink-0"
        />
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                {{ $partner?->name ?? 'Unknown' }}
            </p>
            @if($partner?->email)
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ $partner->email }}
                </p>
            @endif
        </div>
    </div>

    {{-- Project Type --}}
    @if($projectType)
        <div class="flex items-start gap-2">
            <x-filament::icon
                icon="heroicon-m-squares-2x2"
                class="w-4 h-4 text-gray-400 mt-0.5 shrink-0"
            />
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ ucwords(str_replace('_', ' ', $projectType)) }}
                </p>
            </div>
        </div>
    @endif

    {{-- Address --}}
    @if($addressDisplay)
        <div class="flex items-start gap-2">
            <x-filament::icon
                icon="heroicon-m-map-pin"
                class="w-4 h-4 text-gray-400 mt-0.5 shrink-0"
            />
            <div>
                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ $addressDisplay }}
                </p>
            </div>
        </div>
    @endif
</div>
