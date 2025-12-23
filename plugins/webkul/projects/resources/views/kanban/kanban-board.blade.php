@php
    // Separate Inbox (To Do) from workflow stages
    $inboxStatus = $statuses->firstWhere('title', 'To Do');
    $boardStatuses = $statuses->reject(fn($s) => $s['title'] === 'To Do');
    $inboxCount = $inboxStatus ? count($inboxStatus['records'] ?? []) : 0;

    // Check for new items (created in last 24 hours)
    $newInboxCount = 0;
    if ($inboxStatus && !empty($inboxStatus['records'])) {
        foreach ($inboxStatus['records'] as $record) {
            if ($record->created_at && $record->created_at->gt(now()->subDay())) {
                $newInboxCount++;
            }
        }
    }

    // Leads inbox data (passed from controller)
    $leadsInboxOpen = $this->leadsInboxOpen ?? true;
@endphp

<x-filament-panels::page class="!p-0">
    {{-- Main Kanban Board - Full Height --}}
    <div
        x-data="{
            inboxOpen: localStorage.getItem('kanban_inbox_open') === 'true',
            lastViewed: localStorage.getItem('kanban_inbox_last_viewed') || null,
            hasNewItems: {{ $newInboxCount > 0 ? 'true' : 'false' }},
            toggleInbox() {
                this.inboxOpen = !this.inboxOpen;
                localStorage.setItem('kanban_inbox_open', this.inboxOpen);
                if (this.inboxOpen) {
                    this.markAsViewed();
                }
            },
            markAsViewed() {
                this.lastViewed = Date.now();
                localStorage.setItem('kanban_inbox_last_viewed', this.lastViewed);
                this.hasNewItems = false;
            }
        }"
        x-init="inboxOpen && markAsViewed()"
        class="h-[calc(100vh-140px)]"
    >
        {{-- Single Flex Container for ALL columns (Leads Inbox + Project Inbox + Workflow Stages) --}}
        <div
            wire:ignore.self
            class="flex gap-3 h-full overflow-x-auto overflow-y-hidden px-3 py-2"
            style="scrollbar-width: thin;"
        >
            {{-- LEADS INBOX (First Column - New Inquiries) --}}
            @if(isset($leads))
                <div
                    x-data="{ leadsOpen: {{ $leadsInboxOpen ? 'true' : 'false' }} }"
                    class="flex-shrink-0 h-full"
                >
                    {{-- Collapsed State --}}
                    <div
                        x-show="!leadsOpen"
                        @click="leadsOpen = true; $wire.leadsInboxOpen = true"
                        class="w-12 h-full cursor-pointer flex flex-col items-center py-3 rounded-lg transition-all duration-200 bg-amber-600 hover:bg-amber-700"
                        title="Open Leads Inbox ({{ $leadsCount ?? 0 }} leads)"
                    >
                        {{-- Lead Icon with Count --}}
                        <div class="relative mb-1">
                            <x-heroicon-m-user-plus class="w-6 h-6 text-white" />
                            <span class="absolute -top-1.5 -right-2.5 min-w-[20px] h-[20px] flex items-center justify-center text-[11px] font-bold rounded-full px-1 {{ ($newLeadsCount ?? 0) > 0 ? 'bg-yellow-300 text-yellow-900 animate-pulse' : 'bg-white text-amber-700' }}">
                                {{ $leadsCount ?? 0 }}
                            </span>
                        </div>

                        @if(($newLeadsCount ?? 0) > 0)
                            <span class="bg-yellow-300 text-yellow-900 text-[8px] font-bold px-1.5 py-0.5 rounded mt-1 animate-bounce">NEW</span>
                        @endif

                        <div class="flex-1 flex items-center justify-center" style="writing-mode: vertical-rl; text-orientation: mixed;">
                            <span class="text-xs font-black text-white tracking-[0.2em] uppercase">LEADS</span>
                        </div>
                        <x-heroicon-m-chevron-right class="w-4 h-4 text-white/80 mt-2" />
                    </div>

                    {{-- Expanded Leads Panel --}}
                    <div
                        x-show="leadsOpen"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-x-8"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="flex flex-col h-full"
                        style="width: 280px; min-width: 280px;"
                    >
                        {{-- Header --}}
                        <div class="flex items-center justify-between px-4 py-2 rounded-t-lg shrink-0 bg-amber-600">
                            <h3 class="font-medium text-white text-sm flex items-center gap-1.5">
                                <x-heroicon-s-user-plus class="w-4 h-4" />
                                <span>Leads</span>
                                <span class="text-white/60">/</span>
                                <span class="text-white/90">{{ $leadsCount ?? 0 }}</span>
                                @if(($newLeadsCount ?? 0) > 0)
                                    <span class="text-yellow-300 text-xs">({{ $newLeadsCount }} new)</span>
                                @endif
                            </h3>
                            <button
                                @click="leadsOpen = false; $wire.leadsInboxOpen = false"
                                class="text-white/60 hover:text-white hover:bg-white/10 rounded p-1"
                            >
                                <x-heroicon-m-chevron-left class="w-4 h-4" />
                            </button>
                        </div>

                        {{-- Leads Cards --}}
                        <div class="flex-1 flex flex-col gap-2 p-2 overflow-y-auto bg-amber-50 dark:bg-amber-900/20 rounded-b-lg border border-t-0 border-amber-200 dark:border-amber-800">
                            @forelse($leads ?? [] as $lead)
                                @php
                                    $isNewLead = $lead->created_at && $lead->created_at->gt(now()->subDay());
                                @endphp
                                <div
                                    wire:click="openLeadDetails({{ $lead->id }})"
                                    class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-amber-200 dark:border-amber-700 p-3 cursor-pointer hover:shadow-md hover:border-amber-400 transition-all"
                                >
                                    @if($isNewLead)
                                        <span class="absolute -top-1 -left-1 z-10 bg-yellow-400 text-yellow-900 text-[8px] font-bold px-1 py-0.5 rounded shadow">NEW</span>
                                    @endif

                                    {{-- Lead Header --}}
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-gray-900 dark:text-white text-sm truncate">
                                                {{ $lead->full_name }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $lead->email }}
                                            </p>
                                        </div>
                                        <span class="text-xs text-gray-400 ml-2 whitespace-nowrap">
                                            {{ $lead->created_at->diffForHumans(null, true) }}
                                        </span>
                                    </div>

                                    {{-- Lead Info --}}
                                    <div class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                        @if($lead->project_type)
                                            <div class="flex items-center gap-1">
                                                <x-heroicon-m-briefcase class="w-3 h-3 text-gray-400" />
                                                <span class="truncate">{{ is_array($lead->project_type) ? implode(', ', $lead->project_type) : $lead->project_type }}</span>
                                            </div>
                                        @endif
                                        @if($lead->budget_range)
                                            <div class="flex items-center gap-1">
                                                <x-heroicon-m-currency-dollar class="w-3 h-3 text-gray-400" />
                                                <span>
                                                    @switch($lead->budget_range)
                                                        @case('under_10k') < $10K @break
                                                        @case('10k_25k') $10K-$25K @break
                                                        @case('25k_50k') $25K-$50K @break
                                                        @case('50k_100k') $50K-$100K @break
                                                        @case('over_100k') > $100K @break
                                                        @default {{ $lead->budget_range }}
                                                    @endswitch
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Source Badge --}}
                                    @if($lead->source)
                                        <div class="mt-2">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                                {{ $lead->source->getLabel() }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="flex-1 flex flex-col items-center justify-center text-gray-400 py-8 px-4">
                                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/50 rounded-full flex items-center justify-center mb-3">
                                        <x-heroicon-o-user-plus class="w-6 h-6 text-amber-500 opacity-50" />
                                    </div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 text-center">No pending leads</p>
                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 text-center">New inquiries will appear here</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            {{-- Project Inbox Sidebar (Left Side - Start of Pipeline) --}}
            @if($inboxStatus)
                {{-- Collapsed State (Vertical Tab) --}}
                <div
                    x-show="!inboxOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-4"
                    @click="toggleInbox()"
                    class="w-12 flex-shrink-0 cursor-pointer flex flex-col items-center py-3 rounded-lg transition-all duration-200 h-full bg-gray-500 hover:bg-gray-600"
                    title="Open Inbox ({{ $inboxCount }} items)"
                >
                    {{-- Inbox Icon with Count Badge --}}
                    <div class="relative mb-1">
                        <x-heroicon-m-inbox-arrow-down class="w-6 h-6 text-white rotate-180" />
                        {{-- Count Badge (always visible) --}}
                        <span
                            class="absolute -top-1.5 -right-2.5 min-w-[20px] h-[20px] flex items-center justify-center text-[11px] font-bold rounded-full px-1"
                            :class="hasNewItems ? 'bg-yellow-400 text-yellow-900 animate-pulse' : '{{ $inboxCount > 0 ? "bg-red-500 text-white" : "bg-white text-gray-600" }}'"
                        >
                            {{ $inboxCount }}
                        </span>
                    </div>

                    {{-- NEW Badge (when there are new items) --}}
                    <template x-if="hasNewItems">
                        <span class="bg-yellow-400 text-yellow-900 text-[8px] font-bold px-1.5 py-0.5 rounded mt-1 animate-bounce">
                            NEW
                        </span>
                    </template>

                    {{-- Vertical Label - Large and Prominent --}}
                    <div
                        class="flex-1 flex items-center justify-center"
                        style="writing-mode: vertical-rl; text-orientation: mixed;"
                    >
                        <span class="text-xs font-black text-white tracking-[0.2em] uppercase">
                            INBOX
                        </span>
                    </div>

                    {{-- Expand Arrow --}}
                    <x-heroicon-m-chevron-right class="w-4 h-4 text-white/80 mt-2" />
                </div>

                {{-- Expanded Inbox Panel --}}
                <div
                    x-show="inboxOpen"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-x-8"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-8"
                    class="flex-shrink-0 flex flex-col h-full"
                    style="width: 280px; min-width: 280px; max-width: 280px;"
                >
                    {{-- Inbox Header (matching column headers) --}}
                    <div class="flex items-center justify-between px-4 py-2 rounded-t-lg shrink-0" style="background-color: #6b7280;">
                        <h3 class="font-medium text-white text-sm flex items-center gap-1.5">
                            <span>Inbox</span>
                            <span class="text-white/60">/</span>
                            <span class="text-white/90">{{ $inboxCount }}</span>
                            @if($newInboxCount > 0)
                                <span class="text-yellow-300 text-xs">({{ $newInboxCount }} new)</span>
                            @endif
                        </h3>
                        <div class="flex items-center gap-1">
                            {{-- Add Project Button --}}
                            <button
                                wire:click="openCreateModal('{{ $inboxStatus['id'] }}')"
                                class="text-white/60 hover:text-white hover:bg-white/10 rounded p-1 transition-all duration-100"
                                title="Add project"
                            >
                                <x-heroicon-m-plus class="w-4 h-4" />
                            </button>
                            {{-- Collapse Button --}}
                            <button
                                @click="toggleInbox()"
                                class="text-white/60 hover:text-white hover:bg-white/10 rounded p-1 transition-all duration-100"
                                title="Collapse inbox"
                            >
                                <x-heroicon-m-chevron-left class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Inbox Cards (Draggable) --}}
                    <div
                        data-status-id="{{ $inboxStatus['id'] }}"
                        class="flex-1 flex flex-col gap-2 p-2 overflow-y-auto bg-gray-100/70 dark:bg-gray-800/50 rounded-b-lg border border-t-0 border-gray-200 dark:border-gray-700"
                        style="scrollbar-width: thin;"
                    >
                        @php $status = $inboxStatus; @endphp
                        @forelse($inboxStatus['records'] as $record)
                            @php
                                $isNewItem = $record->created_at && $record->created_at->gt(now()->subDay());
                            @endphp
                            <div class="relative">
                                @if($isNewItem)
                                    <span class="absolute -top-1 -left-1 z-10 bg-yellow-400 text-yellow-900 text-[8px] font-bold px-1 py-0.5 rounded shadow">
                                        NEW
                                    </span>
                                @endif
                                @include(static::$recordView)
                            </div>
                        @empty
                            <div class="flex-1 flex flex-col items-center justify-center text-gray-400 py-8 px-4">
                                <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
                                    <x-heroicon-o-inbox class="w-6 h-6 opacity-50" />
                                </div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 text-center">Inbox is empty</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 text-center leading-relaxed">New projects will appear here</p>
                                <button
                                    wire:click="openCreateModal('{{ $inboxStatus['id'] }}')"
                                    class="mt-3 text-xs text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
                                >
                                    <x-heroicon-m-plus class="w-3.5 h-3.5" />
                                    Add project
                                </button>
                            </div>
                        @endforelse
                    </div>

                </div>
            @endif

            {{-- Workflow Stage Columns --}}
            @foreach($boardStatuses as $status)
                @include(static::$statusView)
            @endforeach

            <div wire:ignore>
                @include(static::$scriptsView)
            </div>
        </div>
    </div>

    {{-- Edit Record Modal (from package) --}}
    @unless($disableEditModal)
        <x-filament-kanban::edit-record-modal/>
    @endunless

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

    {{-- Lead Detail Modal --}}
    <x-filament::modal
        id="kanban--lead-detail-modal"
        :close-by-clicking-away="true"
        :close-button="true"
        slide-over
        width="2xl"
    >
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-user-plus class="w-5 h-5 text-amber-500" />
                <span>Lead Details</span>
            </div>
        </x-slot>

        @if($selectedLead ?? null)
            <div class="space-y-6">
                {{-- Contact Info Section --}}
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <x-heroicon-m-user class="w-4 h-4" />
                        Contact Information
                    </h3>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Name:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $selectedLead->full_name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Email:</span>
                            <a href="mailto:{{ $selectedLead->email }}" class="text-primary-600 hover:underline ml-1">{{ $selectedLead->email }}</a>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Phone:</span>
                            <a href="tel:{{ $selectedLead->phone }}" class="text-primary-600 hover:underline ml-1">{{ $selectedLead->phone }}</a>
                        </div>
                        @if($selectedLead->company_name)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Company:</span>
                                <span class="ml-1">{{ $selectedLead->company_name }}</span>
                            </div>
                        @endif
                        @if($selectedLead->preferred_contact_method)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Preferred Contact:</span>
                                <span class="ml-1 capitalize">{{ $selectedLead->preferred_contact_method }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Project Info Section --}}
                @if($selectedLead->project_type || $selectedLead->budget_range || $selectedLead->message)
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <x-heroicon-m-briefcase class="w-4 h-4" />
                            Project Details
                        </h3>
                        <div class="space-y-3 text-sm">
                            @if($selectedLead->project_type)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Project Type:</span>
                                    <span class="ml-1">{{ is_array($selectedLead->project_type) ? implode(', ', $selectedLead->project_type) : $selectedLead->project_type }}</span>
                                </div>
                            @endif
                            @if($selectedLead->budget_range)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Budget Range:</span>
                                    <span class="ml-1 font-medium">
                                        @switch($selectedLead->budget_range)
                                            @case('under_10k') Under $10,000 @break
                                            @case('10k_25k') $10,000 - $25,000 @break
                                            @case('25k_50k') $25,000 - $50,000 @break
                                            @case('50k_100k') $50,000 - $100,000 @break
                                            @case('over_100k') Over $100,000 @break
                                            @default {{ $selectedLead->budget_range }}
                                        @endswitch
                                    </span>
                                </div>
                            @endif
                            @if($selectedLead->design_style)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Design Style:</span>
                                    <span class="ml-1">{{ is_array($selectedLead->design_style) ? implode(', ', $selectedLead->design_style) : $selectedLead->design_style }}</span>
                                </div>
                            @endif
                            @if($selectedLead->wood_species)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Wood Species:</span>
                                    <span class="ml-1">{{ ucfirst(str_replace('_', ' ', $selectedLead->wood_species)) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Message Section --}}
                @if($selectedLead->message || $selectedLead->project_description)
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <x-heroicon-m-chat-bubble-left-ellipsis class="w-4 h-4" />
                            Message
                        </h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $selectedLead->message ?? $selectedLead->project_description }}</p>
                    </div>
                @endif

                {{-- Address Section --}}
                @if($selectedLead->city || $selectedLead->street1)
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <x-heroicon-m-map-pin class="w-4 h-4" />
                            Project Location
                        </h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            @if($selectedLead->street1){{ $selectedLead->street1 }}<br>@endif
                            @if($selectedLead->street2){{ $selectedLead->street2 }}<br>@endif
                            {{ $selectedLead->city }}@if($selectedLead->state), {{ $selectedLead->state }}@endif @if($selectedLead->zip){{ $selectedLead->zip }}@endif
                        </p>
                    </div>
                @endif

                {{-- Metadata --}}
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <span>Submitted: {{ $selectedLead->created_at->format('M d, Y g:i A') }}</span>
                        @if($selectedLead->source)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                {{ $selectedLead->source->getLabel() }}
                            </span>
                        @endif
                    </div>
                    <span class="text-gray-400">{{ $selectedLead->days_since_submission }} days ago</span>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 pt-4">
                    <button
                        wire:click="convertLeadToProject({{ $selectedLead->id }})"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
                    >
                        <x-heroicon-m-arrow-right-circle class="w-5 h-5" />
                        Convert to Project
                    </button>
                    <button
                        wire:click="updateLeadStatus({{ $selectedLead->id }}, 'disqualified')"
                        x-on:click="$dispatch('close-modal', { id: 'kanban--lead-detail-modal' })"
                        class="flex items-center justify-center gap-2 px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors"
                    >
                        <x-heroicon-m-x-circle class="w-5 h-5" />
                        Disqualify
                    </button>
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-heroicon-o-user-plus class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>Select a lead to view details</p>
            </div>
        @endif
    </x-filament::modal>
</x-filament-panels::page>
