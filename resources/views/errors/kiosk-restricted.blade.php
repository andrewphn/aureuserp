<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Restricted - TCS Woodwork</title>
    <link rel="stylesheet" href="/css/time-clock-kiosk.css">
</head>
<body>
    <div class="kiosk-container" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
        <div class="kiosk-header">
            <h1 class="kiosk-title">TCS Woodwork</h1>
            <p class="kiosk-subtitle">Time Clock</p>
        </div>

        <div class="clock-card" style="max-width: 500px; text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔒</div>
            <h2 class="employee-name" style="color: #ef4444;">Access Restricted</h2>
            <p class="clock-status" style="margin-bottom: 1.5rem;">
                The time clock is only accessible from the shop network.
            </p>
            <p style="color: #6b7280; font-size: 0.875rem;">
                Please use the tablet in the shop to clock in/out.
            </p>
            @if(config('app.debug'))
                <p style="color: #6b7280; font-size: 0.75rem; margin-top: 2rem;">
                    Your IP: {{ $clientIp ?? 'Unknown' }}
                </p>
            @endif
        </div>

        <div class="kiosk-footer">
            TCS Woodwork Time Clock
        </div>
    </div>
</body>
</html>
