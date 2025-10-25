@php
    $project = $getViewData()['project'] ?? null;
@endphp

@if($project)
    @livewire('production-timeline', ['project' => $project], key('production-timeline-' . $project->id))
@else
    <div class="text-center py-4 text-gray-500 bg-gray-50 rounded-lg">
        <p>Save the project first to view the production timeline</p>
    </div>
@endif
