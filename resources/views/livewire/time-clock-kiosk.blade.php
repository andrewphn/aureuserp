<div class="min-h-screen bg-gray-900 text-white p-4 sm:p-8" x-data="{ time: '{{ $this->getCurrentTime() }}' }" x-init="setInterval(() => time = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }), 1000)">
    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl sm:text-5xl font-bold text-amber-400">TCS Woodwork</h1>
        <p class="text-xl sm:text-2xl text-gray-400 mt-2">Time Clock</p>
        <div class="mt-4">
            <span class="text-5xl sm:text-6xl font-mono font-bold" x-text="time"></span>
            <p class="text-lg text-gray-500 mt-2">{{ $this->getCurrentDate() }}</p>
        </div>
    </div>

    {{-- Status Message --}}
    @if($statusMessage)
        <div class="max-w-2xl mx-auto mb-6">
            <div @class([
                'p-4 rounded-lg text-center text-lg font-medium',
                'bg-green-800 text-green-100' => $statusType === 'success',
                'bg-red-800 text-red-100' => $statusType === 'error',
                'bg-blue-800 text-blue-100' => $statusType === 'info',
            ])>
                {{ $statusMessage }}
            </div>
        </div>
    @endif

    {{-- Employee Selection Mode --}}
    @if($mode === 'select')
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-semibold text-center mb-6 text-gray-300">Select Your Name</h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach($employees as $employee)
                    <button
                        wire:click="selectEmployee({{ $employee['id'] }}, '{{ addslashes($employee['name']) }}')"
                        class="p-6 bg-gray-800 hover:bg-gray-700 active:bg-gray-600 rounded-xl transition-colors text-xl font-medium text-center border border-gray-700 hover:border-amber-500"
                    >
                        {{ $employee['name'] }}
                    </button>
                @endforeach
            </div>

            {{-- Today's Attendance Summary --}}
            @if(count($todayAttendance) > 0)
                <div class="mt-12">
                    <h3 class="text-xl font-semibold text-center mb-4 text-gray-400">Today's Attendance</h3>
                    <div class="bg-gray-800 rounded-xl p-4 max-w-xl mx-auto">
                        <div class="space-y-2">
                            @foreach($todayAttendance as $attendance)
                                <div class="flex justify-between items-center py-2 border-b border-gray-700 last:border-0">
                                    <span>{{ $attendance['name'] }}</span>
                                    <span @class([
                                        'px-3 py-1 rounded-full text-sm font-medium',
                                        'bg-green-700' => $attendance['is_clocked_in'],
                                        'bg-gray-600' => !$attendance['is_clocked_in'],
                                    ])>
                                        @if($attendance['is_clocked_in'])
                                            In at {{ $attendance['clock_in_time'] }}
                                        @else
                                            {{ ($attendance['today_hours'] ?? 0) > 0 ? sprintf('%.1fh', $attendance['today_hours']) : 'Not in' }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Clock In/Out Mode --}}
    @if($mode === 'clock')
        <div class="max-w-lg mx-auto">
            <button
                wire:click="backToSelect"
                class="mb-6 text-gray-400 hover:text-white flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to employee list
            </button>

            <div class="bg-gray-800 rounded-2xl p-6 sm:p-8">
                <h2 class="text-3xl font-bold text-center mb-2">{{ $selectedUserName }}</h2>

                @if($isClockedIn)
                    <p class="text-center text-gray-400 mb-8">
                        Clocked in at <span class="text-green-400 font-semibold">{{ $clockedInAt }}</span>
                    </p>

                    {{-- Break Duration Selection --}}
                    <div class="mb-6">
                        <label class="block text-lg text-gray-300 mb-3">Lunch Break Duration</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([30 => '30 min', 45 => '45 min', 60 => '1 hour'] as $minutes => $label)
                                <button
                                    wire:click="setBreakDuration({{ $minutes }})"
                                    @class([
                                        'p-4 rounded-lg text-lg font-medium transition-colors',
                                        'bg-amber-600 text-white' => $breakDurationMinutes === $minutes,
                                        'bg-gray-700 text-gray-300 hover:bg-gray-600' => $breakDurationMinutes !== $minutes,
                                    ])
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Project Selection (Optional) --}}
                    @if(count($projects) > 0)
                        <div class="mb-6">
                            <label class="block text-lg text-gray-300 mb-3">Project (Optional)</label>
                            <select
                                wire:model="selectedProjectId"
                                class="w-full p-4 bg-gray-700 border border-gray-600 rounded-lg text-lg text-white"
                            >
                                <option value="">No project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Clock Out Button --}}
                    <button
                        wire:click="clockOut"
                        wire:loading.attr="disabled"
                        class="w-full p-6 bg-red-600 hover:bg-red-700 active:bg-red-800 disabled:opacity-50 rounded-xl text-2xl font-bold transition-colors"
                    >
                        <span wire:loading.remove>Clock Out</span>
                        <span wire:loading>Processing...</span>
                    </button>
                @else
                    <p class="text-center text-gray-400 mb-8">Not currently clocked in</p>

                    {{-- Clock In Button --}}
                    <button
                        wire:click="clockIn"
                        wire:loading.attr="disabled"
                        class="w-full p-6 bg-green-600 hover:bg-green-700 active:bg-green-800 disabled:opacity-50 rounded-xl text-2xl font-bold transition-colors"
                    >
                        <span wire:loading.remove>Clock In</span>
                        <span wire:loading>Processing...</span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Footer --}}
    <div class="fixed bottom-4 left-0 right-0 text-center text-gray-600 text-sm">
        TCS Woodwork Time Clock &bull; Mon-Thu 8am-5pm
    </div>
</div>
