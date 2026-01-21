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
     * Check if IP is in allowed list (supports CIDR notation for IPv4 and IPv6)
     */
    protected function isIpAllowed(string $clientIp, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowed) {
            $allowed = trim($allowed);

            // Direct IP match
            if ($clientIp === $allowed) {
                return true;
            }

            // CIDR notation check
            if (str_contains($allowed, '/')) {
                if ($this->ipInCidr($clientIp, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is within CIDR range (supports both IPv4 and IPv6)
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);

        // IPv6 check
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ipv6InCidr($ip, $cidr);
        }

        // IPv4 check
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }
            
            $maskLong = -1 << (32 - (int)$mask);
            $subnetLong &= $maskLong;
            
            return ($ipLong & $maskLong) === $subnetLong;
        }

        return false;
    }

    /**
     * Check if IPv6 address is within CIDR range
     */
    protected function ipv6InCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        // Normalize IPv6 addresses
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }
        
        // Calculate mask bytes
        $maskBytes = (int)$mask;
        $fullBytes = intval($maskBytes / 8);
        $bits = $maskBytes % 8;
        
        // Compare full bytes
        for ($i = 0; $i < $fullBytes; $i++) {
            if ($ipBin[$i] !== $subnetBin[$i]) {
                return false;
            }
        }
        
        // Compare partial byte if needed
        if ($bits > 0 && $fullBytes < 16) {
            $maskByte = 0xFF << (8 - $bits);
            if ((ord($ipBin[$fullBytes]) & $maskByte) !== (ord($subnetBin[$fullBytes]) & $maskByte)) {
                return false;
            }
        }
        
        return true;
    }
}
