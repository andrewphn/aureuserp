<?php

namespace App\Filament\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\Log;

class Login extends BaseLogin
{
    use WithRateLimiting;

    /**
     * Mount
     */
    public function mount(): void
    {
        $isAuthenticated = Filament::auth()->check();
        $sessionId = session()->getId();
        $sessionExists = session()->has('_token');
        $cookieName = config('session.cookie');
        $cookieDomain = config('session.domain');
        $cookieSecure = config('session.secure');
        $cookieSameSite = config('session.same_site');
        
        Log::info('[LOGIN DEBUG] Mount called', [
            'is_authenticated' => $isAuthenticated,
            'user_id' => $isAuthenticated ? Filament::auth()->id() : null,
            'user_email' => $isAuthenticated ? Filament::auth()->user()?->email : null,
            'session_id' => $sessionId,
            'session_exists' => $sessionExists,
            'cookie_name' => $cookieName,
            'cookie_domain' => $cookieDomain,
            'cookie_secure' => $cookieSecure,
            'cookie_same_site' => $cookieSameSite,
            'url' => request()->url(),
            'ip' => request()->ip(),
        ]);

        // Call parent mount first
        parent::mount();

        // Additional debugging after parent mount
        if (Filament::auth()->check()) {
            $user = Filament::auth()->user();
            $panel = Filament::getCurrentOrDefaultPanel();
            $canAccess = $user instanceof FilamentUser ? $user->canAccessPanel($panel) : false;
            
            Log::info('[LOGIN DEBUG] After parent mount - checking access', [
                'user_id' => $user->id ?? null,
                'user_email' => $user->email ?? null,
                'panel_id' => $panel->getId(),
                'can_access' => $canAccess,
            ]);
        }
    }

    /**
     * Authenticate - override with debugging
     */
    public function authenticate(): ?LoginResponse
    {
        $sessionIdBefore = session()->getId();
        $data = $this->form->getState();
        
        Log::info('[LOGIN DEBUG] Authenticate called', [
            'email' => $data['email'] ?? null,
            'has_password' => !empty($data['password'] ?? null),
            'password_length' => isset($data['password']) ? strlen($data['password']) : 0,
            'remember' => $data['remember'] ?? false,
            'form_data_keys' => array_keys($data),
            'session_id_before' => $sessionIdBefore,
            'url' => request()->url(),
            'ip' => request()->ip(),
            'request_method' => request()->method(),
            'all_request_data' => request()->all(),
        ]);

        try {
            $this->rateLimit(5);
            Log::debug('[LOGIN DEBUG] Rate limit check passed');
        } catch (TooManyRequestsException $exception) {
            Log::warning('[LOGIN DEBUG] Rate limit exceeded', [
                'seconds_until_available' => $exception->secondsUntilAvailable,
            ]);
            return parent::authenticate(); // Let parent handle the rate limit notification
        }

        // Call parent authenticate but log the result
        try {
            $result = parent::authenticate();
        } catch (\Exception $e) {
            Log::error('[LOGIN DEBUG] Exception during authentication', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
        
        $sessionIdAfter = session()->getId();
        $user = Filament::auth()->user();
        $panel = Filament::getCurrentOrDefaultPanel();

        Log::info('[LOGIN DEBUG] After parent authenticate', [
            'result' => $result !== null ? 'success' : 'failed',
            'session_id_after' => $sessionIdAfter,
            'session_id_changed' => $sessionIdBefore !== $sessionIdAfter,
            'user_id' => $user?->id ?? null,
            'user_email' => $user?->email ?? null,
            'is_filament_user' => $user instanceof FilamentUser,
            'panel_id' => $panel->getId(),
            'can_access' => $user instanceof FilamentUser ? $user->canAccessPanel($panel) : false,
            'redirect_url' => $result ? 'will redirect' : 'no redirect',
        ]);

        if ($result === null && $user) {
            Log::warning('[LOGIN DEBUG] Authenticate returned null but user exists', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
        }

        if ($result === null && !$user) {
            Log::warning('[LOGIN DEBUG] Authenticate returned null and no user', [
                'likely_reason' => 'authentication_failed_or_validation_error',
            ]);
        }

        return $result;
    }
}
