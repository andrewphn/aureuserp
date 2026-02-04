<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Time Clock - TCS Woodwork</title>

    {{-- Favicon and App Icons --}}
    <link rel="icon" type="image/png" href="/tcs_logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/tcs_logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/tcs_logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/tcs_logo.png">
    <meta name="apple-mobile-web-app-title" content="TCS Time Clock">

    {{-- Kiosk mode --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#111827">

    {{-- Auto-redirect for confirmation and summary screens --}}
    @if(($mode ?? '') === 'confirmed' || ($mode ?? '') === 'summary')
        <meta http-equiv="refresh" content="5;url={{ route('clock-legacy') }}">
    @endif

    {{-- Same CSS as the modern kiosk --}}
    <link rel="stylesheet" href="/css/time-clock-kiosk.css?v={{ filemtime(public_path('css/time-clock-kiosk.css')) }}">

    {{-- No Livewire, no Alpine, no Vite, no JS --}}
</head>
<body class="antialiased">
    <div class="kiosk-container">

        {{-- Header --}}
        <div class="kiosk-header">
            <img src="/tcs_logo.png"
                 alt="TCS Woodwork"
                 style="height: 5rem; margin-bottom: 0.5rem; display: inline-block; filter: invert(1);">
            <p class="kiosk-subtitle">Time Clock</p>
            <div class="kiosk-time">{{ now()->format('g:i A') }}</div>
            <p class="kiosk-date">{{ now()->format('l, F j, Y') }}</p>
        </div>

        {{-- Status / Error Messages --}}
        @if(session('error') || isset($error))
            <div class="status-message status-error">
                {{ session('error') ?? $error }}
            </div>
        @endif

        @if(session('success') || isset($success))
            <div class="status-message status-success">
                {{ session('success') ?? $success }}
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- MODE: select — Employee Selection                            --}}
        {{-- ============================================================ --}}
        @if(($mode ?? 'select') === 'select')
            <div class="employee-grid">
                <h2 class="section-title">Select Your Name</h2>

                <div class="employee-buttons">
                    @foreach($employees ?? [] as $employee)
                        <form method="POST" action="{{ route('clock-legacy.select') }}" style="display:contents;">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $employee['id'] }}">
                            <input type="hidden" name="name" value="{{ $employee['name'] }}">
                            <input type="hidden" name="employee_id" value="{{ $employee['employee_id'] }}">
                            <button type="submit" class="employee-btn">
                                {{ $employee['name'] }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

        {{-- ============================================================ --}}
        {{-- MODE: pin — PIN Entry                                        --}}
        {{-- ============================================================ --}}
        @elseif($mode === 'pin')
            <div class="clock-panel">
                <a href="{{ route('clock-legacy.back') }}" class="back-btn" style="text-decoration:none;">
                    &larr; Back to employee list
                </a>

                <div class="clock-card">
                    <h2 class="employee-name">{{ $selectedName }}</h2>
                    <p class="clock-status">Enter your {{ $pinLength }}-digit PIN</p>

                    {{-- PIN Display (updated client-side, no page reload) --}}
                    <div class="pin-display" id="pinDots">
                        @for($i = 0; $i < $pinLength; $i++)
                            <div class="pin-dot" id="dot{{ $i }}"></div>
                        @endfor
                    </div>

                    {{-- Single form — PIN is accumulated client-side via basic JS (ES3, works on iOS 12) --}}
                    <form method="POST" action="{{ route('clock-legacy.pin') }}" id="pinForm">
                        @csrf
                        <input type="hidden" name="digit" value="" id="pinAll">

                        <div class="numpad">
                            @foreach([1,2,3,4,5,6,7,8,9] as $num)
                                <button type="button" class="numpad-btn" onclick="addDigit('{{ $num }}')">{{ $num }}</button>
                            @endforeach

                            {{-- Clear --}}
                            <button type="button" class="numpad-btn numpad-action" onclick="clearPin()">Clear</button>

                            {{-- 0 --}}
                            <button type="button" class="numpad-btn" onclick="addDigit('0')">0</button>

                            {{-- Backspace --}}
                            <button type="button" class="numpad-btn numpad-action" onclick="backspace()">&larr;</button>
                        </div>
                    </form>

                    {{--
                        Basic inline JS — no Proxy, no ES2015+, no Alpine.
                        Uses only var, getElementById, className — works on iOS 9+.
                    --}}
                    <script>
                        var pin = '';
                        var pinLen = {{ $pinLength }};
                        function updateDots() {
                            for (var i = 0; i < pinLen; i++) {
                                var dot = document.getElementById('dot' + i);
                                if (i < pin.length) {
                                    dot.className = 'pin-dot pin-dot-filled';
                                } else {
                                    dot.className = 'pin-dot';
                                }
                            }
                        }
                        function addDigit(d) {
                            if (pin.length < pinLen) {
                                pin = pin + d;
                                updateDots();
                                if (pin.length >= pinLen) {
                                    document.getElementById('pinAll').value = pin;
                                    document.getElementById('pinForm').submit();
                                }
                            }
                        }
                        function clearPin() {
                            pin = '';
                            updateDots();
                        }
                        function backspace() {
                            pin = pin.substring(0, pin.length - 1);
                            updateDots();
                        }
                    </script>
                </div>
            </div>

        {{-- ============================================================ --}}
        {{-- MODE: confirmed — Clock-In Confirmation                      --}}
        {{-- ============================================================ --}}
        @elseif($mode === 'confirmed')
            <div class="clock-panel">
                <div class="clock-card" style="text-align: center; padding: 3rem 2rem;">
                    <div style="font-size: 4rem; font-weight: 700; color: #10b981; margin-bottom: 1rem;">
                        &#10003;
                    </div>
                    <h2 class="employee-name" style="margin-bottom: 1rem;">{{ $selectedName }}</h2>
                    <p class="clock-status" style="font-size: 1.5rem; margin-bottom: 2rem;">
                        Clocked In
                    </p>
                    <div style="background: rgba(255,255,255,0.08); border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
                        <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 0.5rem;">Time</p>
                        <p style="font-size: 2.5rem; font-weight: 700; color: rgba(255,255,255,0.98); margin-bottom: 0;">
                            {{ $clockedInAt }}
                        </p>
                    </div>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem;">
                        Returning to main screen in 5 seconds&hellip;
                    </p>
                    <a href="{{ route('clock-legacy') }}" class="back-btn" style="text-decoration:none; margin-top:1rem; display:inline-block;">
                        Return now
                    </a>
                </div>
            </div>

        {{-- ============================================================ --}}
        {{-- MODE: clock — Already Clocked In, show clock-out option      --}}
        {{-- ============================================================ --}}
        @elseif($mode === 'clock')
            <div class="clock-panel">
                <a href="{{ route('clock-legacy.back') }}" class="back-btn" style="text-decoration:none;">
                    &larr; Back to employee list
                </a>

                <div class="clock-card">
                    <h2 class="employee-name">{{ $selectedName }}</h2>

                    <div style="background: rgba(255,255,255,0.08); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; text-align: center;">
                        <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 0.5rem;">Clocked In</p>
                        <p style="font-size: 2.5rem; font-weight: 700; color: rgba(255,255,255,0.98);">
                            {{ $clockedInAt }}
                        </p>

                        @if($isOnLunch ?? false)
                            <p style="color: #f59e0b; font-size: 1.25rem; font-weight: 600; margin-top: 1rem;">
                                Currently on lunch break
                            </p>
                        @elseif($lunchTaken ?? false)
                            <p style="color: #10b981; font-size: 1rem; margin-top: 1rem;">
                                Lunch completed
                            </p>
                        @else
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-top: 1rem;">
                                No lunch taken
                            </p>
                        @endif
                    </div>

                    {{-- Clock Out Button --}}
                    <form method="POST" action="{{ route('clock-legacy.clock-out') }}">
                        @csrf
                        <button type="submit" class="clock-out-btn" style="width: 100%;">
                            Clock Out
                        </button>
                    </form>
                </div>
            </div>

        {{-- ============================================================ --}}
        {{-- MODE: clockout-lunch — Lunch Duration Selection              --}}
        {{-- ============================================================ --}}
        @elseif($mode === 'clockout-lunch')
            <div class="clock-panel">
                <a href="{{ route('clock-legacy.back') }}" class="back-btn" style="text-decoration:none;">
                    &larr; Back
                </a>

                <div class="clock-card">
                    <h2 class="employee-name">{{ $selectedName }}</h2>
                    <p class="clock-status">No lunch was logged. Select lunch duration:</p>

                    {{-- Preset Duration Buttons --}}
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 2rem 0;">
                        @foreach([30 => '30 min', 45 => '45 min', 60 => '1 hour'] as $mins => $label)
                            <form method="POST" action="{{ route('clock-legacy.lunch-clock-out') }}" style="display:contents;">
                                @csrf
                                <input type="hidden" name="minutes" value="{{ $mins }}">
                                <button type="submit" class="clock-in-btn" style="padding: 1.5rem; font-size: 1.25rem; font-weight: 600;">
                                    {{ $label }}
                                </button>
                            </form>
                        @endforeach
                    </div>

                    {{-- No lunch option --}}
                    <form method="POST" action="{{ route('clock-legacy.lunch-clock-out') }}" style="margin-top: 1rem;">
                        @csrf
                        <input type="hidden" name="minutes" value="0">
                        <button type="submit" class="clock-out-btn" style="width: 100%;">
                            No Lunch &mdash; Clock Out Now
                        </button>
                    </form>

                    {{-- Custom Duration --}}
                    <form method="POST" action="{{ route('clock-legacy.lunch-clock-out') }}" style="margin-top: 1.5rem; padding: 1.5rem; background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px solid rgba(255,255,255,0.15);">
                        @csrf
                        <label style="display: block; color: rgba(255,255,255,0.75); font-size: 0.9rem; margin-bottom: 0.5rem; font-weight: 600;">
                            Custom duration (minutes):
                        </label>
                        <div style="display:flex; gap:0.75rem; align-items:center;">
                            <input
                                type="number"
                                name="minutes"
                                min="1"
                                max="480"
                                placeholder="e.g. 45"
                                style="flex:1; padding: 1rem; font-size: 1.25rem; border: 1px solid rgba(255,255,255,0.25); border-radius: 8px; text-align: center; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.95);">
                            <button type="submit" class="clock-in-btn" style="padding: 1rem 1.5rem;">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        {{-- ============================================================ --}}
        {{-- MODE: summary — Clock-Out Summary                            --}}
        {{-- ============================================================ --}}
        @elseif($mode === 'summary')
            <div class="clock-panel">
                <div class="clock-card">
                    <h2 class="employee-name">{{ $selectedName }}</h2>
                    <p class="clock-status" style="color: #10b981; font-size: 1.5rem; font-weight: 600; margin-bottom: 2rem;">
                        &#10003; Clocked Out
                    </p>

                    <div style="background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid rgba(255,255,255,0.15);">
                        <div style="margin-bottom: 1.5rem;">
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 0.5rem;">Clock In</p>
                            <p style="font-size: 1.5rem; font-weight: 600; color: rgba(255,255,255,0.98);">{{ $clockInTime ?? 'N/A' }}</p>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 0.5rem;">Clock Out</p>
                            <p style="font-size: 1.5rem; font-weight: 600; color: rgba(255,255,255,0.98);">{{ $clockOutTime ?? 'N/A' }}</p>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 0.5rem;">Hours Worked</p>
                            <p style="font-size: 2rem; font-weight: 700; color: #059669;">
                                @php
                                    $h = floor($hoursWorked ?? 0);
                                    $m = round((($hoursWorked ?? 0) - $h) * 60);
                                @endphp
                                {{ $h }}h{{ $m > 0 ? " {$m}m" : '' }}
                            </p>
                        </div>

                        @if($lunchMinutes ?? null)
                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 0.5rem;">Lunch</p>
                                <p style="font-size: 1.25rem; font-weight: 600; color: #10b981;">{{ $lunchMinutes }} minutes</p>
                            </div>
                        @endif

                        @if($projectName ?? null)
                            <div>
                                <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 0.5rem;">Project</p>
                                <p style="font-size: 1.25rem; font-weight: 600; color: #3b82f6;">{{ $projectName }}</p>
                            </div>
                        @endif
                    </div>

                    <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-align: center; margin-top: 1rem;">
                        Returning to main screen in 5 seconds&hellip;
                    </p>
                    <a href="{{ route('clock-legacy') }}" class="back-btn" style="text-decoration:none; margin-top:1rem; display:inline-block;">
                        Return now
                    </a>
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="kiosk-footer">
            TCS Woodwork Time Clock &bull; Mon-Thu 8am-5pm
        </div>
    </div>
</body>
</html>
