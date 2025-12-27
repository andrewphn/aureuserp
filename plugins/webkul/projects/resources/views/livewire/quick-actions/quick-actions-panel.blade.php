@php
    // When rendered as modal content, we receive $record directly
    // When rendered as Livewire component, we use $project
    $project = $project ?? $record ?? null;
@endphp

@if($project)
    <div>
        @livewire('quick-actions-panel', ['project' => $project], key('quick-actions-' . $project->id))
    </div>
@else
    <div class="p-4 text-center text-gray-500">
        No project selected
    </div>
@endif
