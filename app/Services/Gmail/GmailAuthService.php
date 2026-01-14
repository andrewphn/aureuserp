<?php

namespace App\Services\Gmail;

use Exception;
use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Gmail Authentication Service
 *
 * Reuses Google OAuth credentials to access Gmail API with readonly scope.
 */
class GmailAuthService
{
    private const TOKEN_CACHE_KEY = 'google_gmail_access_token';
    private const TOKEN_CACHE_TTL = 3300; // 55 minutes

    private ?Client $client = null;
    private ?Gmail $gmailService = null;

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Check if the service is configured and ready.
     */
    public function isConfigured(): bool
    {
        return $this->client !== null;
    }

    /**
     * Get the authenticated Gmail service.
     */
    public function getGmailService(): ?Gmail
    {
        if (! $this->client) {
            return null;
        }

        if (! $this->gmailService) {
            $this->gmailService = new Gmail($this->client);
        }

        return $this->gmailService;
    }

    /**
     * Initialize the Google Client.
     */
    protected function initializeClient(): void
    {
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            Log::warning('Gmail credentials not configured');
            return;
        }

        try {
            $this->client = new Client();
            $this->client->setApplicationName('TCS Woodwork ERP');
            $this->client->setClientId($clientId);
            $this->client->setClientSecret($clientSecret);
            $this->client->setAccessType('offline');
            $this->client->setScopes([
                'https://www.googleapis.com/auth/gmail.readonly',
            ]);

            $accessToken = $this->getCachedAccessToken();

            if ($accessToken && ! $this->isTokenExpired($accessToken)) {
                $this->client->setAccessToken($accessToken);
            } else {
                $this->refreshAndCacheToken($refreshToken);
            }
        } catch (Exception $e) {
            Log::error('Failed to initialize Gmail client', [
                'error' => $e->getMessage(),
            ]);
            $this->client = null;
        }
    }

    /**
     * Get cached access token.
     */
    protected function getCachedAccessToken(): ?array
    {
        return Cache::get(self::TOKEN_CACHE_KEY);
    }

    /**
     * Check if token is expired.
     */
    protected function isTokenExpired(array $token): bool
    {
        if (! isset($token['created'])) {
            return true;
        }

        $expiresIn = $token['expires_in'] ?? 3600;
        $created = $token['created'];
        $expiryTime = $created + $expiresIn - 300; // 5 minutes buffer

        return time() >= $expiryTime;
    }

    /**
     * Refresh and cache the access token.
     */
    protected function refreshAndCacheToken(string $refreshToken): void
    {
        try {
            $this->client->refreshToken($refreshToken);
            $accessToken = $this->client->getAccessToken();

            if ($accessToken) {
                Cache::put(self::TOKEN_CACHE_KEY, $accessToken, self::TOKEN_CACHE_TTL);
                Log::debug('Gmail access token refreshed and cached');
            }
        } catch (Exception $e) {
            Log::error('Failed to refresh Gmail token', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
