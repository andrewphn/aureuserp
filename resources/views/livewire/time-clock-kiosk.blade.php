<div class="kiosk-container" x-data="{ time: '{{ $this->getCurrentTime() }}' }" x-init="setInterval(() => time = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }), 1000)">
    {{-- Header --}}
    <div class="kiosk-header">
        <h1 class="kiosk-title">TCS Woodwork</h1>
        <p class="kiosk-subtitle">Time Clock</p>
        <div class="kiosk-time" x-text="time"></div>
        <p class="kiosk-date">{{ $this->getCurrentDate() }}</p>
    </div>

    {{-- Status Message --}}
    @if($statusMessage)
        <div class="status-message {{ $statusType === 'success' ? 'status-success' : ($statusType === 'error' ? 'status-error' : 'status-info') }}">
            {{ $statusMessage }}
        </div>
    @endif

    {{-- Employee Selection Mode --}}
    @if($mode === 'select')
        <div class="employee-grid">
            <h2 class="section-title">Select Your Name</h2>

            <div class="employee-buttons">
                @foreach($employees as $employee)
                    <button
                        wire:click="selectEmployee({{ $employee['id'] }}, '{{ addslashes($employee['name']) }}')"
                        class="employee-btn"
                    >
                        {{ $employee['name'] }}
                    </button>
                @endforeach
            </div>

            {{-- Today's Attendance Summary --}}
            @if(count($todayAttendance) > 0)
                <div class="attendance-section">
                    <h3 class="attendance-title">Today's Attendance</h3>
                    <div class="attendance-list">
                        @foreach($todayAttendance as $attendance)
                            <div class="attendance-item">
                                <span>{{ $attendance['name'] }}</span>
                                <span class="attendance-status {{ $attendance['is_clocked_in'] ? 'status-in' : 'status-out' }}">
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
            @endif
        </div>
    @endif

    {{-- Clock In/Out Mode --}}
    @if($mode === 'clock')
        <div class="clock-panel">
            <button wire:click="backToSelect" class="back-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to employee list
            </button>

            <div class="clock-card">
                <h2 class="employee-name">{{ $selectedUserName }}</h2>

                @if($isClockedIn)
                    <p class="clock-status">
                        Clocked in at <span class="time">{{ $clockedInAt }}</span>
                    </p>

                    {{-- Break Duration Selection --}}
                    <div class="form-section">
                        <label class="form-label">Lunch Break Duration</label>
                        <div class="break-buttons">
                            @foreach([30 => '30 min', 45 => '45 min', 60 => '1 hour'] as $minutes => $label)
                                <button
                                    wire:click="setBreakDuration({{ $minutes }})"
                                    class="break-btn {{ $breakDurationMinutes === $minutes ? 'break-btn-active' : 'break-btn-inactive' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Project Selection (Optional) --}}
                    @if(count($projects) > 0)
                        <div class="form-section">
                            <label class="form-label">Project (Optional)</label>
                            <select wire:model="selectedProjectId" class="project-select">
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
                        class="clock-out-btn"
                    >
                        <span wire:loading.remove>Clock Out</span>
                        <span wire:loading>Processing...</span>
                    </button>
                @else
                    <p class="clock-status">Not currently clocked in</p>

                    {{-- Clock In Button --}}
                    <button
                        wire:click="clockIn"
                        wire:loading.attr="disabled"
                        class="clock-in-btn"
                    >
                        <span wire:loading.remove>Clock In</span>
                        <span wire:loading>Processing...</span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Footer --}}
    <div class="kiosk-footer">
        TCS Woodwork Time Clock &bull; Mon-Thu 8am-5pm
    </div>
</div>
