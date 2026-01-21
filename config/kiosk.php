<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kiosk IP Restriction
    |--------------------------------------------------------------------------
    |
    | When enabled, the time clock kiosk will only be accessible from
    | IP addresses in the allowed list. This prevents employees from
    | clocking in/out from home.
    |
    | Set KIOSK_IP_RESTRICTION_ENABLED=false in .env for development.
    |
    */
    'ip_restriction_enabled' => env('KIOSK_IP_RESTRICTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed IP Addresses
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of allowed IPs or CIDR ranges.
    | Examples:
    |   - Single IP: 203.0.113.50
    |   - CIDR range: 192.168.1.0/24 (all IPs from 192.168.1.0 to 192.168.1.255)
    |   - Local network: 10.0.0.0/8
    |
    | Common setups:
    |   - Shop WiFi only: Set to your shop's public IP
    |   - Local network: 192.168.1.0/24
    |   - Multiple locations: 203.0.113.50,198.51.100.25
    |
    */
    'allowed_ips' => array_filter(
        array_map('trim', explode(',', env('KIOSK_ALLOWED_IPS', '127.0.0.1,::1')))
    ),

    /*
    |--------------------------------------------------------------------------
    | PIN Requirement
    |--------------------------------------------------------------------------
    |
    | When enabled, employees must enter their PIN to clock in/out.
    | PINs are stored on the employee record.
    |
    */
    'pin_required' => env('KIOSK_PIN_REQUIRED', true),

    /*
    |--------------------------------------------------------------------------
    | PIN Length
    |--------------------------------------------------------------------------
    |
    | The required length for employee PINs.
    |
    */
    'pin_length' => env('KIOSK_PIN_LENGTH', 4),

    /*
    |--------------------------------------------------------------------------
    | Inactivity Timeout
    |--------------------------------------------------------------------------
    |
    | Number of seconds of inactivity before automatically resetting
    | to the employee selection screen. This helps maintain privacy
    | on shared kiosk devices.
    |
    | Set to 0 to disable auto-timeout.
    |
    */
    'timeout_seconds' => env('KIOSK_TIMEOUT_SECONDS', 60),
];
