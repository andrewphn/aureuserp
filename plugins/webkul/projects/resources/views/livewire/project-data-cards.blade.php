<div>
    <!-- View Mode Toggle using Filament Tabs -->
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-white/10">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Project Breakdown</h3>

        <x-filament::tabs>
            <x-filament::tabs.item
                wire:click="$set('viewMode', 'cards')"
                :active="$viewMode === 'cards'"
                icon="heroicon-m-squares-2x2"
            >
                Cards
            </x-filament::tabs.item>
            <x-filament::tabs.item
                wire:click="$set('viewMode', 'table')"
                :active="$viewMode === 'table'"
                icon="heroicon-m-table-cells"
            >
                Table
            </x-filament::tabs.item>
        </x-filament::tabs>
    </div>

    @if($viewMode === 'cards')
        <!-- Card Grid View -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @forelse($rooms as $room)
                @php
                    $roomTypeColor = match($room->room_type) {
                        'kitchen' => 'info',
                        'pantry' => 'success',
                        'bathroom' => 'primary',
                        'office' => 'warning',
                        'laundry' => 'gray',
                        default => 'gray',
                    };
                    $roomIcon = match($room->room_type) {
                        'kitchen' => 'heroicon-o-fire',
                        'pantry' => 'heroicon-o-archive-box',
                        'bathroom' => 'heroicon-o-beaker',
                        'office' => 'heroicon-o-briefcase',
                        default => 'heroicon-o-home',
                    };
                    $isExpanded = in_array($room->id, $expandedRooms);
                @endphp

                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                    <!-- Room Card Header -->
                    <div
                        wire:click="toggleRoom({{ $room->id }})"
                        class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <!-- Room Icon -->
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                    <x-filament::icon
                                        :icon="$roomIcon"
                                        class="h-5 w-5 text-gray-500 dark:text-gray-400"
                                    />
                                </div>

                                <!-- Room Info -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-sm font-semibold text-gray-950 dark:text-white truncate">{{ $room->name }}</h4>
                                        <x-filament::badge :color="$roomTypeColor" size="sm">
                                            {{ ucfirst($room->room_type ?? 'Other') }}
                                        </x-filament::badge>
                                    </div>
                                    @if($room->floor_number)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Floor {{ $room->floor_number }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Expand Icon -->
                            <x-filament::icon
                                icon="heroicon-m-chevron-down"
                                class="h-5 w-5 text-gray-400 transition-transform duration-200 flex-shrink-0 {{ $isExpanded ? 'rotate-180' : '' }}"
                            />
                        </div>

                        <!-- Quick Stats Bar -->
                        <div class="mt-3 flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-o-map-pin" class="h-3.5 w-3.5 text-gray-400" />
                                <span class="text-gray-600 dark:text-gray-300">{{ $room->location_count }} {{ Str::plural('location', $room->location_count) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-o-square-3-stack-3d" class="h-3.5 w-3.5 text-gray-400" />
                                <span class="text-gray-600 dark:text-gray-300">{{ $room->cabinet_count }} {{ Str::plural('cabinet', $room->cabinet_count) }}</span>
                            </div>
                        </div>

                        <!-- LF & Value Summary -->
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-primary-50 dark:bg-primary-500/10 p-2 text-center">
                                <div class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ number_format($room->total_linear_feet, 1) }}</div>
                                <div class="text-[9px] text-gray-500 dark:text-gray-400 uppercase tracking-wide">Linear Feet</div>
                            </div>
                            <div class="rounded-lg bg-success-50 dark:bg-success-500/10 p-2 text-center">
                                <div class="text-lg font-bold text-success-600 dark:text-success-400">${{ number_format($room->total_value, 0) }}</div>
                                <div class="text-[9px] text-gray-500 dark:text-gray-400 uppercase tracking-wide">Est. Value</div>
                            </div>
                        </div>
                    </div>

                    <!-- Expandable Detail Section (Accordion) -->
                    @if($isExpanded)
                        <div class="border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                            <div class="p-3 space-y-3">
                                @foreach($room->locations as $location)
                                    @php
                                        $isLocExpanded = in_array($location->id, $expandedLocations);
                                        $locRunCount = $location->cabinetRuns->count();
                                        $locCabCount = $location->cabinetRuns->sum(fn($run) => $run->cabinets->count());
                                        $locLF = $location->cabinetRuns->sum(fn($run) => $run->cabinets->sum('linear_feet'));
                                    @endphp

                                    <!-- Location Card -->
                                    <div class="fi-section rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                                        <div
                                            wire:click="toggleLocation({{ $location->id }})"
                                            class="p-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <x-filament::icon icon="heroicon-o-map-pin" class="h-4 w-4 text-gray-400" />
                                                    <span class="text-xs font-medium text-gray-950 dark:text-white">{{ $location->name }}</span>
                                                    @if($location->location_type)
                                                        <x-filament::badge color="gray" size="sm">
                                                            {{ ucfirst($location->location_type) }}
                                                        </x-filament::badge>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $locRunCount }} {{ Str::plural('run', $locRunCount) }}</span>
                                                    @if($locLF > 0)
                                                        <x-filament::badge color="primary" size="sm">
                                                            {{ format_linear_feet($locLF) }}
                                                        </x-filament::badge>
                                                    @endif
                                                    <x-filament::icon
                                                        icon="heroicon-m-chevron-down"
                                                        class="h-4 w-4 text-gray-400 transition-transform {{ $isLocExpanded ? 'rotate-180' : '' }}"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Location Expanded: Cabinet Runs -->
                                        @if($isLocExpanded && $locRunCount > 0)
                                            <div class="border-t border-gray-200 dark:border-white/10 p-2.5 space-y-2">
                                                @foreach($location->cabinetRuns as $run)
                                                    @php
                                                        $cabCount = $run->cabinets->count();
                                                        $runLF = $run->cabinets->sum('linear_feet');
                                                    @endphp

                                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <div class="flex items-center gap-2">
                                                                <x-filament::icon icon="heroicon-o-rectangle-stack" class="h-3.5 w-3.5 text-gray-400" />
                                                                <span class="text-xs font-medium text-gray-950 dark:text-white">{{ $run->name ?: 'Run #' . $loop->iteration }}</span>
                                                                @if($run->run_type)
                                                                    <x-filament::badge color="info" size="sm">
                                                                        {{ ucfirst($run->run_type) }}
                                                                    </x-filament::badge>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                                                <span>{{ $cabCount }} cab</span>
                                                                @if($runLF > 0)
                                                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ format_linear_feet($runLF) }}</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- Inline Pricing Dropdowns -->
                                                        <div class="grid grid-cols-3 gap-1.5">
                                                            <select
                                                                wire:change="updateCabinetRun({{ $run->id }}, 'cabinet_level', $event.target.value)"
                                                                class="fi-select-input text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-950 dark:text-white py-1 px-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                                            >
                                                                @foreach($levelOptions as $value => $label)
                                                                    <option value="{{ $value }}" {{ $run->cabinet_level === $value ? 'selected' : '' }}>{{ Str::limit($label, 20) }}</option>
                                                                @endforeach
                                                            </select>
                                                            <select
                                                                wire:change="updateCabinetRun({{ $run->id }}, 'material_category', $event.target.value)"
                                                                class="fi-select-input text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-950 dark:text-white py-1 px-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                                            >
                                                                @foreach($materialOptions as $value => $label)
                                                                    <option value="{{ $value }}" {{ $run->material_category === $value ? 'selected' : '' }}>{{ Str::limit($label, 20) }}</option>
                                                                @endforeach
                                                            </select>
                                                            <select
                                                                wire:change="updateCabinetRun({{ $run->id }}, 'finish_option', $event.target.value)"
                                                                class="fi-select-input text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-950 dark:text-white py-1 px-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                                            >
                                                                @foreach($finishOptions as $value => $label)
                                                                    <option value="{{ $value }}" {{ $run->finish_option === $value ? 'selected' : '' }}>{{ Str::limit($label, 15) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                @if($room->locations->isEmpty())
                                    <p class="text-xs text-gray-400 italic text-center py-2">No locations defined</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <!-- Empty State -->
                <div class="col-span-full fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-8 text-center">
                    <x-filament::icon
                        icon="heroicon-o-home"
                        class="h-10 w-10 mx-auto text-gray-400 mb-3"
                    />
                    <p class="text-sm text-gray-500 dark:text-gray-400">No rooms defined yet</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Add rooms to get started with project breakdown</p>
                </div>
            @endforelse
        </div>

        <!-- Summary Footer -->
        @if($rooms->count() > 0)
            <div class="mt-4 fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <div class="grid grid-cols-4 gap-2">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $rooms->count() }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Rooms</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $rooms->sum('location_count') }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Locations</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($rooms->sum('total_linear_feet'), 1) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Linear Feet</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600 dark:text-success-400">${{ number_format($rooms->sum('total_value'), 0) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Value</div>
                    </div>
                </div>
            </div>
        @endif
    @else
        <!-- Table View - Show original relation managers via slot -->
        <div class="text-xs text-gray-500 dark:text-gray-400 text-center py-4 italic">
            Table view uses the standard Filament relation managers below.
        </div>
    @endif
</div>
