@php
    $total = 100;
    $progress = ($getState() / $total) * 100;
    $displayProgress = $progress == 100 ? number_format($progress, 0) : number_format($progress, 2);

    // Just keep the semantic color (not full Tailwind class)
    $color = $getColor($state) ?? 'primary';
@endphp

<div class="progress-container">
    <div class="progress-bar progress-{{ $color }}" style="width: {{ $displayProgress }}%"></div>

    <div class="progress-text">
        @if (
            $column instanceof \Webkul\Support\Filament\Tables\Columns\ProgressBarEntry
            && $column->getCanShow()
        )
            <small
                @class([
                    'text-gray-700' => $displayProgress != 100,
                    'text-white' => $displayProgress == 100
                ])
            >
                {{ $displayProgress }}%
            </small>
        @endif
    </div>
</div>

<style>
    .progress-container {
        width: 100%;
        background-color: #e5e7eb;
        border-radius: 0.375rem;
        height: 1.5rem;
        overflow: hidden;
        position: relative;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .progress-bar {
        height: 100%;
        border-radius: 0.375rem;
        transition: width 0.3s, background-color 0.3s;
        width: 0;
    }

    /* Custom colors */
    .progress-primary {
        background-color: #2563eb; /* Tailwind blue-600 */
    }
    .progress-success {
        background-color: #16a34a; /* Tailwind green-600 */
    }
    .progress-danger {
        background-color: #dc2626; /* Tailwind red-600 */
    }
    .progress-warning {
        background-color: #ca8a04; /* Tailwind yellow-600 */
    }
    .progress-info {
        background-color: #0284c7; /* Tailwind sky-600 */
    }
    .progress-gray {
        background-color: #4b5563; /* Tailwind gray-600 */
    }

    .progress-text {
        text-align: center;
        font-size: 0.875rem;
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }
</style>
