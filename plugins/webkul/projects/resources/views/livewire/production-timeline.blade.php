<div class="production-timeline-minimal">
    <style>
        /* Minimal, Compact Timeline Design */
        .production-timeline-minimal {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
        }

        /* Header Row - Single Line, Compact */
        .timeline-header-minimal {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            color: #111827;
        }

        .project-name-minimal {
            font-weight: 600;
            font-size: 0.9375rem;
        }

        .metrics-minimal {
            display: flex;
            gap: 1rem;
            align-items: center;
            font-size: 0.8125rem;
        }

        .metric-item {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            color: #6b7280;
        }

        .metric-item.danger {
            color: #ef4444;
            font-weight: 600;
        }

        .metric-item.warning {
            color: #f59e0b;
        }

        .metric-item svg {
            width: 14px;
            height: 14px;
        }

        /* Stage Timeline Row - Horizontal, Minimal */
        .stages-row-minimal {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 40px;
            margin-bottom: 0.5rem;
        }

        /* Connecting Line */
        .stage-line-minimal {
            position: absolute;
            left: 5%;
            right: 5%;
            top: 50%;
            height: 2px;
            background: #d1d5db;
            transform: translateY(-1px);
            z-index: 0;
        }

        /* Individual Stage */
        .stage-minimal {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .stage-minimal:hover {
            opacity: 0.7;
        }

        /* Stage Dot Indicators */
        .stage-dot-minimal {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #9ca3af;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.375rem;
            transition: all 0.2s;
        }

        .stage-dot-minimal.completed {
            background: #10b981;
            border-color: #10b981;
        }

        .stage-dot-minimal.current {
            width: 18px;
            height: 18px;
            background: #3b82f6;
            border-color: #3b82f6;
            animation: pulse-dot-minimal 2s infinite;
        }

        .stage-dot-minimal.upcoming {
            background: white;
            border-color: #d1d5db;
        }

        @keyframes pulse-dot-minimal {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
            }
        }

        .stage-dot-minimal svg {
            width: 10px;
            height: 10px;
            color: white;
        }

        /* Stage Labels */
        .stage-label-minimal {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 0.125rem;
        }

        .stage-date-minimal {
            font-size: 0.6875rem;
            color: #9ca3af;
        }

        .stage-count-minimal {
            font-size: 0.6875rem;
            color: #6b7280;
            margin-top: 0.125rem;
        }

        /* Progress Bar - Single Line, Minimal */
        .progress-minimal {
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .progress-bar-minimal {
            flex: 1;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill-minimal {
            height: 100%;
            background: #3b82f6;
            border-radius: 2px;
            transition: width 0.5s ease;
        }

        .progress-text-minimal {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 600;
            min-width: 32px;
        }

        /* Expanded Stage Content */
        .stage-expansion-minimal {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin: 0.5rem 0;
            font-size: 0.8125rem;
        }

        .expansion-header-minimal {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .expansion-title-minimal {
            font-weight: 600;
            color: #111827;
        }

        .expansion-close-minimal {
            font-size: 0.75rem;
            color: #6b7280;
            cursor: pointer;
        }

        .expansion-close-minimal:hover {
            color: #111827;
        }

        .milestone-list-minimal {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .milestone-item-minimal {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.5rem;
            background: white;
            border-radius: 0.25rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .milestone-item-minimal:hover {
            border-color: #3b82f6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .milestone-dot-minimal {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .milestone-dot-minimal.completed {
            background: #10b981;
        }

        .milestone-dot-minimal.overdue {
            background: #ef4444;
        }

        .milestone-dot-minimal.pending {
            background: #3b82f6;
        }

        .milestone-name-minimal {
            flex: 1;
            color: #111827;
            font-size: 0.8125rem;
        }

        .milestone-badge-minimal {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.6875rem;
            font-weight: 500;
        }

        .milestone-badge-minimal.critical {
            background: #fef2f2;
            color: #991b1b;
        }

        .milestone-badge-minimal.overdue {
            background: #fef2f2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .timeline-header-minimal {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .metrics-minimal {
                flex-wrap: wrap;
            }

            .stages-row-minimal {
                overflow-x: auto;
                justify-content: flex-start;
            }

            .stage-minimal {
                min-width: 80px;
            }
        }
    </style>

    <!-- Compact Header Row -->
    <div class="timeline-header-minimal">
        <div class="project-name-minimal">{{ $project->name }}</div>
        <div class="metrics-minimal">
            @if($daysRemaining !== null)
                <span class="metric-item {{ $daysRemaining < 0 ? 'danger' : ($daysRemaining < 7 ? 'warning' : '') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ abs($daysRemaining) }}d {{ $daysRemaining < 0 ? 'overdue' : 'left' }}
                </span>
            @endif

            @if($overdueCount > 0)
                <span class="metric-item danger">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $overdueCount }} overdue
                </span>
            @endif

            <span class="metric-item">{{ $progress }}%</span>
        </div>
    </div>

    <!-- Minimal Stage Timeline Row -->
    <div class="stages-row-minimal">
        <div class="stage-line-minimal"></div>

        @foreach($stages as $index => $stage)
        <div class="stage-minimal" wire:click="changeStage('{{ $stage['key'] }}')">
            <div class="stage-dot-minimal {{ $stage['status'] }}">
                @if($stage['status'] === 'completed')
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                @endif
            </div>

            <div class="stage-label-minimal">{{ $stage['label'] }}</div>

            @if($project->start_date && $project->desired_completion_date)
                @php
                    $totalDays = $project->start_date->diffInDays($project->desired_completion_date);
                    $daysPerStage = $totalDays / count($stages);
                    $stageDate = $project->start_date->copy()->addDays($daysPerStage * $index);
                @endphp
                <div class="stage-date-minimal">{{ $stageDate->format('M d') }}</div>
            @endif

            @if(count($stage['milestones']) > 0)
                <div class="stage-count-minimal">{{ count($stage['milestones']) }}</div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Minimal Progress Bar -->
    <div class="progress-minimal">
        <div class="progress-bar-minimal">
            <div class="progress-fill-minimal" style="width: {{ $progress }}%"></div>
        </div>
        <div class="progress-text-minimal">{{ $progress }}%</div>
    </div>

    <!-- Expandable Stage Content (Shown when stage has milestones) -->
    @foreach($stages as $stage)
        @if($stage['status'] === 'current' && count($stage['milestones']) > 0)
        <div class="stage-expansion-minimal">
            <div class="expansion-header-minimal">
                <div class="expansion-title-minimal">{{ $stage['label'] }} Milestones</div>
                <div class="expansion-close-minimal">[Click to collapse]</div>
            </div>

            <div class="milestone-list-minimal">
                @foreach($stage['milestones'] as $milestone)
                <div class="milestone-item-minimal">
                    <div class="milestone-dot-minimal {{ $milestone['is_completed'] ? 'completed' : ($milestone['is_overdue'] ? 'overdue' : 'pending') }}"></div>
                    <div class="milestone-name-minimal">{{ $milestone['name'] }}</div>
                    @if($milestone['is_overdue'])
                        <span class="milestone-badge-minimal overdue">Overdue</span>
                    @endif
                    <span style="font-size: 0.75rem; color: #9ca3af;">{{ $milestone['deadline'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
</div>
