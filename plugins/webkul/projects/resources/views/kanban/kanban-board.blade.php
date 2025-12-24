@php
    // Exclude "To Do" from workflow stages - leads are the inbox now
    $boardStatuses = $statuses->reject(fn($s) => $s['title'] === 'To Do');

    // Leads are now the inbox
    $inboxOpen = $this->leadsInboxOpen ?? true;
    $inboxCount = $leadsCount ?? 0;
    $newInboxCount = $newLeadsCount ?? 0;
@endphp

<x-filament-panels::page class="!p-0">
    {{-- Main Kanban Board - Full Height --}}
    <div
        x-data="{
            inboxOpen: {{ $inboxOpen ? 'true' : 'false' }},
            hasNewItems: {{ $newInboxCount > 0 ? 'true' : 'false' }},
            toggleInbox() {
                this.inboxOpen = !this.inboxOpen;
                $wire.leadsInboxOpen = this.inboxOpen;
            }
        }"
        class="h-[calc(100vh-140px)]"
    >
        {{-- Single Flex Container for ALL columns (Inbox + Workflow Stages) --}}
        <div
            wire:ignore.self
            class="flex gap-3 h-full overflow-x-auto overflow-y-hidden px-3 py-2"
            style="scrollbar-width: thin;"
        >
            {{-- INBOX COLUMN (Leads / New Inquiries) --}}
            <div class="flex-shrink-0 h-full">
                {{-- Collapsed State --}}
                <div
                    x-show="!inboxOpen"
                    @click="toggleInbox()"
                    class="w-10 h-full cursor-pointer flex flex-col items-center py-4 rounded-lg transition-all duration-150 bg-white border-2 border-gray-900 hover:bg-gray-50"
                    title="Open Inbox ({{ $inboxCount }} inquiries)"
                >
                    <span
                        class="text-xs font-medium text-gray-900"
                        style="writing-mode: vertical-rl; text-orientation: mixed;"
                    >
                        Inbox ({{ $inboxCount }})
                    </span>
                </div>

                {{-- Expanded Inbox Panel --}}
                <div
                    x-show="inboxOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="flex flex-col h-full"
                    style="width: 280px; min-width: 280px; max-width: 280px;"
                >
                    {{-- Header - Black outlined, no fill --}}
                    <div class="flex items-center justify-between px-4 py-2 rounded-t-lg transition-all duration-150 bg-white border-2 border-gray-900 border-b-0">
                        <h3 class="font-medium text-gray-900 text-sm flex items-center gap-1.5">
                            <span>Inbox</span>
                            <span class="text-gray-400">/</span>
                            <span class="text-gray-700">{{ $inboxCount }}</span>
                        </h3>
                        <div class="flex items-center gap-1">
                            {{-- Add Lead Button --}}
                            <a
                                href="{{ route('filament.admin.resources.leads.create') }}"
                                class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded p-1 transition-all duration-100"
                                title="Add new lead"
                            >
                                <x-heroicon-m-plus class="w-4 h-4" />
                            </a>
                            {{-- Collapse Button --}}
                            <button
                                @click="toggleInbox()"
                                class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded p-1 transition-all duration-100"
                                title="Collapse inbox"
                            >
                                <x-heroicon-m-chevron-double-left class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Lead Cards - Black outlined container --}}
                    <div class="flex-1 flex flex-col gap-2 p-2 overflow-y-auto bg-white dark:bg-gray-800/50 rounded-b-lg border-2 border-t-0 border-gray-900 dark:border-gray-700">
                        @forelse($leads ?? [] as $lead)
                            <div
                                wire:click="openLeadDetails({{ $lead->id }})"
                                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3 cursor-pointer hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all"
                            >
                                {{-- Lead Header --}}
                                <div class="flex items-start justify-between mb-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm truncate flex-1">
                                        {{ $lead->full_name }}
                                    </h4>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mb-2">
                                    {{ $lead->email }}
                                </p>

                                {{-- Lead Info --}}
                                @if($lead->project_type || $lead->budget_range)
                                    <div class="space-y-1 text-xs text-gray-600 dark:text-gray-300 mb-2">
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
                                @endif

                                {{-- Footer with source and time --}}
                                <div class="flex items-center justify-between text-[10px]">
                                    @if($lead->source)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $lead->source->getLabel() }}
                                        </span>
                                    @else
                                        <span></span>
                                    @endif
                                    <span class="text-gray-400">{{ $lead->created_at->diffForHumans(null, true) }}</span>
                                </div>
                            </div>
                        @empty
                            {{-- Empty State - Matching other columns --}}
                            <div class="flex-1 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 py-8">
                                <x-heroicon-o-inbox class="w-8 h-8 mb-2 opacity-40" />
                                <p class="text-xs">No leads</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

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
