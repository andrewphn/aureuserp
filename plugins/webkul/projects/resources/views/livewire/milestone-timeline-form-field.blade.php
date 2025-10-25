@php
    $project = $getViewData()['project'] ?? null;
@endphp

@if($project)
    @livewire('milestone-timeline', ['project' => $project], key('milestone-timeline-' . $project->id))
@else
    <div class="text-center py-4 text-gray-500">
        <p>Save the project first to view the timeline</p>
    </div>
@endif
