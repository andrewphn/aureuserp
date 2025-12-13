<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict Time Clock Kiosk to Shop Network Only
 *
 * This middleware ensures the kiosk can only be accessed from
 * the shop's network by checking the client IP against a whitelist.
 *
 * Configuration in .env:
 *   KIOSK_ALLOWED_IPS=192.168.1.0/24,10.0.0.0/8,YOUR_PUBLIC_IP
 *   KIOSK_IP_RESTRICTION_ENABLED=true
 */
class KioskIpRestriction
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if restriction is disabled (useful for development)
        if (!config('kiosk.ip_restriction_enabled', true)) {
            return $next($request);
        }

        $allowedIps = config('kiosk.allowed_ips', []);

        // If no IPs configured, allow all (with warning in logs)
        if (empty($allowedIps)) {
            \Log::warning('Kiosk IP restriction enabled but no IPs configured. Allowing all access.');
            return $next($request);
        }

        $clientIp = $request->ip();

        // Check if client IP is in allowed list
        if ($this->isIpAllowed($clientIp, $allowedIps)) {
            return $next($request);
        }

        // Log blocked attempt
        \Log::warning("Kiosk access blocked from IP: {$clientIp}");

        // Return access denied page
        return response()->view('errors.kiosk-restricted', [
            'clientIp' => $clientIp,
        ], 403);
    }

    /**
     * Check if IP is in allowed list (supports CIDR notation)
     */
    protected function isIpAllowed(string $clientIp, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowed) {
            $allowed = trim($allowed);

            // Direct IP match
            if ($clientIp === $allowed) {
                return true;
            }

            // CIDR notation check (e.g., 192.168.1.0/24)
            if (str_contains($allowed, '/')) {
                if ($this->ipInCidr($clientIp, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is within CIDR range
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        $subnetLong &= $maskLong;

        return ($ipLong & $maskLong) === $subnetLong;
    }
}
