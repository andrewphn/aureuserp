<x-filament-panels::page
    x-data="{
        autoSaveInterval: null,
        init() {
            // Auto-save every 30 seconds (Don't Make Me Think principle: removes anxiety about losing work)
            this.autoSaveInterval = setInterval(() => {
                $wire.saveDraft();
            }, 30000);
        },
        destroy() {
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
            }
        }
    }"
>
    {{-- Draft Resume Banner --}}
    @if($this->draft)
        <div class="rounded-lg bg-warning-50 dark:bg-warning-500/10 p-4 border border-warning-200 dark:border-warning-500/20 mb-6">
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

    {{-- Auto-Save Status Indicator (subtle, in header area) --}}
    <div class="flex items-center justify-end mb-2 text-xs text-gray-400 dark:text-gray-500">
        <span class="flex items-center gap-1.5">
            @if($this->lastSavedAt)
                <x-heroicon-o-cloud-arrow-up class="w-3.5 h-3.5 text-green-500" />
                <span>Draft saved {{ $this->lastSavedAt }}</span>
            @else
                <x-heroicon-o-cloud class="w-3.5 h-3.5" />
                <span>Auto-saving enabled</span>
            @endif
        </span>
    </div>

    {{-- Two Column Layout: Wizard + Summary Sidebar --}}
    {{--
        Following Medusa TwoColumnLayout pattern (FilamentPHP best practice):
        - Mobile/Tablet: Stacked vertically (flex-col)
        - Desktop XL+: Side-by-side (flex-row) with constrained sidebar width
        - Sidebar has max-width to prevent layout breaking
        - items-start keeps sidebar at top when scrolling form
    --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:gap-6">
        {{-- Main Column: Wizard Form (grows to fill available space) --}}
        <div class="w-full min-w-0 flex-1">
            <form wire:submit="create">
                {{ $this->form }}
            </form>
        </div>

        {{-- Sidebar Column: Summary (constrained width on desktop) --}}
        <div class="w-full xl:w-80 xl:max-w-xs xl:flex-shrink-0 xl:sticky xl:top-20">
            <livewire:project-summary-sidebar
                :data="$this->data"
                :stage="'discovery'"
                :widgets="['project_preview', 'customer', 'project_type', 'location', 'scope', 'budget', 'timeline', 'documents', 'lead_source']"
                :show-header="true"
                :show-footer="true"
                :footer-widget="'estimate'"
                :price-per-linear-foot="350.00"
                :collapsible="true"
            />
        </div>
    </div>

</x-filament-panels::page>
