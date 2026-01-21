<div class="kiosk-container"
     @if($mode !== 'confirmed' && $mode !== 'select' && $mode !== 'pin' && $mode !== 'clockout-lunch' && $mode !== 'summary')
     wire:poll.30s="loadTodayAttendance"
     @endif>

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

    {{-- Clock Out Lunch Duration Selection Mode --}}
    @if($mode === 'clockout-lunch')
        <div class="clock-panel" wire:key="clockout-lunch-panel"
             x-data="{
                 customMinutes: '',
                 showCustom: false,
                 submitCustom() {
                     const minutes = parseInt(this.customMinutes);
                     if (minutes >= 1 && minutes <= 480) {
                         @this.call('setLunchAndClockOut', minutes);
                     } else {
                         alert('Please enter a duration between 1 and 480 minutes');
                     }
                 }
             }"
             x-on:keydown.escape="@this.call('cancelClockOutLunch')">
            <button wire:click="cancelClockOutLunch" class="back-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back (Esc)
            </button>

            <div class="clock-card">
                <h2 class="employee-name">{{ $selectedUserName }}</h2>
                <p class="clock-status">No lunch was logged. Select lunch duration:</p>

                {{-- Preset Duration Buttons --}}
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 2rem 0;">
                    <button
                        wire:click="setLunchAndClockOut(30)"
                        wire:loading.attr="disabled"
                        wire:target="setLunchAndClockOut"
                        class="clock-in-btn"
                        style="padding: 1.5rem; font-size: 1.25rem; font-weight: 600;">
                        <span wire:loading.remove wire:target="setLunchAndClockOut">30 min</span>
                        <span wire:loading wire:target="setLunchAndClockOut">...</span>
                    </button>
                    <button
                        wire:click="setLunchAndClockOut(45)"
                        wire:loading.attr="disabled"
                        wire:target="setLunchAndClockOut"
                        class="clock-in-btn"
                        style="padding: 1.5rem; font-size: 1.25rem; font-weight: 600;">
                        <span wire:loading.remove wire:target="setLunchAndClockOut">45 min</span>
                        <span wire:loading wire:target="setLunchAndClockOut">...</span>
                    </button>
                    <button
                        wire:click="setLunchAndClockOut(60)"
                        wire:loading.attr="disabled"
                        wire:target="setLunchAndClockOut"
                        class="clock-in-btn"
                        style="padding: 1.5rem; font-size: 1.25rem; font-weight: 600;">
                        <span wire:loading.remove wire:target="setLunchAndClockOut">1 hour</span>
                        <span wire:loading wire:target="setLunchAndClockOut">...</span>
                    </button>
                </div>

                {{-- Custom Duration Option --}}
                <div style="margin-top: 1.5rem;">
                    <button
                        x-on:click="showCustom = !showCustom; if (!showCustom) customMinutes = '';"
                        class="clock-in-btn"
                        style="width: 100%; background: #f3f4f6; color: #111827; border: 2px solid #d1d5db;">
                        <span x-show="!showCustom">Custom Duration</span>
                        <span x-show="showCustom">Cancel Custom</span>
                    </button>

                    <div x-show="showCustom"
                         x-transition
                         style="margin-top: 1rem; padding: 1.5rem; background: #f9fafb; border-radius: 12px; border: 2px solid #e5e7eb;">
                        <label style="display: block; color: #374151; font-size: 0.9rem; margin-bottom: 0.5rem; font-weight: 600;">
                            Enter duration (minutes):
                        </label>
                        <input
                            type="number"
                            x-model="customMinutes"
                            x-on:keydown.enter="submitCustom()"
                            min="1"
                            max="480"
                            placeholder="Enter minutes (1-480)"
                            style="width: 100%; padding: 1rem; font-size: 1.25rem; border: 2px solid #d1d5db; border-radius: 8px; text-align: center; margin-bottom: 1rem;"
                            autofocus>
                        <button
                            x-on:click="submitCustom()"
                            x-bind:disabled="!customMinutes || customMinutes < 1 || customMinutes > 480"
                            wire:loading.attr="disabled"
                            wire:target="setLunchAndClockOut"
                            class="clock-in-btn"
                            style="width: 100%;">
                            <span wire:loading.remove wire:target="setLunchAndClockOut">Submit</span>
                            <span wire:loading wire:target="setLunchAndClockOut">Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- PIN Entry Mode --}}
    @if($mode === 'pin')
        <div class="clock-panel"
             x-data="{
                 localPin: '',
                 pinLength: @js($this->getPinLength()),
                 handleKeydown(event) {
                     // Number keys (0-9) - add digit locally (no server call until complete)
                     if (event.key >= '0' && event.key <= '9') {
                         event.preventDefault();

                         // Add to local PIN (optimistic UI - no server call)
                         if (this.localPin.length < this.pinLength) {
                             this.localPin += event.key;

                             // When PIN is complete, send all digits at once
                             if (this.localPin.length >= this.pinLength) {
                                 // Set all digits at once on server
                                 @this.set('pin', this.localPin);
                                 // Then verify immediately
                                 @this.call('verifyPin');
                             }
                         }
                     }
                     // Backspace/Delete - remove last digit
                     else if (event.key === 'Backspace' || event.key === 'Delete') {
                         event.preventDefault();
                         this.localPin = this.localPin.slice(0, -1);
                         @this.set('pin', this.localPin);
                     }
                     // Enter - submit PIN if complete
                     else if (event.key === 'Enter') {
                         event.preventDefault();
                         if (this.localPin.length >= this.pinLength) {
                             @this.set('pin', this.localPin);
                             @this.call('verifyPin');
                         }
                     }
                     // Escape - go back
                     else if (event.key === 'Escape') {
                         event.preventDefault();
                         this.localPin = '';
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
                <div class="pin-display">
                    @for($i = 0; $i < 4; $i++)
                        <div class="pin-dot"
                             x-bind:class="localPin.length > {{ $i }} ? 'pin-dot-filled' : ''"></div>
                    @endfor
                </div>

                {{-- Numpad --}}
                <div class="numpad">
                    @foreach([1,2,3,4,5,6,7,8,9] as $num)
                        <button
                            x-on:click="
                                if (localPin.length < pinLength) {
                                    localPin += '{{ $num }}';
                                    if (localPin.length >= pinLength) {
                                        @this.set('pin', localPin);
                                        @this.call('verifyPin');
                                    }
                                }
                            "
                            class="numpad-btn"
                            style="transition: transform 0.1s, background-color 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.95)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'">
                            {{ $num }}
                        </button>
                    @endforeach
                    <button x-on:click="localPin = ''; @this.set('pin', '');"
                            class="numpad-btn numpad-action"
                            style="transition: transform 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.95)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'">
                        Clear
                    </button>
                    <button x-on:click="
                                if (localPin.length < pinLength) {
                                    localPin += '0';
                                    if (localPin.length >= pinLength) {
                                        @this.set('pin', localPin);
                                        @this.call('verifyPin');
                                    }
                                }
                            "
                            class="numpad-btn"
                            style="transition: transform 0.1s;"
                            x-on:mousedown="$el.style.transform = 'scale(0.95)'"
                            x-on:mouseup="$el.style.transform = 'scale(1)'"
                            x-on:mouseleave="$el.style.transform = 'scale(1)'">
                        0
                    </button>
                    <button x-on:click="localPin = localPin.slice(0, -1); @this.set('pin', localPin);"
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
                    x-on:click="
                        if (localPin.length >= pinLength) {
                            @this.set('pin', localPin);
                            @this.call('verifyPin');
                        }
                    "
                    class="clock-in-btn"
                    style="margin-top: 1.5rem;"
                    x-bind:disabled="localPin.length < pinLength"
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
                 isTransitioning: false,
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
                        if (this.hasReturned || this.isTransitioning) return;
                        this.hasReturned = true;
                        this.isTransitioning = true;

                        // Clear all timers
                        if (this.timer) clearInterval(this.timer);
                        if (this.countdownTimer) clearInterval(this.countdownTimer);
                        if (this.timeoutTimer) clearTimeout(this.timeoutTimer);

                        // Stop ALL Livewire activity before reload to prevent 500 errors
                        // This prevents any pending requests from causing checksum corruption
                        try {
                            if (window.Livewire) {
                                // Get the current component instance
                                const component = @this;
                                if (component) {
                                    // Stop polling for this component
                                    component.stopPolling();
                                }

                                // Stop all Livewire polling globally
                                if (window.Livewire.stopPolling) {
                                    window.Livewire.stopPolling();
                                }

                                // Cancel any pending XHR requests
                                if (window.Livewire.all && window.Livewire.all().length > 0) {
                                    window.Livewire.all().forEach(comp => {
                                        if (comp && comp.$wire && comp.$wire.__instance) {
                                            // Cancel pending requests
                                            const instance = comp.$wire.__instance;
                                            if (instance.requestQueue) {
                                                instance.requestQueue = [];
                                            }
                                            if (instance.pendingRequest) {
                                                instance.pendingRequest.abort();
                                                instance.pendingRequest = null;
                                            }
                                        }
                                    });
                                }
                            }
                        } catch (e) {
                            console.warn('Error stopping Livewire:', e);
                        }

                        // Small delay to ensure Livewire cleanup completes, then reload
                        setTimeout(() => {
                            // Reload the page to avoid Livewire checksum corruption
                            // This is more reliable than calling backToSelect() after a timeout
                            window.location.href = window.location.pathname;
                        }, 150);
                    }, 5000);
                 },
                 destroy() {
                     this.isTransitioning = true;
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
        <div class="clock-panel" wire:key="clock-panel"
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
                             @this.call('showClockOut');
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
                    Keyboard shortcuts: I=Clock In, O=Clock Out, Esc=Back
                </p>

                @if($isClockedIn)
                    {{-- Clocked In Display --}}
                    <div style="background: #f3f4f6; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; text-align: center;">
                        {{-- Clocked In Time --}}
                        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Clocked In</p>
                        <p style="font-size: 2.5rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem;">
                            {{ $clockedInAt }}
                        </p>

                        {{-- Counting Duration --}}
                        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">
                            @if($isOnLunch)
                                Lunch Duration
                            @else
                                Duration
                            @endif
                        </p>
                        <p style="font-size: 2rem; font-weight: 600; color: #059669;"
                           x-data="{
                               clockInTime: @js($clockInTimestamp),
                               lunchStartTime: @js($lunchStartTimestamp),
                               isOnLunch: @js($isOnLunch),
                               elapsedSeconds: 0,
                               timer: null,
                               init() {
                                   const updateTimer = () => {
                                       if (this.isOnLunch && this.lunchStartTime) {
                                           // Show lunch duration (time since lunch started)
                                           const startTime = new Date(this.lunchStartTime);
                                           const now = new Date();
                                           this.elapsedSeconds = Math.floor((now - startTime) / 1000);
                                       } else if (this.clockInTime) {
                                           // Show overall duration (time since clock-in)
                                           const startTime = new Date(this.clockInTime);
                                           const now = new Date();
                                           this.elapsedSeconds = Math.floor((now - startTime) / 1000);
                                       }
                                   };

                                   updateTimer();
                                   this.timer = setInterval(updateTimer, 1000);

                                   // Update when lunch status changes
                                   Livewire.hook('commit', ({ component }) => {
                                       if (component === @this) {
                                           this.isOnLunch = @this.get('isOnLunch') || false;
                                           this.lunchStartTime = @this.get('lunchStartTimestamp') || null;
                                       }
                                   });
                               },
                               destroy() {
                                   if (this.timer) clearInterval(this.timer);
                               },
                               formatTime(seconds) {
                                   const hours = Math.floor(seconds / 3600);
                                   const mins = Math.floor((seconds % 3600) / 60);
                                   const secs = seconds % 60;
                                   if (hours > 0) {
                                       return `${hours}h ${mins}m ${secs}s`;
                                   }
                                   return `${mins}m ${secs}s`;
                               }
                           }"
                           x-init="init()"
                           x-on:destroyed="destroy()">
                            <span x-text="formatTime(elapsedSeconds)">0m 0s</span>
                        </p>

                        {{-- Lunch Status --}}
                        @if($isOnLunch)
                            <p style="color: #6b7280; font-size: 0.9rem; margin-top: 1rem; margin-bottom: 0.5rem;">Lunch</p>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #f59e0b;"
                               x-data="{
                                   lunchStartTimestamp: @js($lunchStartTimestamp),
                                   scheduledMinutes: @js($scheduledLunchDurationMinutes ?? 60),
                                   lunchDurationSeconds: 0,
                                   remainingMinutes: 0,
                                   timer: null,
                                   isOnLunch: @js($isOnLunch),
                               init() {
                                   // Always initialize if on lunch, even if timestamp isn't set yet
                                   if (this.isOnLunch) {
                                       const updateRemaining = () => {
                                           // Check if still on lunch (user might have manually ended it)
                                           const currentOnLunch = @this.get('isOnLunch');
                                           if (!currentOnLunch) {
                                               // User manually ended lunch, stop timer
                                               if (this.timer) {
                                                   clearInterval(this.timer);
                                                   this.timer = null;
                                               }
                                               return;
                                           }

                                           // Get fresh timestamp from Livewire if not set
                                           if (!this.lunchStartTimestamp) {
                                               this.lunchStartTimestamp = @this.get('lunchStartTimestamp');
                                           }

                                           if (this.lunchStartTimestamp) {
                                               const startTime = new Date(this.lunchStartTimestamp);
                                               const now = new Date();
                                               // Calculate lunch duration (elapsed time)
                                               this.lunchDurationSeconds = Math.floor((now - startTime) / 1000);

                                               // Calculate remaining time until auto-end (if scheduled minutes set)
                                               if (this.scheduledMinutes) {
                                                   const elapsedMinutes = Math.floor((now - startTime) / 60000);
                                                   this.remainingMinutes = Math.max(0, this.scheduledMinutes - elapsedMinutes);

                                                   // Auto-end lunch when time is up (only if still on lunch)
                                                   if (this.remainingMinutes <= 0 && this.timer && currentOnLunch) {
                                                       clearInterval(this.timer);
                                                       this.timer = null;
                                                       @this.call('endLunch');
                                                   }
                                               }
                                           }
                                       };
                                       updateRemaining();
                                       this.timer = setInterval(updateRemaining, 1000); // Update every second
                                   }
                               },
                                   destroy() {
                                       if (this.timer) clearInterval(this.timer);
                                   },
                                   formatTime(seconds) {
                                       const hours = Math.floor(seconds / 3600);
                                       const mins = Math.floor((seconds % 3600) / 60);
                                       const secs = seconds % 60;
                                       if (hours > 0) {
                                           return `${hours}h ${mins}m ${secs}s`;
                                       }
                                       return `${mins}m ${secs}s`;
                                   }
                               }"
                               x-init="init()"
                               x-on:destroyed="destroy()">
                                On Break<br>
                                <span style="font-size: 1rem; color: #92400e;">Started: {{ $lunchStartTime ?? 'N/A' }}</span><br>
                                <span style="font-size: 1.1rem; color: #b45309; font-weight: 600; margin-top: 0.5rem; display: block;" x-show="lunchDurationSeconds > 0">
                                    Duration: <span x-text="formatTime(lunchDurationSeconds)"></span>
                                </span>
                                <span style="font-size: 0.9rem; color: #b45309; margin-top: 0.25rem; display: block;" x-show="scheduledMinutes && remainingMinutes > 0">
                                    Auto-return in <span x-text="remainingMinutes"></span> min
                                </span>
                                <span style="font-size: 0.9rem; color: #059669; margin-top: 0.25rem; display: block;" x-show="scheduledMinutes && remainingMinutes <= 0 && timer !== null">
                                    Returning to work...
                                </span>
                            </p>
                        @elseif($lunchTaken)
                            <p style="color: #6b7280; font-size: 0.9rem; margin-top: 1rem; margin-bottom: 0.5rem;">Lunch</p>
                            <p style="font-size: 1.25rem; font-weight: 600; color: #10b981;">
                                {{ $lunchStartTime }} - {{ $lunchEndTime }}<br>
                                <span style="font-size: 0.9rem; color: #047857;">({{ $breakDurationMinutes }} min)</span>
                            </p>
                        @else
                            <p style="color: #6b7280; font-size: 0.9rem; margin-top: 1rem;">No lunch taken</p>
                        @endif
                    </div>

                    {{-- Project Selection (Optional) --}}
                    @if(count($projects) > 0)
                        <div class="form-section" style="margin-top: 1.5rem;">
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
                        wire:click="showClockOut"
                        wire:loading.attr="disabled"
                        wire:target="showClockOut"
                        class="clock-out-btn"
                        style="transition: transform 0.1s, opacity 0.1s; margin-top: 1rem; width: 100%;"
                        x-on:mousedown="$el.style.transform = 'scale(0.98)'"
                        x-on:mouseup="$el.style.transform = 'scale(1)'"
                        x-on:mouseleave="$el.style.transform = 'scale(1)'"
                    >
                        <span wire:loading.remove wire:target="showClockOut">Clock Out (O)</span>
                        <span wire:loading wire:target="showClockOut">Processing...</span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Clock Out Summary Mode --}}
    @if($mode === 'summary')
        <div class="clock-panel" wire:key="summary-panel"
             x-data="{
                 timeoutTimer: null,
                 hasReturned: false,
                 init() {
                     // Auto-return to select screen after 5 seconds
                     this.timeoutTimer = setTimeout(() => {
                         if (this.hasReturned) return;
                         this.hasReturned = true;
                         @this.call('backToSelectFromSummary');
                     }, 5000);
                 },
                 destroy() {
                     if (this.timeoutTimer) clearTimeout(this.timeoutTimer);
                 }
             }"
             x-init="init()"
             x-on:destroyed="destroy()">
            <div class="clock-card">
                <h2 class="employee-name">{{ $selectedUserName }}</h2>
                <p class="clock-status" style="color: #10b981; font-size: 1.5rem; font-weight: 600; margin-bottom: 2rem;">
                    ✓ Clocked Out
                </p>

                {{-- Summary Details --}}
                <div style="background: #f9fafb; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; border: 2px solid #e5e7eb;">
                    <div style="margin-bottom: 1.5rem;">
                        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Clock In</p>
                        <p style="font-size: 1.5rem; font-weight: 600; color: #111827;">{{ $summaryClockInTime ?? 'N/A' }}</p>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Clock Out</p>
                        <p style="font-size: 1.5rem; font-weight: 600; color: #111827;">{{ $summaryClockOutTime ?? 'N/A' }}</p>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Hours Worked</p>
                        <p style="font-size: 2rem; font-weight: 700; color: #059669;">
                            {{ $summaryHoursWorked ? $this->formatHours($summaryHoursWorked) : '0h 0m' }}
                        </p>
                    </div>

                    @if($summaryLunchMinutes)
                        <div style="margin-bottom: 1.5rem;">
                            <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Lunch</p>
                            <p style="font-size: 1.25rem; font-weight: 600; color: #10b981;">{{ $summaryLunchMinutes }} minutes</p>
                        </div>
                    @endif

                    @if($summaryProjectName)
                        <div>
                            <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Project</p>
                            <p style="font-size: 1.25rem; font-weight: 600; color: #3b82f6;">{{ $summaryProjectName }}</p>
                        </div>
                    @endif
                </div>

                <p style="color: #6b7280; font-size: 0.9rem; text-align: center; margin-top: 1rem;"
                   x-data="{ countdown: 5 }"
                   x-init="
                       const interval = setInterval(() => {
                           countdown--;
                           if (countdown <= 0) {
                               clearInterval(interval);
                           }
                       }, 1000);
                   ">
                    Returning to main screen in <span x-text="countdown"></span> seconds...
                </p>
            </div>
        </div>
    @endif

    {{-- Footer --}}
    <div class="kiosk-footer">
        TCS Woodwork Time Clock &bull; Mon-Thu 8am-5pm
    </div>
</div>
