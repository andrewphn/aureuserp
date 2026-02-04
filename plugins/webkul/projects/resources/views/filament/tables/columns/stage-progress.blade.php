@php
    $record = $getRecord();
    $stage = $record->stage;
    $stageColor = $stage?->color ?? '#6B7280';
    $stageName = $stage?->name ?? 'No Stage';

    // Calculate milestone progress
    $milestones = $record->milestones ?? collect();
    $totalMilestones = $milestones->count();
    $completedMilestones = $milestones->filter(fn($m) => $m->is_completed)->count();
    $progress = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;
    $milestoneText = $totalMilestones > 0 ? "{$completedMilestones}/{$totalMilestones}" : '';
@endphp

<div style="min-width: 160px; max-width: 200px;">
    {{-- Progress bar container - thick --}}
    <div style="position: relative; width: 100%; height: 32px; background-color: #e5e7eb; border-radius: 6px; overflow: hidden;">
        {{-- Progress fill based on milestone completion --}}
        <div style="position: absolute; top: 0; left: 0; bottom: 0; width: {{ $progress }}%; background-color: {{ $stageColor }}; transition: width 0.3s ease;"></div>

        {{-- Text overlay: Stage name on left, milestone count on right --}}
        <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: space-between; padding: 0 10px;">
            {{-- Stage name with icon --}}
            <div style="display: flex; align-items: center; gap: 5px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px; color: white; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.6));">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                </svg>
                <span style="font-size: 11px; font-weight: 700; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.6); white-space: nowrap;">
                    {{ $stageName }}
                </span>
            </div>

            {{-- Milestone count --}}
            @if($milestoneText)
                <span style="font-size: 11px; font-weight: 700; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.6); background: rgba(0,0,0,0.2); padding: 2px 6px; border-radius: 4px;">
                    {{ $milestoneText }}
                </span>
            @endif
        </div>
    </div>
</div>
