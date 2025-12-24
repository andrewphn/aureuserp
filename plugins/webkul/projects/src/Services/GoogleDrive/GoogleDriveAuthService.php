<?php

namespace Webkul\Project\Services\GoogleDrive;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Google Drive Authentication Service
 *
 * Handles OAuth2 authentication for Google Drive API access using refresh tokens.
 */
class GoogleDriveAuthService
{
    private ?Client $client = null;
    private ?Drive $driveService = null;

    private const TOKEN_CACHE_KEY = 'google_drive_access_token';
    private const TOKEN_CACHE_TTL = 3300; // 55 minutes

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize the Google Client
     */
    protected function initializeClient(): void
    {
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            Log::warning('Google Drive credentials not configured');
            return;
        }

        try {
            $this->client = new Client();
            $this->client->setApplicationName('TCS Woodwork ERP');
            $this->client->setClientId($clientId);
            $this->client->setClientSecret($clientSecret);
            $this->client->setAccessType('offline');
            $this->client->setScopes([
                'https://www.googleapis.com/auth/drive',
                'https://www.googleapis.com/auth/drive.file',
            ]);

            // Try to get cached access token
            $accessToken = $this->getCachedAccessToken();

            if ($accessToken && !$this->isTokenExpired($accessToken)) {
                $this->client->setAccessToken($accessToken);
            } else {
                // Refresh the token
                $this->refreshAndCacheToken($refreshToken);
            }
        } catch (Exception $e) {
            Log::error('Failed to initialize Google Drive client', [
                'error' => $e->getMessage(),
            ]);
            $this->client = null;
        }
    }

    /**
     * Check if the service is configured and ready
     */
    public function isConfigured(): bool
    {
        return $this->client !== null;
    }

    /**
     * Get the authenticated Google Client
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * Get the Google Drive service
     */
    public function getDriveService(): ?Drive
    {
        if (!$this->client) {
            return null;
        }

        if (!$this->driveService) {
            $this->driveService = new Drive($this->client);
        }

        return $this->driveService;
    }

    /**
     * Get the configured root folder ID
     */
    public function getFolderId(): ?string
    {
        return config('services.google_drive.folder_id');
    }

    /**
     * Test the authentication by making a simple API call
     */
    public function testConnection(): array
    {
        try {
            $drive = $this->getDriveService();

            if (!$drive) {
                return [
                    'success' => false,
                    'error' => 'Google Drive not configured',
                ];
            }

            // Test by getting about info
            $about = $drive->about->get(['fields' => 'user']);

            return [
                'success' => true,
                'user' => $about->getUser()->getEmailAddress(),
                'display_name' => $about->getUser()->getDisplayName(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cached access token
     */
    protected function getCachedAccessToken(): ?array
    {
        return Cache::get(self::TOKEN_CACHE_KEY);
    }

    /**
     * Check if token is expired
     */
    protected function isTokenExpired(array $token): bool
    {
        if (!isset($token['created'])) {
            return true;
        }

        $expiresIn = $token['expires_in'] ?? 3600;
        $created = $token['created'];
        $expiryTime = $created + $expiresIn - 300; // 5 minutes buffer

        return time() >= $expiryTime;
    }

    /**
     * Refresh and cache the access token
     */
    protected function refreshAndCacheToken(string $refreshToken): void
    {
        try {
            $this->client->refreshToken($refreshToken);
            $accessToken = $this->client->getAccessToken();

            if ($accessToken) {
                Cache::put(self::TOKEN_CACHE_KEY, $accessToken, self::TOKEN_CACHE_TTL);
                Log::debug('Google Drive access token refreshed and cached');
            }
        } catch (Exception $e) {
            Log::error('Failed to refresh Google Drive token', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Clear cached token (useful for testing or re-authentication)
     */
    public function clearCache(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
        $this->client = null;
        $this->driveService = null;
    }
}
