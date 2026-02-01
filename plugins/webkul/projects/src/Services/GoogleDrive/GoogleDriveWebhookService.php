<?php

namespace Webkul\Project\Services\GoogleDrive;

use Google\Service\Drive;
use Google\Service\Drive\Channel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\GoogleDriveWatch;
use Exception;

/**
 * Google Drive Webhook Service
 *
 * Manages push notification channels (watches) for Google Drive folders.
 * When files change in watched folders, Google sends notifications to our webhook endpoint.
 */
class GoogleDriveWebhookService
{
    protected ?Drive $driveService;
    protected GoogleDriveAuthService $authService;

    /**
     * Watch expiration time (Google max is ~1 week, we use 6 days to be safe)
     */
    public const WATCH_EXPIRATION_SECONDS = 518400; // 6 days

    /**
     * Cache prefix for watch data
     */
    private const CACHE_PREFIX = 'google_drive_watch_';

    public function __construct(GoogleDriveAuthService $authService)
    {
        $this->authService = $authService;
        $this->driveService = $authService->getDriveService();
    }

    /**
     * Check if service is ready
     */
    public function isReady(): bool
    {
        return $this->driveService !== null;
    }

    /**
     * Get the webhook callback URL
     */
    public function getWebhookUrl(): string
    {
        return config('services.google_drive.webhook_url')
            ?? url('/api/v1/google-drive/webhook');
    }

    /**
     * Create a watch channel for a project's Google Drive folder
     *
     * @param Project $project The project to watch
     * @return array|null Watch channel info or null on failure
     */
    public function watchProject(Project $project): ?array
    {
        if (!$this->isReady()) {
            Log::warning('Google Drive service not ready, cannot create watch');
            return null;
        }

        if (!$project->google_drive_root_folder_id) {
            Log::debug('Project has no Google Drive folder, cannot watch', [
                'project_id' => $project->id,
            ]);
            return null;
        }

        try {
            // Generate unique channel ID
            $channelId = 'project-' . $project->id . '-' . Str::uuid();

            // Calculate expiration (Google uses milliseconds)
            $expirationMs = (time() + self::WATCH_EXPIRATION_SECONDS) * 1000;

            // Create the watch channel
            $channel = new Channel([
                'id' => $channelId,
                'type' => 'web_hook',
                'address' => $this->getWebhookUrl(),
                'expiration' => $expirationMs,
                'payload' => true,
                'params' => [
                    'ttl' => self::WATCH_EXPIRATION_SECONDS,
                ],
            ]);

            // Start watching the folder
            $response = $this->driveService->files->watch(
                $project->google_drive_root_folder_id,
                $channel
            );

            $watchData = [
                'channel_id' => $response->getId(),
                'resource_id' => $response->getResourceId(),
                'resource_uri' => $response->getResourceUri(),
                'expiration' => $response->getExpiration(),
                'project_id' => $project->id,
                'folder_id' => $project->google_drive_root_folder_id,
                'created_at' => now()->toIso8601String(),
            ];

            // Store watch in database
            $this->storeWatch($project, $watchData);

            // Also cache for quick lookup
            Cache::put(
                self::CACHE_PREFIX . $response->getId(),
                $watchData,
                self::WATCH_EXPIRATION_SECONDS
            );

            Log::info('Created Google Drive watch for project', [
                'project_id' => $project->id,
                'channel_id' => $response->getId(),
                'expiration' => date('Y-m-d H:i:s', $response->getExpiration() / 1000),
            ]);

            return $watchData;
        } catch (Exception $e) {
            Log::error('Failed to create Google Drive watch', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Stop watching a project's Google Drive folder
     *
     * @param Project $project The project to stop watching
     * @return bool Success status
     */
    public function unwatchProject(Project $project): bool
    {
        $watch = $this->getActiveWatch($project);

        if (!$watch) {
            return true; // Nothing to unwatch
        }

        return $this->stopWatch($watch['channel_id'], $watch['resource_id']);
    }

    /**
     * Stop a specific watch channel
     *
     * @param string $channelId The channel ID
     * @param string $resourceId The resource ID
     * @return bool Success status
     */
    public function stopWatch(string $channelId, string $resourceId): bool
    {
        if (!$this->isReady()) {
            return false;
        }

        try {
            $channel = new Channel([
                'id' => $channelId,
                'resourceId' => $resourceId,
            ]);

            $this->driveService->channels->stop($channel);

            // Remove from database and cache
            GoogleDriveWatch::where('channel_id', $channelId)->delete();
            Cache::forget(self::CACHE_PREFIX . $channelId);

            Log::info('Stopped Google Drive watch', [
                'channel_id' => $channelId,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to stop Google Drive watch', [
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Renew a project's watch before it expires
     *
     * @param Project $project The project to renew watch for
     * @return array|null New watch data or null on failure
     */
    public function renewWatch(Project $project): ?array
    {
        // Stop existing watch first
        $this->unwatchProject($project);

        // Create new watch
        return $this->watchProject($project);
    }

    /**
     * Get active watch for a project
     *
     * @param Project $project The project
     * @return array|null Watch data or null if not watching
     */
    public function getActiveWatch(Project $project): ?array
    {
        $watch = GoogleDriveWatch::where('project_id', $project->id)
            ->where('expires_at', '>', now())
            ->first();

        if ($watch) {
            return $watch->toArray();
        }

        return null;
    }

    /**
     * Get project from channel ID
     *
     * @param string $channelId The channel ID from webhook
     * @return Project|null The project or null if not found
     */
    public function getProjectFromChannel(string $channelId): ?Project
    {
        // Try cache first
        $cached = Cache::get(self::CACHE_PREFIX . $channelId);
        if ($cached && isset($cached['project_id'])) {
            return Project::find($cached['project_id']);
        }

        // Fall back to database
        $watch = GoogleDriveWatch::where('channel_id', $channelId)->first();
        if ($watch) {
            return Project::find($watch->project_id);
        }

        return null;
    }

    /**
     * Store watch data in database
     *
     * @param Project $project The project
     * @param array $watchData The watch data
     */
    protected function storeWatch(Project $project, array $watchData): void
    {
        // Remove any existing watches for this project
        GoogleDriveWatch::where('project_id', $project->id)->delete();

        // Create new watch record
        GoogleDriveWatch::create([
            'project_id' => $project->id,
            'channel_id' => $watchData['channel_id'],
            'resource_id' => $watchData['resource_id'],
            'resource_uri' => $watchData['resource_uri'] ?? null,
            'folder_id' => $watchData['folder_id'],
            'expires_at' => now()->addSeconds(self::WATCH_EXPIRATION_SECONDS),
        ]);
    }

    /**
     * Get all watches expiring soon (within 24 hours)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringWatches()
    {
        return GoogleDriveWatch::where('expires_at', '<=', now()->addDay())
            ->where('expires_at', '>', now())
            ->get();
    }

    /**
     * Renew all expiring watches
     *
     * @return array Summary of renewals
     */
    public function renewExpiringWatches(): array
    {
        $watches = $this->getExpiringWatches();
        $results = [
            'total' => $watches->count(),
            'renewed' => 0,
            'failed' => 0,
        ];

        foreach ($watches as $watch) {
            $project = Project::find($watch->project_id);
            if (!$project) {
                $watch->delete();
                continue;
            }

            $newWatch = $this->renewWatch($project);
            if ($newWatch) {
                $results['renewed']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info('Renewed expiring Google Drive watches', $results);

        return $results;
    }
}
