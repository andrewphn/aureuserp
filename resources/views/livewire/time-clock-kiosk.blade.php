<div class="kiosk-container" wire:poll.30s="loadTodayAttendance">
    {{-- Header --}}
    <div class="kiosk-header">
        <img src="{{ asset('tcs_logo.png') }}" alt="TCS Woodwork" style="height: 5rem; margin-bottom: 0.5rem; display: inline-block; filter: invert(1);" onerror="this.src='{{ asset('images/logo.svg') }}'; this.onerror=null;">
        <p class="kiosk-subtitle">Time Clock</p>
        <div class="kiosk-time">{{ $this->getCurrentTime() }}</div>
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
                                        {{ ($attendance['today_hours'] ?? 0) > 0 ? $this->formatHours($attendance['today_hours']) : 'Not in' }}
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
        <div class="clock-panel"
             x-data="{
                 optimisticPin: @js($pin),
                 handleKeydown(event) {
                     // Number keys (0-9) - add digit (auto-submits when complete)
                     if (event.key >= '0' && event.key <= '9') {
                         event.preventDefault();
                         const pinLength = @js($this->getPinLength());

                         // Optimistic UI update - show digit immediately
                         if (this.optimisticPin.length < pinLength) {
                             this.optimisticPin += event.key;
                         }

                         // Then sync with server
                         @this.call('addPinDigit', event.key).then(() => {
                             this.optimisticPin = @this.get('pin') || '';
                             // Auto-submit when PIN is complete
                             if (this.optimisticPin.length >= pinLength) {
                                 @this.call('verifyPin');
                             }
                         });
                     }
                     // Backspace/Delete - remove last digit
                     else if (event.key === 'Backspace' || event.key === 'Delete') {
                         event.preventDefault();
                         @this.call('removePinDigit');
                     }
                     // Enter - submit PIN if complete (optional, auto-submits on 4th digit)
                     else if (event.key === 'Enter') {
                         event.preventDefault();
                         const pinLength = @js($this->getPinLength());
                         const currentPin = @this.get('pin') || '';
                         if (currentPin.length >= pinLength) {
                             @this.call('verifyPin');
                         }
                     }
                     // Escape - go back
                     else if (event.key === 'Escape') {
                         event.preventDefault();
                         @this.call('backToSelect');
                     }
                 }
             }"
             x-on:keydown="handleKeydown"
             x-on:click="$el.focus()"
             tabindex="0"
             style="outline: none;"
             autofocus>
            <button wire:click="backToSelect" class="back-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to employee list (Esc)
            </button>

            <div class="clock-card">
                <h2 class="employee-name">{{ $selectedUserName }}</h2>
                <p class="clock-status">Enter your {{ $this->getPinLength() }}-digit PIN</p>
                <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem; text-align: center;">
                    Use keyboard: 0-9 to enter (auto-submits), Backspace to delete, Esc to go back
                </p>

                {{-- PIN Display --}}
                <div class="pin-display"
                     x-data="{
                         optimisticPin: @js($pin),
                         pinLength: @js($this->getPinLength()),
                         init() {
                             // Sync optimistic pin when Livewire updates
                             Livewire.hook('commit', ({ component }) => {
                                 if (component === @this) {
                                     this.optimisticPin = @this.get('pin') || '';
                                 }
                             });
                         }
                     }">
                    @for($i = 0; $i < 4; $i++)
                        <div class="pin-dot"
                             x-bind:class="optimisticPin.length > {{ $i }} ? 'pin-dot-filled' : ''"></div>
                    @endfor
                </div>

                {{-- Numpad --}}
                <div class="numpad"
                     x-data="{
                         optimisticPin: @js($pin),
                         pinLength: @js($this->getPinLength()),
                         handlePinDigit(digit) {
                             // Optimistic UI update - show digit immediately
                             if (this.optimisticPin.length < this.pinLength) {
                                 this.optimisticPin += digit;
                             }

                             // Then sync with server
                             @this.call('addPinDigit', digit).then(() => {
                                 this.optimisticPin = @this.get('pin') || '';
                                 // Auto-submit when PIN is complete
                                 if (this.optimisticPin.length >= this.pinLength) {
                                     @this.call('verifyPin');
                                 }
                             });
                         }
                     }">
                    @foreach([1,2,3,4,5,6,7,8,9] as $num)
                        <button
                            x-on:click="handlePinDigit('{{ $num }}')"
                            class="numpad-btn"
                            style="transition: transform 0.1s, background-color 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.95)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'">
                            {{ $num }}
                        </button>
                    @endforeach
                    <button wire:click="clearPin"
                            wire:loading.attr="disabled"
                            class="numpad-btn numpad-action"
                            style="transition: transform 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.95)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'">
                        <span wire:loading.remove wire:target="clearPin">Clear</span>
                        <span wire:loading wire:target="clearPin">...</span>
                    </button>
                    <button x-on:click="handlePinDigit('0')"
                            class="numpad-btn"
                            style="transition: transform 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.95)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'">
                        0
                    </button>
                    <button wire:click="removePinDigit"
                            wire:loading.attr="disabled"
                            class="numpad-btn numpad-action"
                            style="transition: transform 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.95)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'">
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
                    Continue (Enter)
                </button>
            </div>
        </div>
    @endif

    {{-- Clock In Confirmation Mode --}}
    @if($mode === 'confirmed')
        <div class="clock-panel"
             x-data="{
                 clockInTime: @js($clockedInAt),
                 startTime: new Date(),
                 elapsedSeconds: 0,
                 remainingSeconds: 5,
                 timer: null,
                 timeoutTimer: null,
                 countdownTimer: null,
                 hasReturned: false,
                 init() {
                     // Start running timer
                     this.timer = setInterval(() => {
                         const now = new Date();
                         this.elapsedSeconds = Math.floor((now - this.startTime) / 1000);
                     }, 1000);

                     // Countdown timer
                     this.countdownTimer = setInterval(() => {
                         this.remainingSeconds--;
                         if (this.remainingSeconds <= 0) {
                             clearInterval(this.countdownTimer);
                         }
                     }, 1000);

                     // Auto-return to login page after 5 seconds
                     this.timeoutTimer = setTimeout(() => {
                         // Prevent multiple calls
                         if (this.hasReturned) return;
                         this.hasReturned = true;

                         // Clear all timers
                         if (this.timer) clearInterval(this.timer);
                         if (this.countdownTimer) clearInterval(this.countdownTimer);
                         if (this.timeoutTimer) clearTimeout(this.timeoutTimer);

                         // Return to employee selection (login page)
                         @this.call('backToSelect').catch(() => {
                             // If call fails, try redirecting anyway
                             window.location.reload();
                         });
                     }, 5000);
                 },
                 destroy() {
                     if (this.timer) clearInterval(this.timer);
                     if (this.countdownTimer) clearInterval(this.countdownTimer);
                     if (this.timeoutTimer) clearTimeout(this.timeoutTimer);
                 },
                 formatTime(seconds) {
                     const mins = Math.floor(seconds / 60);
                     const secs = seconds % 60;
                     return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                 }
             }"
             x-init="init()"
             x-on:destroyed="destroy()">
            <div class="clock-card" style="text-align: center; padding: 3rem 2rem;">
                <div style="font-size: 4rem; font-weight: 700; color: #10b981; margin-bottom: 1rem;">
                    ✓
                </div>
                <h2 class="employee-name" style="margin-bottom: 1rem;">{{ $selectedUserName }}</h2>
                <p class="clock-status" style="font-size: 1.5rem; margin-bottom: 2rem;">
                    Clocked In
                </p>
                <div style="background: #f3f4f6; border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
                    <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Time</p>
                    <p style="font-size: 2.5rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem;">
                        {{ $clockedInAt }}
                    </p>
                    <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Duration</p>
                    <p style="font-size: 2rem; font-weight: 600; color: #059669;">
                        <span x-text="formatTime(elapsedSeconds)"></span>
                    </p>
                </div>
                <p style="color: #6b7280; font-size: 0.85rem;">
                    Returning to main screen in <span x-text="Math.max(0, remainingSeconds)"></span> seconds...
                </p>
            </div>
        </div>
    @endif

    {{-- Clock In/Out Mode --}}
    @if($mode === 'clock')
        <div class="clock-panel"
             x-data="{
                 isClockedIn: @js($isClockedIn),
                 isOnLunch: @js($isOnLunch),
                 lunchTaken: @js($lunchTaken),
                 handleKeydown(event) {
                     // Escape - go back
                     if (event.key === 'Escape') {
                         event.preventDefault();
                         @this.call('backToSelect');
                     }
                     // Clock In (I key) - only if not clocked in
                     else if (event.key === 'i' || event.key === 'I') {
                         if (!this.isClockedIn) {
                             event.preventDefault();
                             @this.call('clockIn');
                         }
                     }
                     // Clock Out (O key) - only if clocked in
                     else if (event.key === 'o' || event.key === 'O') {
                         if (this.isClockedIn && !this.isOnLunch) {
                             event.preventDefault();
                             @this.call('clockOut');
                         }
                     }
                     // Start Lunch (L key) - only if clocked in, not on lunch, and before 4 PM
                     else if (event.key === 'l' || event.key === 'L') {
                         if (this.isClockedIn && !this.isOnLunch && !this.lunchTaken) {
                             event.preventDefault();
                             // Check time before starting lunch
                             const currentHour = new Date().getHours();
                             if (currentHour < 16) {
                                 @this.call('startLunch');
                             }
                         }
                     }
                     // End Lunch (E key) - only if on lunch
                     else if (event.key === 'e' || event.key === 'E') {
                         if (this.isOnLunch) {
                             event.preventDefault();
                             @this.call('endLunch');
                         }
                     }
                 },
                 init() {
                     // Update state when Livewire updates
                     Livewire.hook('commit', ({ component }) => {
                         if (component === @this) {
                             this.isClockedIn = @this.get('isClockedIn') || false;
                             this.isOnLunch = @this.get('isOnLunch') || false;
                             this.lunchTaken = @this.get('lunchTaken') || false;
                         }
                     });
                 }
             }"
             x-on:keydown="handleKeydown"
             x-on:click="$el.focus()"
             tabindex="0"
             style="outline: none;"
             autofocus>
            <button wire:click="backToSelect" class="back-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to employee list (Esc)
            </button>

            <div class="clock-card">
                <h2 class="employee-name">{{ $selectedUserName }}</h2>
                <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem; margin-bottom: 1rem; text-align: center;">
                    Keyboard shortcuts: I=Clock In, O=Clock Out, L=Start Lunch, E=End Lunch, Esc=Back<br>
                    <span style="font-size: 0.7rem;">Click the time to start/end lunch</span>
                </p>

                @if($isClockedIn)
                    <p class="clock-status">
                        Clocked in at
                        @if(!$isOnLunch && !$lunchTaken && $this->canTakeLunch())
                            <span class="time"
                                  wire:click="startLunch"
                                  wire:loading.attr="disabled"
                                  wire:target="startLunch"
                                  style="cursor: pointer; text-decoration: underline; color: #f59e0b; font-weight: 600; transition: opacity 0.1s;"
                                  x-on:mousedown="$el.style.opacity = '0.7'"
                                  x-on:mouseup="$el.style.opacity = '1'"
                                  x-on:mouseleave="$el.style.opacity = '1'"
                                  title="Click to start lunch break">
                                {{ $clockedInAt }}
                            </span>
                        @else
                            <span class="time">{{ $clockedInAt }}</span>
                        @endif
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
                            <p style="color: #b45309; font-size: 1rem;">
                                Started at
                                <span wire:click="endLunch"
                                      wire:loading.attr="disabled"
                                      wire:target="endLunch"
                                      style="cursor: pointer; text-decoration: underline; color: #059669; font-weight: 600; transition: opacity 0.1s;"
                                      x-on:mousedown="$el.style.opacity = '0.7'"
                                      x-on:mouseup="$el.style.opacity = '1'"
                                      x-on:mouseleave="$el.style.opacity = '1'"
                                      title="Click to end lunch break">
                                    {{ $lunchStartTime }}
                                </span>
                            </p>
                        </div>

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
                            wire:target="clockOut"
                            class="clock-out-btn"
                            style="transition: transform 0.1s, opacity 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.98)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'"
                        >
                            <span wire:loading.remove wire:target="clockOut">Clock Out (O)</span>
                            <span wire:loading wire:target="clockOut">Processing...</span>
                        </button>

                    {{-- NO LUNCH YET - SHOW OPTIONS --}}
                    @else
                        @if($this->canTakeLunch())
                            <p style="text-align: center; color: #6b7280; font-size: 0.9rem; margin: 1rem 0;">
                                Click the time above to start lunch break
                            </p>
                        @else
                            <p style="text-align: center; color: #6b7280; font-size: 0.9rem; margin: 1rem 0;">
                                Lunch break not available after 4 PM
                            </p>
                        @endif

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
                            wire:target="clockOut"
                            class="clock-out-btn"
                            style="transition: transform 0.1s, opacity 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.98)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'"
                        >
                            <span wire:loading.remove wire:target="clockOut">Clock Out (O)</span>
                            <span wire:loading wire:target="clockOut">Processing...</span>
                        </button>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- Footer --}}
    <div class="kiosk-footer">
        TCS Woodwork Time Clock &bull; Mon-Thu 8am-5pm
    </div>
</div>
