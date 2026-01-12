@php
    $record = $getRecord();
@endphp

<div class="w-full">
    @livewire('project-data-cards', ['project' => $record], key('project-data-cards-' . $record->id))
</div>
