<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Draft Resume Banner --}}
        @if($this->draft)
            <div class="rounded-lg bg-warning-50 dark:bg-warning-500/10 p-4 border border-warning-200 dark:border-warning-500/20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-document-duplicate class="h-5 w-5 text-warning-500" />
                        <div>
                            <p class="text-sm font-medium text-warning-800 dark:text-warning-200">
                                Resuming Draft
                            </p>
                            <p class="text-xs text-warning-600 dark:text-warning-300">
                                Last saved {{ $this->draft->updated_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <x-filament::button
                        wire:click="discardDraft"
                        color="gray"
                        size="sm"
                    >
                        Start Fresh
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- Wizard Form --}}
        <form wire:submit="create">
            {{ $this->form }}
        </form>

        {{-- Save & Exit Button (always visible) --}}
        <div class="fixed bottom-6 left-6 z-50">
            <x-filament::button
                wire:click="saveAndExit"
                color="gray"
                icon="heroicon-o-arrow-left-on-rectangle"
            >
                Save & Exit
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
