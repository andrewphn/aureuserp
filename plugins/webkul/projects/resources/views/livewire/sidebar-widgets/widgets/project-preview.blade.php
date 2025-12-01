{{-- Project Preview Widget --}}
{{-- Shows auto-generated draft/project number, name, and status at top of sidebar --}}
@php
    $draftNumberPreview = $this->draftNumberPreview;
    $projectNumberPreview = $this->projectNumberPreview;
    $status = $this->projectStatus;
    $isDraft = $status['label'] === 'Draft';
@endphp

<div class="space-y-1.5 pb-2 border-b border-gray-200 dark:border-gray-700 mb-2">
    {{-- Status Badge --}}
    <div class="flex items-center justify-between">
        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">STATUS</span>
        <span @class([
            'inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium',
            'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $status['color'] === 'warning',
            'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $status['color'] === 'success',
            'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400' => $status['color'] === 'gray',
        ])>
            <x-dynamic-component :component="$status['icon']" class="w-2.5 h-2.5" />
            {{ $status['label'] }}
        </span>
    </div>

    {{-- Draft Number (primary identifier for new projects) --}}
    <div class="flex items-center justify-between">
        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">DRAFT #</span>
        <div class="text-right max-w-[140px]">
            <span @class([
                'text-[11px] font-mono font-semibold break-all leading-tight',
                'text-primary-600 dark:text-primary-400' => !$draftNumberPreview['isPlaceholder'],
                'text-gray-400 dark:text-gray-500 italic' => $draftNumberPreview['isPlaceholder'],
            ])>
                {{ $draftNumberPreview['value'] }}
            </span>
            @if($draftNumberPreview['hint'])
                <p class="text-[9px] text-gray-400 dark:text-gray-500 mt-0.5">{{ $draftNumberPreview['hint'] }}</p>
            @endif
        </div>
    </div>

    {{-- Project Number (shown for converted projects, or pending for drafts) --}}
    <div class="flex items-center justify-between">
        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PROJECT #</span>
        <div class="text-right">
            @if($projectNumberPreview['isPlaceholder'])
                <span class="text-[10px] text-gray-400 dark:text-gray-500 italic">Pending</span>
            @else
                <span class="text-[11px] font-mono font-semibold text-primary-600 dark:text-primary-400 break-all leading-tight">
                    {{ $projectNumberPreview['value'] }}
                </span>
            @endif
        </div>
    </div>

</div>
