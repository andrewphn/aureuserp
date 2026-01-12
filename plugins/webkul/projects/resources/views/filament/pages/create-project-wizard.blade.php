<x-filament-panels::page
    x-data="{
        autoSaveInterval: null,
        lastSaveTime: null,
        displayTime: '',
        isSaving: false,
        updateInterval: null,
        draftTimestamp: '{{ $this->draft?->updated_at?->toISOString() ?? '' }}',

        init() {
            if (this.draftTimestamp) {
                this.lastSaveTime = new Date(this.draftTimestamp);
                this.updateDisplayTime();
            }

            this.autoSaveInterval = setInterval(() => {
                this.save();
            }, 30000);

            this.updateInterval = setInterval(() => {
                this.updateDisplayTime();
            }, 30000);
        },

        save() {
            this.isSaving = true;
            this.$wire.saveDraft().then(() => {
                this.lastSaveTime = new Date();
                this.displayTime = 'just now';
                this.isSaving = false;
            });
        },

        updateDisplayTime() {
            if (!this.lastSaveTime) return;
            const now = new Date();
            const diffMs = now - this.lastSaveTime;
            const diffSeconds = Math.floor(diffMs / 1000);
            const diffMinutes = Math.floor(diffSeconds / 60);
            const diffHours = Math.floor(diffMinutes / 60);

            if (diffSeconds < 60) {
                this.displayTime = 'just now';
            } else if (diffMinutes < 60) {
                this.displayTime = diffMinutes === 1 ? '1 minute ago' : diffMinutes + ' minutes ago';
            } else if (diffHours < 24) {
                this.displayTime = diffHours === 1 ? '1 hour ago' : diffHours + ' hours ago';
            } else {
                this.displayTime = this.lastSaveTime.toLocaleDateString();
            }
        },

        destroy() {
            if (this.autoSaveInterval) clearInterval(this.autoSaveInterval);
            if (this.updateInterval) clearInterval(this.updateInterval);
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
            {{-- Saving indicator --}}
            <template x-if="isSaving">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Saving...</span>
                </span>
            </template>

            {{-- Saved indicator --}}
            <template x-if="!isSaving && lastSaveTime">
                <span class="flex items-center gap-1.5 transition-opacity duration-300">
                    <x-heroicon-o-cloud-arrow-up class="w-3.5 h-3.5 text-green-500" />
                    <span>Draft saved <span x-text="displayTime"></span></span>
                </span>
            </template>

            {{-- Initial state --}}
            <template x-if="!isSaving && !lastSaveTime">
                <span class="flex items-center gap-1.5">
                    <x-heroicon-o-cloud class="w-3.5 h-3.5" />
                    <span>Auto-saving enabled</span>
                </span>
            </template>
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
        {{-- wire:key forces re-render when data changes to avoid reactive prop mutation error --}}
        <div class="w-full xl:w-80 xl:max-w-xs xl:flex-shrink-0 xl:sticky xl:top-20">
            <livewire:project-summary-sidebar
                wire:key="sidebar-{{ md5(json_encode($this->data ?? [])) }}"
                :data="$this->data"
                :stage="'discovery'"
                :widgets="['project_preview', 'customer', 'checkout_summary', 'capacity', 'project_type', 'location', 'budget', 'timeline', 'documents', 'lead_source']"
                :show-header="true"
                :show-footer="false"
                :footer-widget="null"
                :price-per-linear-foot="350.00"
                :collapsible="true"
            />
        </div>
    </div>


</x-filament-panels::page>
