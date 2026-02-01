<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveWebhookService;
use Webkul\Project\Jobs\SyncProjectDriveFolderJob;

/**
 * Google Drive Webhook Controller
 *
 * Handles incoming push notifications from Google Drive when files change.
 */
class GoogleDriveWebhookController extends Controller
{
    protected GoogleDriveWebhookService $webhookService;
    protected GoogleDriveService $driveService;

    public function __construct(
        GoogleDriveWebhookService $webhookService,
        GoogleDriveService $driveService
    ) {
        $this->webhookService = $webhookService;
        $this->driveService = $driveService;
    }

    /**
     * Handle incoming Google Drive webhook notification
     *
     * Google sends notifications with special headers:
     * - X-Goog-Channel-ID: The channel ID we created
     * - X-Goog-Resource-ID: The resource being watched
     * - X-Goog-Resource-State: sync, change, add, remove, update, trash, untrash
     * - X-Goog-Changed: Optional, comma-separated list of change types
     * - X-Goog-Message-Number: Sequence number
     */
    public function handle(Request $request): Response
    {
        // Get headers
        $channelId = $request->header('X-Goog-Channel-ID');
        $resourceId = $request->header('X-Goog-Resource-ID');
        $resourceState = $request->header('X-Goog-Resource-State');
        $changed = $request->header('X-Goog-Changed');
        $messageNumber = $request->header('X-Goog-Message-Number');

        Log::info('Google Drive webhook received', [
            'channel_id' => $channelId,
            'resource_id' => $resourceId,
            'resource_state' => $resourceState,
            'changed' => $changed,
            'message_number' => $messageNumber,
        ]);

        // Validate required headers
        if (!$channelId || !$resourceId) {
            Log::warning('Google Drive webhook missing required headers');
            return response('', 400);
        }

        // Handle sync notification (sent when watch is created)
        if ($resourceState === 'sync') {
            Log::debug('Google Drive watch sync confirmed', [
                'channel_id' => $channelId,
            ]);
            return response('', 200);
        }

        // Find the project associated with this channel
        $project = $this->webhookService->getProjectFromChannel($channelId);

        if (!$project) {
            Log::warning('Google Drive webhook for unknown channel', [
                'channel_id' => $channelId,
            ]);
            // Return 200 to prevent Google from retrying
            return response('', 200);
        }

        // Dispatch sync job based on the change type
        try {
            SyncProjectDriveFolderJob::dispatch($project, [
                'resource_state' => $resourceState,
                'changed' => $changed,
                'message_number' => $messageNumber,
            ]);

            Log::info('Dispatched Google Drive sync job', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
                'resource_state' => $resourceState,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Google Drive sync job', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Always return 200 to acknowledge receipt
        // Google will retry on non-2xx responses
        return response('', 200);
    }

    /**
     * Verify domain ownership (required by Google for push notifications)
     * This endpoint returns a verification token for Google Search Console
     */
    public function verify(Request $request): Response
    {
        $token = config('services.google_drive.domain_verification_token');

        if ($token) {
            return response($token, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('', 404);
    }

    /**
     * Get webhook status and active watches
     */
    public function status(Request $request)
    {
        $watches = \Webkul\Project\Models\GoogleDriveWatch::with('project:id,project_number,name')
            ->active()
            ->get()
            ->map(function ($watch) {
                return [
                    'project_id' => $watch->project_id,
                    'project_number' => $watch->project?->project_number,
                    'channel_id' => $watch->channel_id,
                    'folder_id' => $watch->folder_id,
                    'expires_at' => $watch->expires_at->toIso8601String(),
                    'expires_in' => $watch->expires_at->diffForHumans(),
                    'is_expiring_soon' => $watch->isExpiringSoon(),
                ];
            });

        return response()->json([
            'webhook_url' => $this->webhookService->getWebhookUrl(),
            'active_watches' => $watches->count(),
            'watches' => $watches,
        ]);
    }
}
