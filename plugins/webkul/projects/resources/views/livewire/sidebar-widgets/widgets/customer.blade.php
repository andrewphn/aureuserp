{{-- Customer Widget with Tabbed Details --}}
<div class="space-y-1.5" x-data="{ showDetails: false, activeTab: 'contact' }">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h4 class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Customer</h4>
        <div class="flex items-center gap-0.5">
            @if($this->customerName)
                <x-filament::icon-button
                    icon="heroicon-m-chevron-down"
                    color="gray"
                    size="xs"
                    x-on:click="showDetails = !showDetails"
                    ::class="showDetails ? 'rotate-180' : ''"
                    class="transition-transform duration-200 !h-6 !w-6"
                />
                <x-filament::icon-button
                    icon="heroicon-m-pencil-square"
                    color="gray"
                    size="xs"
                    wire:click="$dispatch('open-customer-modal', { partnerId: {{ $this->data['partner_id'] ?? 'null' }} })"
                    label="Edit customer"
                    class="!h-6 !w-6"
                />
            @else
                <x-filament::icon-button
                    icon="heroicon-m-plus-circle"
                    color="success"
                    size="xs"
                    wire:click="$dispatch('open-customer-modal', { partnerId: null })"
                    label="Add new customer"
                    class="!h-6 !w-6"
                />
            @endif
        </div>
    </div>

    @if($this->customerName)
        {{-- Primary Info --}}
        <div class="flex items-center gap-1.5">
            <x-filament::icon icon="heroicon-o-user" class="h-3.5 w-3.5 text-gray-400 flex-shrink-0" />
            <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $this->customerName }}</span>
        </div>

        {{-- Quick Summary (when collapsed) --}}
        <div x-show="!showDetails" class="text-[11px] text-gray-500 dark:text-gray-400 pl-5" @click="showDetails = true">
            @if($this->customer?->phone || $this->customer?->email)
                <span class="cursor-pointer hover:text-primary-500">
                    {{ $this->customer?->phone ?: Str::limit($this->customer?->email, 22) }}
                </span>
            @endif
            @if($this->customerHistory && $this->customerHistory['totalProjects'] > 0)
                <span class="text-gray-300 dark:text-gray-600 mx-1">·</span>
                <span class="text-primary-500 cursor-pointer">{{ $this->customerHistory['totalProjects'] }} projects</span>
            @endif
        </div>

        {{-- Expanded Tabbed Details --}}
        <div x-show="showDetails" x-collapse class="space-y-1.5 pt-1">
            {{-- Tab Navigation --}}
            <div class="flex flex-wrap gap-0.5 border-b border-gray-100 dark:border-gray-700 pb-1">
                <button type="button" @click="activeTab = 'contact'"
                    :class="activeTab === 'contact' ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-medium rounded transition-colors">
                    <x-filament::icon icon="heroicon-m-phone" class="h-2.5 w-2.5" />
                    <span>Contact</span>
                </button>
                <button type="button" @click="activeTab = 'address'"
                    :class="activeTab === 'address' ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-medium rounded transition-colors">
                    <x-filament::icon icon="heroicon-m-map-pin" class="h-2.5 w-2.5" />
                    <span>Address</span>
                </button>
                @if($this->customerHistory && $this->customerHistory['totalProjects'] > 0)
                    <button type="button" @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                        class="flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-medium rounded transition-colors">
                        <x-filament::icon icon="heroicon-m-folder" class="h-2.5 w-2.5" />
                        <span>Projects</span>
                        <x-filament::badge size="xs" color="gray" class="!text-[9px] !px-1">{{ $this->customerHistory['totalProjects'] }}</x-filament::badge>
                    </button>
                @endif
                @if($this->customerChatter && ($this->customerChatter['totalMessages'] > 0 || $this->customerChatter['totalActivities'] > 0))
                    <button type="button" @click="activeTab = 'chatter'"
                        :class="activeTab === 'chatter' ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                        class="flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-medium rounded transition-colors">
                        <x-filament::icon icon="heroicon-m-chat-bubble-left-right" class="h-2.5 w-2.5" />
                        <span>Chatter</span>
                        <x-filament::badge size="xs" color="info" class="!text-[9px] !px-1">{{ $this->customerChatter['totalMessages'] + $this->customerChatter['totalActivities'] }}</x-filament::badge>
                    </button>
                @endif
                @if($this->customer?->comment)
                    <button type="button" @click="activeTab = 'notes'"
                        :class="activeTab === 'notes' ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                        class="flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-medium rounded transition-colors">
                        <x-filament::icon icon="heroicon-m-document-text" class="h-2.5 w-2.5" />
                        <span>Notes</span>
                    </button>
                @endif
            </div>

            {{-- Tab Content --}}
            <div class="min-h-[40px]">
                {{-- Contact Tab --}}
                <div x-show="activeTab === 'contact'" x-transition.opacity class="space-y-1">
                    @if($this->customer?->email)
                        <a href="mailto:{{ $this->customer->email }}" class="flex items-center gap-1 text-[11px] text-gray-600 dark:text-gray-400 hover:text-primary-500 truncate">
                            <x-filament::icon icon="heroicon-o-envelope" class="h-3 w-3 flex-shrink-0" />
                            <span class="truncate">{{ $this->customer->email }}</span>
                        </a>
                    @endif
                    @if($this->customer?->phone)
                        <a href="tel:{{ $this->customer->phone }}" class="flex items-center gap-1 text-[11px] text-gray-600 dark:text-gray-400 hover:text-primary-500">
                            <x-filament::icon icon="heroicon-o-phone" class="h-3 w-3 flex-shrink-0" />
                            {{ $this->customer->phone }}
                        </a>
                    @endif
                    @if($this->customer?->mobile)
                        <a href="tel:{{ $this->customer->mobile }}" class="flex items-center gap-1 text-[11px] text-gray-600 dark:text-gray-400 hover:text-primary-500">
                            <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-3 w-3 flex-shrink-0" />
                            {{ $this->customer->mobile }}
                        </a>
                    @endif
                    @if($this->customer?->company_name)
                        <div class="flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-500 pt-0.5">
                            <x-filament::icon icon="heroicon-o-building-office" class="h-3 w-3 flex-shrink-0" />
                            {{ $this->customer->company_name }}
                        </div>
                    @endif
                    @if(!$this->customer?->email && !$this->customer?->phone && !$this->customer?->mobile)
                        <p class="text-[11px] text-gray-400 italic">No contact info</p>
                    @endif
                </div>

                {{-- Address Tab --}}
                <div x-show="activeTab === 'address'" x-transition.opacity class="space-y-1">
                    @php
                        $addressParts = collect([
                            $this->customer?->street,
                            $this->customer?->street2,
                        ])->filter()->implode("\n");
                        $cityStateZip = trim(($this->customer?->city ?? '') . ', ' . ($this->customer?->state?->code ?? '') . ' ' . ($this->customer?->zip ?? ''));
                        $hasAddress = $addressParts || (strlen(trim($cityStateZip)) > 3);
                    @endphp
                    @if($hasAddress)
                        <div class="text-[11px] text-gray-600 dark:text-gray-400 whitespace-pre-line leading-tight">
                            @if($addressParts){{ $addressParts }}@endif
                            @if($addressParts && strlen(trim($cityStateZip)) > 3){{ "\n" }}@endif
                            @if(strlen(trim($cityStateZip)) > 3){{ $cityStateZip }}@endif
                        </div>
                        @if($this->customer?->country?->name)
                            <div class="flex items-center gap-1 text-[11px] text-gray-500">
                                <x-filament::icon icon="heroicon-o-globe-americas" class="h-3 w-3 flex-shrink-0" />
                                {{ $this->customer->country->name }}
                            </div>
                        @endif
                    @else
                        <p class="text-[11px] text-gray-400 italic">No address</p>
                    @endif
                </div>

                {{-- Projects/History Tab --}}
                @if($this->customerHistory && $this->customerHistory['totalProjects'] > 0)
                    <div x-show="activeTab === 'history'" x-transition.opacity class="space-y-1.5">
                        {{-- Stats Row --}}
                        <div class="flex gap-2 text-[10px]">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded px-1.5 py-0.5">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $this->customerHistory['totalProjects'] }}</span>
                                <span class="text-gray-500">projects</span>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded px-1.5 py-0.5">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($this->customerHistory['totalLinearFeet'], 0) }}</span>
                                <span class="text-gray-500">LF</span>
                            </div>
                        </div>
                        {{-- Recent Projects --}}
                        <div class="space-y-0.5">
                            @foreach($this->customerHistory['projects'] as $project)
                                <a href="{{ route('filament.admin.resources.project.projects.view', ['record' => $project->id]) }}"
                                   target="_blank"
                                   class="flex items-center justify-between text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-1 py-0.5 -mx-1 transition-colors group">
                                    <span class="flex items-center gap-1 text-gray-600 dark:text-gray-400 group-hover:text-primary-500 truncate">
                                        <x-filament::icon icon="heroicon-o-folder" class="h-2.5 w-2.5 flex-shrink-0" />
                                        <span class="truncate">{{ $project->name ?: ($project->project_number ?: $project->draft_number) }}</span>
                                    </span>
                                    @if($project->estimated_linear_feet)
                                        <span class="text-gray-400 text-[10px] flex-shrink-0 ml-1">{{ number_format($project->estimated_linear_feet, 0) }} LF</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Chatter Tab --}}
                @if($this->customerChatter)
                    <div x-show="activeTab === 'chatter'" x-transition.opacity class="space-y-1.5">
                        @if($this->customerChatter['messages']->count() > 0)
                            <p class="text-[9px] text-gray-400 uppercase font-medium">Recent Messages</p>
                            <div class="space-y-1">
                                @foreach($this->customerChatter['messages']->take(3) as $message)
                                    <div class="text-[11px] bg-gray-50 dark:bg-gray-800 rounded px-1.5 py-1">
                                        <div class="flex items-center justify-between gap-1 mb-0.5">
                                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $message->creator?->name ?? 'System' }}</span>
                                            <span class="text-[9px] text-gray-400 flex-shrink-0">{{ $message->created_at->diffForHumans(short: true) }}</span>
                                        </div>
                                        <p class="text-gray-600 dark:text-gray-400 line-clamp-2">{{ Str::limit(strip_tags($message->body ?? ''), 80) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if($this->customerChatter['activities']->count() > 0)
                            <p class="text-[9px] text-gray-400 uppercase font-medium pt-1">Recent Activity</p>
                            <div class="space-y-0.5">
                                @foreach($this->customerChatter['activities']->take(3) as $activity)
                                    <div class="flex items-center gap-1 text-[11px] text-gray-600 dark:text-gray-400">
                                        <x-filament::icon icon="heroicon-o-clock" class="h-2.5 w-2.5 flex-shrink-0 text-gray-400" />
                                        <span class="truncate">{{ Str::limit($activity->summary ?? $activity->type ?? 'Activity', 40) }}</span>
                                        <span class="text-[9px] text-gray-400 flex-shrink-0">{{ $activity->created_at->diffForHumans(short: true) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if($this->customerChatter['messages']->isEmpty() && $this->customerChatter['activities']->isEmpty())
                            <p class="text-[11px] text-gray-400 italic">No recent activity</p>
                        @endif
                    </div>
                @endif

                {{-- Notes Tab --}}
                @if($this->customer?->comment)
                    <div x-show="activeTab === 'notes'" x-transition.opacity>
                        <p class="text-[11px] text-gray-600 dark:text-gray-400 italic whitespace-pre-line leading-tight">{{ $this->customer->comment }}</p>
                    </div>
                @endif
            </div>

            {{-- View Full Profile --}}
            @if($this->data['partner_id'])
                <div class="pt-1 border-t border-gray-100 dark:border-gray-700">
                    <x-filament::link
                        href="{{ route('filament.admin.customer.resources.partners.view', ['record' => $this->data['partner_id']]) }}"
                        target="_blank"
                        size="xs"
                        icon="heroicon-m-arrow-top-right-on-square"
                        icon-position="after"
                        class="text-[11px]"
                    >
                        View full profile
                    </x-filament::link>
                </div>
            @endif
        </div>
    @else
        <p class="text-[11px] text-gray-400 italic">Not selected</p>
    @endif
</div>
