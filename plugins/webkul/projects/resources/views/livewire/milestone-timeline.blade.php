<div class="milestone-timeline-wrapper">
    <style>
        .milestone-timeline {
            position: relative;
            padding: 1rem 0;
        }

        .timeline-line {
            position: absolute;
            left: 1.5rem;
            top: 2rem;
            bottom: 2rem;
            width: 2px;
            background: linear-gradient(to bottom, #e5e7eb 0%, #9ca3af 50%, #e5e7eb 100%);
        }

        .timeline-item {
            position: relative;
            padding: 0.75rem 0 0.75rem 4rem;
            margin-bottom: 0.5rem;
        }

        .timeline-dot {
            position: absolute;
            left: 0.875rem;
            top: 1rem;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .timeline-dot.start {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .timeline-dot.milestone {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .timeline-dot.milestone.completed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .timeline-dot.milestone.overdue {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            animation: pulse 2s infinite;
        }

        .timeline-dot.end {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .timeline-content {
            background: white;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .timeline-content:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateX(2px);
        }

        .timeline-content.completed {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .timeline-content.overdue {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .milestone-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .milestone-date {
            font-size: 0.875rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .milestone-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .milestone-badge.completed {
            background: #d1fae5;
            color: #065f46;
        }

        .milestone-badge.overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .milestone-badge.pending {
            background: #dbeafe;
            color: #1e40af;
        }

        .no-milestones {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
            font-style: italic;
        }
    </style>

    <div class="milestone-timeline">
        <div class="timeline-line"></div>

        <!-- Start Date -->
        @if($startDate)
        <div class="timeline-item">
            <div class="timeline-dot start"></div>
            <div class="timeline-content">
                <div class="milestone-name">Project Start</div>
                <div class="milestone-date">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{ $startDate }}
                </div>
            </div>
        </div>
        @endif

        <!-- Milestones -->
        @forelse($milestones as $milestone)
        <div class="timeline-item">
            <div class="timeline-dot milestone {{ $milestone['is_completed'] ? 'completed' : ($milestone['is_past_due'] ? 'overdue' : '') }}"></div>
            <div class="timeline-content {{ $milestone['is_completed'] ? 'completed' : ($milestone['is_past_due'] ? 'overdue' : '') }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1">
                        <div class="milestone-name">{{ $milestone['name'] }}</div>
                        <div class="milestone-date">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $milestone['deadline'] }}
                            @if($milestone['deadline_time'])
                                <span class="text-xs">{{ $milestone['deadline_time'] }}</span>
                            @endif
                        </div>
                        @if($milestone['is_completed'] && $milestone['completed_at'])
                        <div class="milestone-date mt-1">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-green-600">Completed {{ $milestone['completed_at'] }}</span>
                        </div>
                        @endif
                    </div>
                    <div>
                        @if($milestone['is_completed'])
                        <span class="milestone-badge completed">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Completed
                        </span>
                        @elseif($milestone['is_past_due'])
                        <span class="milestone-badge overdue">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            Overdue
                        </span>
                        @else
                        <span class="milestone-badge pending">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                            Pending
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
            @if(!$startDate && !$endDate)
            <div class="no-milestones">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                No timeline data available yet
            </div>
            @endif
        @endforelse

        <!-- End Date -->
        @if($endDate)
        <div class="timeline-item">
            <div class="timeline-dot end"></div>
            <div class="timeline-content">
                <div class="milestone-name">Target Completion</div>
                <div class="milestone-date">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                    </svg>
                    {{ $endDate }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
