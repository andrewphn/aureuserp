{{-- Chatter Modal --}}
<x-filament::modal
    id="kanban--chatter-modal"
    :close-by-clicking-away="true"
    :close-button="true"
    slide-over
    width="2xl"
>
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-primary-500" />
            <span>Project Chatter</span>
        </div>
    </x-slot>

    @if($chatterRecord)
        <div class="flex w-full">
            @livewire('chatter-panel', [
                'record' => $chatterRecord,
                'activityPlans' => collect(),
                'resource' => \Webkul\Project\Filament\Resources\ProjectResource::class,
                'followerViewMail' => null,
                'messageViewMail' => null,
            ], key('chatter-' . $chatterRecord->id))
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <x-heroicon-o-chat-bubble-left-ellipsis class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>Select a project to view chatter</p>
        </div>
    @endif
</x-filament::modal>
