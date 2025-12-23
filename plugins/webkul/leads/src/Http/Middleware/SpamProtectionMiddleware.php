<?php

namespace Webkul\Lead\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SpamProtectionMiddleware
{
    /**
     * Honeypot fields that should be empty
     */
    protected array $honeypotFields = [
        'website',
        'url',
        'honey_email',
        'honey_name',
        '_gotcha',
    ];

    /**
     * Bot user-agent signatures to block
     */
    protected array $botSignatures = [
        'bot',
        'crawler',
        'spider',
        'scraper',
        'curl',
        'wget',
        'python',
        'java',
        'ruby',
        'perl',
        'libwww',
        'httpclient',
    ];

    /**
     * Minimum time in seconds for form submission
     */
    protected int $minSubmissionTime = 3;

    /**
     * Maximum submissions per hour per IP
     */
    protected int $maxPerHour = 5;

    /**
     * Maximum submissions per day per IP
     */
    protected int $maxPerDay = 20;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Check if IP is blocked
        if ($this->isIpBlocked($ip)) {
            Log::warning('Spam Protection: Blocked IP attempted submission', ['ip' => $ip]);
            abort(403, 'Your IP has been temporarily blocked due to suspicious activity.');
        }

        // Check rate limiting
        if ($this->isRateLimited($ip)) {
            Log::warning('Spam Protection: Rate limit exceeded', ['ip' => $ip]);
            abort(429, 'Too many submissions. Please try again later.');
        }

        // Check for bot user-agent
        if ($this->isBotUserAgent($request)) {
            Log::warning('Spam Protection: Bot user-agent detected', [
                'ip' => $ip,
                'user_agent' => $request->userAgent(),
            ]);
            abort(403, 'Access denied.');
        }

        // Check honeypot fields
        if ($this->hasFilledHoneypot($request)) {
            Log::warning('Spam Protection: Honeypot triggered', ['ip' => $ip]);
            $this->blockIp($ip);

            // Silent redirect for bots - don't reveal detection
            return redirect()->back()->with('success', 'Thank you for your submission.');
        }

        // Check submission timing
        if ($this->isTooFast($request)) {
            Log::warning('Spam Protection: Submission too fast', ['ip' => $ip]);

            // Silent redirect - form submitted too quickly
            return redirect()->back()->with('success', 'Thank you for your submission.');
        }

        // Increment rate limit counter
        $this->incrementRateLimit($ip);

        return $next($request);
    }

    /**
     * Check if IP is blocked
     */
    protected function isIpBlocked(string $ip): bool
    {
        return Cache::has("spam_blocked:{$ip}");
    }

    /**
     * Block an IP for 24 hours
     */
    protected function blockIp(string $ip): void
    {
        Cache::put("spam_blocked:{$ip}", true, now()->addHours(24));
    }

    /**
     * Check if IP has exceeded rate limits
     */
    protected function isRateLimited(string $ip): bool
    {
        $hourlyCount = Cache::get("spam_hourly:{$ip}", 0);
        $dailyCount = Cache::get("spam_daily:{$ip}", 0);

        return $hourlyCount >= $this->maxPerHour || $dailyCount >= $this->maxPerDay;
    }

    /**
     * Increment rate limit counters
     */
    protected function incrementRateLimit(string $ip): void
    {
        $hourlyKey = "spam_hourly:{$ip}";
        $dailyKey = "spam_daily:{$ip}";

        if (Cache::has($hourlyKey)) {
            Cache::increment($hourlyKey);
        } else {
            Cache::put($hourlyKey, 1, now()->addHour());
        }

        if (Cache::has($dailyKey)) {
            Cache::increment($dailyKey);
        } else {
            Cache::put($dailyKey, 1, now()->addDay());
        }
    }

    /**
     * Check for bot user-agent signatures
     */
    protected function isBotUserAgent(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        if (empty($userAgent)) {
            return true; // No user-agent is suspicious
        }

        foreach ($this->botSignatures as $signature) {
            if (str_contains($userAgent, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if any honeypot fields are filled
     */
    protected function hasFilledHoneypot(Request $request): bool
    {
        foreach ($this->honeypotFields as $field) {
            if (! empty($request->input($field))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if form was submitted too quickly
     */
    protected function isTooFast(Request $request): bool
    {
        $timestamp = $request->input('_timestamp');

        if (empty($timestamp)) {
            return false; // No timestamp, skip this check
        }

        $submissionTime = time() - (int) $timestamp;

        return $submissionTime < $this->minSubmissionTime;
    }
}
