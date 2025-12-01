{{-- Inspiration Gallery Livewire Component Embed --}}
<div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    @livewire('inspiration-gallery', ['projectId' => $projectId], key('inspiration-gallery-' . ($projectId ?? 'new')))
</div>
