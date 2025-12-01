{{-- Customer History Widget --}}
<div class="space-y-3">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer History</h4>

    @if($this->customerHistory)
        @php
            $history = $this->customerHistory;
        @endphp

        {{-- Summary Stats --}}
        <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-1.5">
                <x-heroicon-o-folder class="h-3.5 w-3.5 text-primary-500" />
                <span class="text-gray-600 dark:text-gray-400">
                    <span class="font-medium text-gray-900 dark:text-white">{{ $history['totalProjects'] }}</span> projects
                </span>
            </div>
            @if($history['totalLinearFeet'] > 0)
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-calculator class="h-3.5 w-3.5 text-success-500" />
                    <span class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($history['totalLinearFeet']) }}</span> LF
                    </span>
                </div>
            @endif
        </div>

        {{-- Recent Projects List --}}
        @if($history['projects']->count() > 0)
            <div class="space-y-2 mt-2">
                <p class="text-xs text-gray-400 dark:text-gray-500">Recent Projects</p>
                <div class="space-y-1.5">
                    @foreach($history['projects'] as $project)
                        <a
                            href="{{ route('filament.admin.resources.project.projects.view', $project->id) }}"
                            class="flex items-center justify-between p-2 rounded-md bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors group"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
                                    {{ $project->name ?: 'Untitled Project' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $project->project_number ?: $project->draft_number ?: 'No number' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if($project->estimated_linear_feet)
                                    <span class="text-xs text-gray-400">{{ number_format($project->estimated_linear_feet) }} LF</span>
                                @endif
                                <x-heroicon-o-arrow-top-right-on-square class="h-3.5 w-3.5 text-gray-400 group-hover:text-primary-500 transition-colors" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- View All Link --}}
        @if($history['totalProjects'] > 5)
            <div class="pt-1">
                <a
                    href="{{ route('filament.admin.resources.project.projects.index', ['tableFilters[partner_id][value]' => $history['partner']->id]) }}"
                    class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                >
                    View all {{ $history['totalProjects'] }} projects
                </a>
            </div>
        @endif
    @else
        <p class="text-sm text-gray-400 italic">Select a customer to see history</p>
    @endif
</div>
