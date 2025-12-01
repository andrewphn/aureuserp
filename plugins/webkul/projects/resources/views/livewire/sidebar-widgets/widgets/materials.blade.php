{{-- Materials Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Materials</h4>

    <div class="space-y-2">
        {{-- BOM Status --}}
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">BOM Status</span>
            @if($data['bom_status'] ?? null)
                @php
                    $bomColor = match($data['bom_status']) {
                        'complete' => 'success',
                        'in_progress' => 'warning',
                        'pending' => 'gray',
                        default => 'gray',
                    };
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $bomColor }}-100 text-{{ $bomColor }}-800 dark:bg-{{ $bomColor }}-500/20 dark:text-{{ $bomColor }}-400">
                    {{ ucfirst(str_replace('_', ' ', $data['bom_status'])) }}
                </span>
            @else
                <span class="text-gray-400 italic text-xs">Not started</span>
            @endif
        </div>

        {{-- PO Count --}}
        @if(($data['po_count'] ?? 0) > 0)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Purchase Orders</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ $data['po_count'] }}
                </span>
            </div>
        @endif

        {{-- Material Cost if available --}}
        @if($data['material_cost'] ?? null)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Material Cost</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    ${{ number_format($data['material_cost'], 2) }}
                </span>
            </div>
        @endif
    </div>
</div>
