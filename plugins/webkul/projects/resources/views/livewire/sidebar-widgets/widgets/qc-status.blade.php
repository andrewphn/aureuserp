{{-- QC Status Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quality Control</h4>

    <div class="space-y-2">
        {{-- QC Status --}}
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">QC Status</span>
            @if($data['qc_status'] ?? null)
                @php
                    $qcColor = match($data['qc_status']) {
                        'passed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'in_review' => 'info',
                        default => 'gray',
                    };
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $qcColor }}-100 text-{{ $qcColor }}-800 dark:bg-{{ $qcColor }}-500/20 dark:text-{{ $qcColor }}-400">
                    {{ ucfirst(str_replace('_', ' ', $data['qc_status'])) }}
                </span>
            @else
                <span class="text-gray-400 italic text-xs">Not started</span>
            @endif
        </div>

        {{-- QC Notes if available --}}
        @if($data['qc_notes'] ?? null)
            <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded p-2">
                {{ Str::limit($data['qc_notes'], 100) }}
            </div>
        @endif

        {{-- Punch List Items --}}
        @if(($data['punch_list_count'] ?? 0) > 0)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Punch List Items</span>
                <span class="font-medium text-warning-600 dark:text-warning-400">
                    {{ $data['punch_list_count'] }}
                </span>
            </div>
        @endif
    </div>
</div>
