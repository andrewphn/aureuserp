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
        <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</span>
        <x-filament::badge
            :color="$status['color']"
            size="xs"
            :icon="$status['icon']"
            class="!text-[10px]"
        >
            {{ $status['label'] }}
        </x-filament::badge>
    </div>

    {{-- Draft Number (primary identifier for new projects) --}}
    <div class="flex items-center justify-between">
        <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Draft #</span>
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
        <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Project #</span>
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
