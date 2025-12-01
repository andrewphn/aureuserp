{{-- PDF Document Manager Livewire Component Embed --}}
<div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    @livewire('pdf-document-manager', ['projectId' => $projectId], key('pdf-manager-' . ($projectId ?? 'new')))
</div>
