<div class="kiosk-container" x-data="{ time: '{{ $this->getCurrentTime() }}' }" x-init="setInterval(() => time = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }), 1000)">
    {{-- Header --}}
    <div class="kiosk-header">
        <img src="/tcs_logo.png" alt="TCS Woodwork" style="height: 5rem; margin-bottom: 0.5rem; display: inline-block; filter: invert(1);">
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
                        wire:click="selectEmployee({{ $employee['id'] }}, '{{ addslashes($employee['name']) }}', {{ $employee['employee_id'] }})"
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

    {{-- PIN Entry Mode --}}
    @if($mode === 'pin')
        <div class="clock-panel">
            <button wire:click="backToSelect" class="back-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to employee list
            </button>

            <div class="clock-card">
                <h2 class="employee-name">{{ $selectedUserName }}</h2>
                <p class="clock-status">Enter your {{ $this->getPinLength() }}-digit PIN</p>

                {{-- PIN Display --}}
                <div class="pin-display">
                    @for($i = 0; $i < $this->getPinLength(); $i++)
                        <div class="pin-dot {{ strlen($pin) > $i ? 'pin-dot-filled' : '' }}"></div>
                    @endfor
                </div>

                {{-- Numpad --}}
                <div class="numpad">
                    @foreach([1,2,3,4,5,6,7,8,9] as $num)
                        <button wire:click="addPinDigit('{{ $num }}')" class="numpad-btn">{{ $num }}</button>
                    @endforeach
                    <button wire:click="clearPin" class="numpad-btn numpad-action">Clear</button>
                    <button wire:click="addPinDigit('0')" class="numpad-btn">0</button>
                    <button wire:click="removePinDigit" class="numpad-btn numpad-action">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/>
                        </svg>
                    </button>
                </div>

                {{-- Submit PIN --}}
                <button
                    wire:click="verifyPin"
                    class="clock-in-btn"
                    style="margin-top: 1.5rem;"
                    {{ strlen($pin) < $this->getPinLength() ? 'disabled' : '' }}
                >
                    Continue
                </button>
            </div>
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

                    {{-- ON LUNCH STATE --}}
                    @if($isOnLunch)
                        <div class="lunch-status" style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 12px; padding: 1.5rem; margin: 1rem 0; text-align: center;">
                            <p style="font-size: 1.25rem; color: #92400e; margin-bottom: 0.5rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width: 1.5rem; height: 1.5rem; display: inline; vertical-align: middle; margin-right: 0.5rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                On Lunch Break
                            </p>
                            <p style="color: #b45309; font-size: 1rem;">Started at {{ $lunchStartTime }}</p>
                        </div>

                        {{-- End Lunch Button --}}
                        <button
                            wire:click="endLunch"
                            wire:loading.attr="disabled"
                            class="clock-in-btn"
                            style="background: #059669; margin-top: 1rem;"
                        >
                            <span wire:loading.remove>End Lunch</span>
                            <span wire:loading>Processing...</span>
                        </button>

                    {{-- LUNCH TAKEN - READY TO CLOCK OUT --}}
                    @elseif($lunchTaken)
                        <div class="lunch-summary" style="background: #d1fae5; border: 2px solid #10b981; border-radius: 12px; padding: 1rem; margin: 1rem 0;">
                            <p style="color: #047857; font-size: 0.9rem; margin: 0;">
                                Lunch: {{ $lunchStartTime }} - {{ $lunchEndTime }}
                                ({{ $breakDurationMinutes }} min)
                            </p>
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

                    {{-- NO LUNCH YET - SHOW OPTIONS --}}
                    @else
                        {{-- Start Lunch Button --}}
                        <button
                            wire:click="startLunch"
                            wire:loading.attr="disabled"
                            class="lunch-btn"
                            style="background: #f59e0b; color: white; font-size: 1.25rem; padding: 1rem 2rem; border-radius: 12px; border: none; cursor: pointer; width: 100%; margin: 1rem 0; font-weight: 600;"
                        >
                            <span wire:loading.remove>Start Lunch</span>
                            <span wire:loading>Processing...</span>
                        </button>

                        <p style="text-align: center; color: #6b7280; font-size: 0.85rem; margin-bottom: 1rem;">
                            Or clock out without lunch:
                        </p>

                        {{-- Manual Break Duration Selection (fallback) --}}
                        <div class="form-section">
                            <label class="form-label">Lunch Break Duration</label>
                            <div class="break-buttons">
                                @foreach([0 => 'No lunch', 30 => '30 min', 45 => '45 min', 60 => '1 hour'] as $minutes => $label)
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
                    @endif
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
