<div class="flex w-full">
    @livewire('chatter-panel', [
        'record' => $record ?? $this->record ?? null,
        'activityPlans' => $activityPlans ?? $this->activityPlans ?? [],
        'resource' => $resource ?? $this->resource ?? '',
        'followerViewMail' => $followerViewMail ?? $this->followerViewMail ?? null,
        'messageViewMail' => $messageViewMail ?? $this->messageViewMail ?? null,
    ])
</div>
