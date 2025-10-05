@props([
    'label' => '',
    'value' => '',
    'unit' => '',
    'icon' => null,
    'gradient' => 'linear-gradient(135deg, #D4A574 0%, #C9995F 100%)', // TCS Gold default
])

<div
    {{ $attributes->merge(['class' => 'tcs-metric-card']) }}
    style="background: {{ $gradient }};"
>
    @if($icon)
        <div class="flex items-center gap-2 mb-3">
            <x-filament::icon
                :icon="$icon"
                class="h-4 w-4 text-white/60"
            />
            <div class="tcs-metric-card-label">{{ $label }}</div>
        </div>
    @else
        <div class="tcs-metric-card-label">{{ $label }}</div>
    @endif

    <div class="tcs-metric-card-value">{{ $value }}</div>
    <div class="tcs-metric-card-unit">{{ $unit }}</div>
</div>
