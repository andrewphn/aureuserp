<div class="relative" style="margin-left: 24px;">
    {{-- STATE: Not Clocked In --}}
    @if($state === 'not_clocked_in')
        <x-filament::button
            wire:click="clockIn"
            wire:loading.attr="disabled"
            color="success"
            size="sm"
            icon="heroicon-m-play"
        >
            <span wire:loading.remove wire:target="clockIn">Clock In</span>
            <span wire:loading wire:target="clockIn">...</span>
        </x-filament::button>
    @endif

    {{-- STATE: Working (Clocked In) --}}
    @if($state === 'working')
        <button
            wire:click="openModal"
            x-data="{
                startTime: {{ $clockedInTimestamp ?? 'null' }},
                elapsed: '',
                init() {
                    if (this.startTime) {
                        this.updateTimer();
                        setInterval(() => this.updateTimer(), 1000);
                    }
                },
                updateTimer() {
                    if (!this.startTime) return;
                    const diff = Math.floor((Date.now() / 1000) - this.startTime);
                    const hours = Math.floor(diff / 3600);
                    const mins = Math.floor((diff % 3600) / 60);
                    this.elapsed = hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
                }
            }"
            class="inline-flex items-center gap-2.5 px-4 py-2 text-sm font-medium rounded-lg transition-colors shadow-sm"
            style="background-color: #10b981; color: white;"
        >
            <x-heroicon-m-clock class="w-4 h-4 flex-shrink-0" />
            <span x-text="elapsed">0m</span>
        </button>
    @endif

    {{-- STATE: On Lunch --}}
    @if($state === 'on_lunch')
        <button
            wire:click="openModal"
            x-data="{
                returnTime: {{ $lunchReturnTimestamp ?? 'null' }},
                remaining: '',
                init() {
                    if (this.returnTime) {
                        this.updateTimer();
                        setInterval(() => this.updateTimer(), 1000);
                    }
                },
                updateTimer() {
                    if (!this.returnTime) return;
                    const diff = Math.floor(this.returnTime - (Date.now() / 1000));
                    if (diff <= 0) {
                        this.remaining = 'Back!';
                        return;
                    }
                    const mins = Math.floor(diff / 60);
                    this.remaining = `${mins}m left`;
                }
            }"
            class="inline-flex items-center gap-2.5 px-4 py-2 text-sm font-medium rounded-lg transition-colors shadow-sm"
            style="background-color: #f59e0b; color: white;"
        >
            <x-heroicon-m-pause class="w-4 h-4 flex-shrink-0" />
            <span x-text="remaining">--</span>
        </button>
    @endif

    {{-- Action Modal --}}
    @if($showModal)
        {{-- Backdrop --}}
        <div
            wire:click="closeModal"
            class="fixed inset-0 z-40"
        ></div>

        {{-- Modal --}}
        <div class="absolute right-0 top-full mt-2 w-auto min-w-[320px] bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-black/10 dark:ring-white/10 z-50 overflow-hidden">
            {{-- Header --}}
            @if($state === 'working')
                <div class="flex items-center gap-3 px-4 py-3" style="background-color: #10b981; color: white;">
                    <x-heroicon-s-clock class="w-5 h-5 flex-shrink-0" />
                    <div>
                        <div class="text-xs" style="opacity: 0.8;">Working since</div>
                        <div class="font-semibold text-base">{{ $clockedInAt }}</div>
                    </div>
                </div>
            @elseif($state === 'on_lunch')
                <div class="flex items-center gap-3 px-4 py-3" style="background-color: #f59e0b; color: white;">
                    <x-heroicon-s-pause-circle class="w-5 h-5 flex-shrink-0" />
                    <div>
                        <div class="text-xs" style="opacity: 0.8;">On break</div>
                        <div class="font-semibold text-base">{{ $lunchDuration }} min lunch</div>
                    </div>
                </div>
            @endif

            {{-- Content --}}
            <div class="p-4">
                @if($showLunchDurationPicker)
                    {{-- Lunch Duration Picker --}}
                    <div class="space-y-3">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">How long?</div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach([30 => '30 min', 45 => '45 min', 60 => '1 hour', 90 => '1.5 hrs'] as $mins => $label)
                                <button
                                    wire:click="startLunch({{ $mins }})"
                                    class="px-4 py-2.5 text-sm font-medium rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition-colors"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <button
                            wire:click="$set('showLunchDurationPicker', false)"
                            class="w-full py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            Cancel
                        </button>
                    </div>
                @else
                    {{-- Action Buttons --}}
                    <div class="space-y-2">
                        @if($state === 'working')
                            <button
                                wire:click="showLunchPicker"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <x-heroicon-m-pause class="w-5 h-5 text-amber-500" />
                                <span>Take a break</span>
                            </button>
                        @elseif($state === 'on_lunch')
                            <button
                                wire:click="returnFromLunch"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-colors"
                            >
                                <x-heroicon-m-play class="w-5 h-5" />
                                <span>Back to work</span>
                            </button>
                        @endif

                        <button
                            wire:click="clockOut"
                            wire:loading.attr="disabled"
                            class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                        >
                            <x-heroicon-m-stop class="w-5 h-5" />
                            <span wire:loading.remove wire:target="clockOut">End shift</span>
                            <span wire:loading wire:target="clockOut">Ending...</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
