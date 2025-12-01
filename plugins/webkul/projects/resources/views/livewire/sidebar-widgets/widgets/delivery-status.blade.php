{{-- Delivery Status Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Delivery Status</h4>

    <div class="space-y-2">
        {{-- BOL Status --}}
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">Bill of Lading</span>
            @if($data['bol_status'] ?? null)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-400">
                    {{ ucfirst($data['bol_status']) }}
                </span>
            @else
                <span class="text-gray-400 italic text-xs">Pending</span>
            @endif
        </div>

        {{-- Delivery Date --}}
        @if($data['delivery_date'] ?? null)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Scheduled</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($data['delivery_date'])->format('M j, Y') }}
                </span>
            </div>
        @endif

        {{-- Final Payment Status --}}
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">Final Payment</span>
            @if($data['final_payment_status'] ?? null)
                @php
                    $paymentColor = match($data['final_payment_status']) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'gray',
                    };
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $paymentColor }}-100 text-{{ $paymentColor }}-800 dark:bg-{{ $paymentColor }}-500/20 dark:text-{{ $paymentColor }}-400">
                    {{ ucfirst($data['final_payment_status']) }}
                </span>
            @else
                <span class="text-gray-400 italic text-xs">Pending</span>
            @endif
        </div>
    </div>
</div>
