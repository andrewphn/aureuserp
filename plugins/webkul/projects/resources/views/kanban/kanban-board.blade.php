@php
    // Exclude "To Do" from workflow stages - leads are the inbox now
    $boardStatuses = $statuses->reject(fn($s) => $s['title'] === 'To Do');

    // Leads are now the inbox
    $inboxOpen = $this->leadsInboxOpen ?? true;
    $inboxCount = $leadsCount ?? 0;
    $newInboxCount = $newLeadsCount ?? 0;
@endphp

<x-filament-panels::page class="!p-0">
    {{-- Filter Widgets - Clickable to toggle filters --}}
    @php
        $totalProjects = \Webkul\Project\Models\Project::count();

        // Blocked = has blocked tasks OR no orders OR no customer (using OR, no double-count)
        $blockedCount = \Webkul\Project\Models\Project::where(function($q) {
            $q->whereHas('tasks', fn($t) => $t->where('state', 'blocked'))
              ->orWhereDoesntHave('orders')
              ->orWhereNull('partner_id');
        })->count();

        $overdueCount = \Webkul\Project\Models\Project::where('desired_completion_date', '<', now())->count();
        $dueSoonCount = \Webkul\Project\Models\Project::whereBetween('desired_completion_date', [now(), now()->addDays(7)])->count();

        // On Track = not overdue AND not blocked (must satisfy both conditions)
        $onTrackCount = \Webkul\Project\Models\Project::where(function($q) {
            $q->whereNull('desired_completion_date')
              ->orWhere('desired_completion_date', '>=', now());
        })
        ->whereDoesntHave('tasks', fn($t) => $t->where('state', 'blocked'))
        ->whereHas('orders')
        ->whereNotNull('partner_id')
        ->count();
    @endphp
    <div class="px-3 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            {{-- All Projects Widget --}}
            <button
                wire:click="toggleWidgetFilter('all')"
                @class([
                    'flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer',
                    'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => ($this->widgetFilter ?? 'all') === 'all',
                    'border-gray-200 dark:border-gray-700 hover:border-gray-300 bg-white dark:bg-gray-800' => ($this->widgetFilter ?? 'all') !== 'all',
                ])
            >
                <div class="text-left">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalProjects }}</div>
                    <div class="text-xs text-gray-500">All Projects</div>
                </div>
            </button>

            {{-- Blocked Widget --}}
            <button
                wire:click="toggleWidgetFilter('blocked')"
                @class([
                    'flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer',
                    'border-purple-500 bg-purple-50 dark:bg-purple-900/20' => ($this->widgetFilter ?? null) === 'blocked',
                    'border-gray-200 dark:border-gray-700 hover:border-purple-300 bg-white dark:bg-gray-800' => ($this->widgetFilter ?? null) !== 'blocked',
                ])
            >
                <div class="w-3 h-8 rounded-sm" style="background-color: #7c3aed;"></div>
                <div class="text-left">
                    <div class="text-2xl font-bold" style="color: #7c3aed;">{{ $blockedCount }}</div>
                    <div class="text-xs text-gray-500">Blocked</div>
                </div>
            </button>

            {{-- Overdue Widget --}}
            <button
                wire:click="toggleWidgetFilter('overdue')"
                @class([
                    'flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer',
                    'border-red-500 bg-red-50 dark:bg-red-900/20' => ($this->widgetFilter ?? null) === 'overdue',
                    'border-gray-200 dark:border-gray-700 hover:border-red-300 bg-white dark:bg-gray-800' => ($this->widgetFilter ?? null) !== 'overdue',
                ])
            >
                <div class="w-3 h-8 rounded-sm" style="background-color: #dc2626;"></div>
                <div class="text-left">
                    <div class="text-2xl font-bold" style="color: #dc2626;">{{ $overdueCount }}</div>
                    <div class="text-xs text-gray-500">Overdue</div>
                </div>
            </button>

            {{-- Due Soon Widget --}}
            <button
                wire:click="toggleWidgetFilter('due_soon')"
                @class([
                    'flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer',
                    'border-orange-500 bg-orange-50 dark:bg-orange-900/20' => ($this->widgetFilter ?? null) === 'due_soon',
                    'border-gray-200 dark:border-gray-700 hover:border-orange-300 bg-white dark:bg-gray-800' => ($this->widgetFilter ?? null) !== 'due_soon',
                ])
            >
                <div class="w-3 h-8 rounded-sm" style="background-color: #ea580c;"></div>
                <div class="text-left">
                    <div class="text-2xl font-bold" style="color: #ea580c;">{{ $dueSoonCount }}</div>
                    <div class="text-xs text-gray-500">Due Soon</div>
                </div>
            </button>

            {{-- On Track Widget --}}
            <button
                wire:click="toggleWidgetFilter('on_track')"
                @class([
                    'flex items-center gap-3 px-4 py-2 rounded-lg border-2 transition-all duration-150 cursor-pointer',
                    'border-green-500 bg-green-50 dark:bg-green-900/20' => ($this->widgetFilter ?? null) === 'on_track',
                    'border-gray-200 dark:border-gray-700 hover:border-green-300 bg-white dark:bg-gray-800' => ($this->widgetFilter ?? null) !== 'on_track',
                ])
            >
                <div class="w-3 h-8 rounded-sm" style="background-color: #16a34a;"></div>
                <div class="text-left">
                    <div class="text-2xl font-bold" style="color: #16a34a;">{{ $onTrackCount }}</div>
                    <div class="text-xs text-gray-500">On Track</div>
                </div>
            </button>

            {{-- Active Filter Indicator --}}
            @if(($this->widgetFilter ?? 'all') !== 'all')
                <div class="ml-auto flex items-center gap-2">
                    <span class="text-xs text-gray-500">Filtering by:</span>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        {{ ucfirst(str_replace('_', ' ', $this->widgetFilter ?? 'all')) }}
                        <button wire:click="toggleWidgetFilter('all')" class="ml-1 hover:text-red-500">
                            <x-heroicon-m-x-mark class="w-3 h-3" />
                        </button>
                    </span>
                </div>
            @endif
        </div>
    </div>

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
        class="h-[calc(100vh-180px)]"
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
                    class="w-10 h-full cursor-pointer flex flex-col items-center justify-center transition-all duration-150 bg-white border-2 border-gray-900 hover:bg-gray-50"
                    title="Open Inbox ({{ $inboxCount }} inquiries)"
                >
                    <span
                        class="text-xs font-medium text-gray-900 whitespace-nowrap"
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
                    <div class="flex items-center justify-between px-4 py-2 transition-all duration-150 bg-white border-2 border-gray-900 border-b-0">
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
                    <div class="flex-1 flex flex-col gap-2 p-2 overflow-y-auto bg-white dark:bg-gray-800/50 border-2 border-t-0 border-gray-900 dark:border-gray-700">
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
            <div x-data="{ activeTab: 'contact' }" class="space-y-4">
                {{-- Filament Native Tabs --}}
                <x-filament::tabs label="Lead details">
                    <x-filament::tabs.item
                        alpine-active="activeTab === 'contact'"
                        x-on:click="activeTab = 'contact'"
                        icon="heroicon-m-user"
                    >
                        Contact
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        alpine-active="activeTab === 'project'"
                        x-on:click="activeTab = 'project'"
                        icon="heroicon-m-briefcase"
                    >
                        Project
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        alpine-active="activeTab === 'location'"
                        x-on:click="activeTab = 'location'"
                        icon="heroicon-m-map-pin"
                    >
                        Location
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        alpine-active="activeTab === 'tracking'"
                        x-on:click="activeTab = 'tracking'"
                        icon="heroicon-m-chart-bar"
                    >
                        Tracking
                    </x-filament::tabs.item>
                </x-filament::tabs>

                {{-- Tab Content --}}
                <div class="min-h-[300px]">
                    {{-- Contact Tab --}}
                    <div x-show="activeTab === 'contact'" x-cloak class="space-y-4">
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
                                @if($selectedLead->source)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Lead Source:</span>
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                            {{ $selectedLead->source->getLabel() }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Metadata --}}
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <x-heroicon-m-clock class="w-4 h-4" />
                                Submission Info
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Submitted:</span>
                                    <span class="ml-1">{{ $selectedLead->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Days Ago:</span>
                                    <span class="ml-1">{{ $selectedLead->days_since_submission }} days</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Status:</span>
                                    <span class="ml-1 capitalize">{{ $selectedLead->status->value ?? 'New' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Project Tab --}}
                    <div x-show="activeTab === 'project'" x-cloak class="space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <x-heroicon-m-briefcase class="w-4 h-4" />
                                Project Details
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                @if($selectedLead->project_type)
                                    <div class="col-span-2">
                                        <span class="text-gray-500 dark:text-gray-400">Project Type:</span>
                                        <span class="ml-1 font-medium">{{ is_array($selectedLead->project_type) ? implode(', ', $selectedLead->project_type) : $selectedLead->project_type }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->project_phase)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Project Phase:</span>
                                        <span class="ml-1 capitalize">{{ str_replace('_', ' ', $selectedLead->project_phase) }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->budget_range)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Budget Range:</span>
                                        <span class="ml-1 font-medium text-green-600">
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
                                @if($selectedLead->timeline_start_date)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Start Date:</span>
                                        <span class="ml-1">{{ $selectedLead->timeline_start_date?->format('M d, Y') }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->timeline_completion_date)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Completion:</span>
                                        <span class="ml-1">{{ $selectedLead->timeline_completion_date?->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($selectedLead->design_style || $selectedLead->wood_species || $selectedLead->finish_choices)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-paint-brush class="w-4 h-4" />
                                    Design Preferences
                                </h3>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    @if($selectedLead->design_style)
                                        <div class="col-span-2">
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
                                    @if($selectedLead->finish_choices)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Finish:</span>
                                            <span class="ml-1">{{ is_array($selectedLead->finish_choices) ? implode(', ', $selectedLead->finish_choices) : $selectedLead->finish_choices }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($selectedLead->message || $selectedLead->project_description || $selectedLead->additional_information)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-chat-bubble-left-ellipsis class="w-4 h-4" />
                                    Message / Notes
                                </h3>
                                @if($selectedLead->message || $selectedLead->project_description)
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-3">{{ $selectedLead->message ?? $selectedLead->project_description }}</p>
                                @endif
                                @if($selectedLead->additional_information)
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                        <span class="text-xs text-gray-500 font-medium">Additional Info:</span>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $selectedLead->additional_information }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Location Tab --}}
                    <div x-show="activeTab === 'location'" x-cloak class="space-y-4">
                        @if($selectedLead->city || $selectedLead->street1)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-map-pin class="w-4 h-4" />
                                    Project Location
                                </h3>
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    @if($selectedLead->street1)<p>{{ $selectedLead->street1 }}</p>@endif
                                    @if($selectedLead->street2)<p>{{ $selectedLead->street2 }}</p>@endif
                                    <p>
                                        {{ $selectedLead->city }}@if($selectedLead->state), {{ $selectedLead->state }}@endif
                                        @if($selectedLead->zip) {{ $selectedLead->zip }}@endif
                                    </p>
                                    @if($selectedLead->country && $selectedLead->country !== 'United States')
                                        <p>{{ $selectedLead->country }}</p>
                                    @endif
                                </div>
                                @if($selectedLead->project_address_notes)
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                        <span class="text-xs text-gray-500 font-medium">Location Notes:</span>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $selectedLead->project_address_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-8 text-center">
                                <x-heroicon-o-map-pin class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                                <p class="text-gray-500">No location information provided</p>
                            </div>
                        @endif
                    </div>

                    {{-- Tracking Tab --}}
                    <div x-show="activeTab === 'tracking'" x-cloak class="space-y-4">
                        {{-- UTM Attribution --}}
                        @if($selectedLead->utm_source || $selectedLead->utm_medium || $selectedLead->utm_campaign)
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-megaphone class="w-4 h-4 text-blue-600" />
                                    Marketing Attribution
                                </h3>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    @if($selectedLead->utm_source)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Source:</span>
                                            <span class="ml-1 font-medium">{{ $selectedLead->utm_source }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->utm_medium)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Medium:</span>
                                            <span class="ml-1">{{ $selectedLead->utm_medium }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->utm_campaign)
                                        <div class="col-span-2">
                                            <span class="text-gray-500 dark:text-gray-400">Campaign:</span>
                                            <span class="ml-1">{{ $selectedLead->utm_campaign }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->utm_content)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Content:</span>
                                            <span class="ml-1">{{ $selectedLead->utm_content }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->utm_term)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Term:</span>
                                            <span class="ml-1">{{ $selectedLead->utm_term }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Click IDs --}}
                        @if($selectedLead->gclid || $selectedLead->fbclid || $selectedLead->msclkid)
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-cursor-arrow-rays class="w-4 h-4 text-purple-600" />
                                    Ad Platform Click IDs
                                </h3>
                                <div class="space-y-2 text-sm">
                                    @if($selectedLead->gclid)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Google</span>
                                            <span class="text-gray-600 truncate text-xs">{{ Str::limit($selectedLead->gclid, 30) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->fbclid)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-600 text-white">Facebook</span>
                                            <span class="text-gray-600 truncate text-xs">{{ Str::limit($selectedLead->fbclid, 30) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->msclkid)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800">Microsoft</span>
                                            <span class="text-gray-600 truncate text-xs">{{ Str::limit($selectedLead->msclkid, 30) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Device & Session Info --}}
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <x-heroicon-m-device-phone-mobile class="w-4 h-4" />
                                Device & Session
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                @if($selectedLead->device_type)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Device:</span>
                                        <span class="ml-1 capitalize">{{ $selectedLead->device_type }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->browser)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Browser:</span>
                                        <span class="ml-1">{{ $selectedLead->browser }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->operating_system)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">OS:</span>
                                        <span class="ml-1">{{ $selectedLead->operating_system }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->visit_count)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Visits:</span>
                                        <span class="ml-1">{{ $selectedLead->visit_count }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->pages_viewed)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Pages Viewed:</span>
                                        <span class="ml-1">{{ $selectedLead->pages_viewed }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->time_on_site_seconds)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Time on Site:</span>
                                        <span class="ml-1">{{ gmdate('i:s', $selectedLead->time_on_site_seconds) }}</span>
                                    </div>
                                @endif
                                @if($selectedLead->ip_address)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">IP:</span>
                                        <span class="ml-1 text-xs">{{ $selectedLead->ip_address }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- First/Last Touch Attribution --}}
                        @if($selectedLead->first_touch_source || $selectedLead->last_touch_source)
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-arrow-path class="w-4 h-4 text-green-600" />
                                    Attribution Journey
                                </h3>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    @if($selectedLead->first_touch_source)
                                        <div class="space-y-1">
                                            <span class="text-xs font-medium text-gray-500 uppercase">First Touch</span>
                                            <div class="text-gray-900 dark:text-white">{{ $selectedLead->first_touch_source }}</div>
                                            @if($selectedLead->first_touch_medium)
                                                <div class="text-xs text-gray-500">{{ $selectedLead->first_touch_medium }}</div>
                                            @endif
                                        </div>
                                    @endif
                                    @if($selectedLead->last_touch_source)
                                        <div class="space-y-1">
                                            <span class="text-xs font-medium text-gray-500 uppercase">Last Touch</span>
                                            <div class="text-gray-900 dark:text-white">{{ $selectedLead->last_touch_source }}</div>
                                            @if($selectedLead->last_touch_medium)
                                                <div class="text-xs text-gray-500">{{ $selectedLead->last_touch_medium }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Landing/Referrer --}}
                        @if($selectedLead->landing_page || $selectedLead->referrer_url)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-m-globe-alt class="w-4 h-4" />
                                    Page Info
                                </h3>
                                <div class="space-y-2 text-sm">
                                    @if($selectedLead->landing_page)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-xs">Landing Page:</span>
                                            <span class="text-xs text-gray-600 break-all">{{ $selectedLead->landing_page }}</span>
                                        </div>
                                    @endif
                                    @if($selectedLead->referrer_url)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-xs">Referrer:</span>
                                            <span class="text-xs text-gray-600 break-all">{{ $selectedLead->referrer_url }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if(!$selectedLead->utm_source && !$selectedLead->device_type && !$selectedLead->first_touch_source)
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-8 text-center">
                                <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                                <p class="text-gray-500">No tracking data available</p>
                                <p class="text-xs text-gray-400 mt-1">This lead was submitted before tracking was enabled</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions (always visible) --}}
                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <x-filament::button
                        wire:click="convertLeadToProject({{ $selectedLead->id }})"
                        color="success"
                        icon="heroicon-m-arrow-right-circle"
                        class="flex-1"
                    >
                        Convert to Project
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.admin.resources.leads.edit', $selectedLead->id) }}"
                        color="gray"
                        icon="heroicon-m-pencil"
                    >
                        Edit
                    </x-filament::button>

                    <x-filament::button
                        wire:click="updateLeadStatus({{ $selectedLead->id }}, 'disqualified')"
                        x-on:click="$dispatch('close-modal', { id: 'kanban--lead-detail-modal' })"
                        color="danger"
                        icon="heroicon-m-x-circle"
                        outlined
                    >
                        Disqualify
                    </x-filament::button>
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
