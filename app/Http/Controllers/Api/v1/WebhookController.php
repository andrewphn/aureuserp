<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\WebhookSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends BaseApiController
{
    /**
     * List all webhook subscriptions for the authenticated user
     */
    public function list(Request $request): JsonResponse
    {
        $subscriptions = WebhookSubscription::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($sub) => $this->transformSubscription($sub));

        return $this->success($subscriptions, 'Webhook subscriptions retrieved');
    }

    /**
     * Subscribe to webhook events
     */
    public function subscribe(Request $request): JsonResponse
    {
        // Build allowed events list including wildcards
        $allowedEvents = array_merge(
            WebhookSubscription::EVENTS,
            ['*', 'project.*', 'room.*', 'cabinet.*', 'cabinet_run.*', 'drawer.*', 'door.*', 'task.*', 'employee.*', 'product.*', 'partner.*']
        );

        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', $allowedEvents),
            'name' => 'nullable|string|max:255',
            'secret' => 'nullable|string|max:64',
        ]);

        $subscription = WebhookSubscription::create([
            'user_id' => $request->user()->id,
            'url' => $validated['url'],
            'events' => $validated['events'],
            'name' => $validated['name'] ?? null,
            'secret' => $validated['secret'] ?? null,
            'is_active' => true,
        ]);

        return $this->success(
            $this->transformSubscription($subscription),
            'Webhook subscription created',
            201
        );
    }

    /**
     * Update a webhook subscription
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('user_id', $request->user()->id)
            ->find($id);

        if (!$subscription) {
            return $this->notFound('Webhook subscription not found');
        }

        // Build allowed events list including wildcards
        $allowedEvents = array_merge(
            WebhookSubscription::EVENTS,
            ['*', 'project.*', 'room.*', 'cabinet.*', 'cabinet_run.*', 'drawer.*', 'door.*', 'task.*', 'employee.*', 'product.*', 'partner.*']
        );

        $validated = $request->validate([
            'url' => 'sometimes|url|max:2048',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string|in:' . implode(',', $allowedEvents),
            'name' => 'nullable|string|max:255',
            'secret' => 'nullable|string|max:64',
            'is_active' => 'sometimes|boolean',
        ]);

        $subscription->update($validated);

        return $this->success(
            $this->transformSubscription($subscription->fresh()),
            'Webhook subscription updated'
        );
    }

    /**
     * Unsubscribe from webhooks
     */
    public function unsubscribe(Request $request, int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('user_id', $request->user()->id)
            ->find($id);

        if (!$subscription) {
            return $this->notFound('Webhook subscription not found');
        }

        $subscription->delete();

        return $this->success(null, 'Webhook subscription deleted');
    }

    /**
     * Get available webhook events
     */
    public function events(): JsonResponse
    {
        $events = collect(WebhookSubscription::EVENTS)
            ->groupBy(fn ($event) => explode('.', $event)[0])
            ->map(fn ($group, $resource) => [
                'resource' => $resource,
                'events' => $group->values(),
            ])
            ->values();

        return $this->success([
            'events' => WebhookSubscription::EVENTS,
            'grouped' => $events,
            'wildcards' => [
                '*' => 'All events',
                'project.*' => 'All project events',
                'cabinet.*' => 'All cabinet events',
            ],
        ], 'Available webhook events');
    }

    /**
     * Test a webhook subscription by sending a test payload
     */
    public function test(Request $request, int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('user_id', $request->user()->id)
            ->find($id);

        if (!$subscription) {
            return $this->notFound('Webhook subscription not found');
        }

        // Dispatch a test event
        \App\Jobs\DispatchWebhook::dispatch('test.ping', [
            'message' => 'This is a test webhook from AureusERP',
            'subscription_id' => $subscription->id,
            'user_id' => $request->user()->id,
        ]);

        return $this->success(null, 'Test webhook queued for delivery');
    }

    /**
     * Get delivery history for a subscription
     */
    public function deliveries(Request $request, int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('user_id', $request->user()->id)
            ->find($id);

        if (!$subscription) {
            return $this->notFound('Webhook subscription not found');
        }

        $deliveries = $subscription->deliveries()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'event' => $d->event,
                'status' => $d->status,
                'response_status' => $d->response_status,
                'duration_ms' => $d->duration_ms,
                'attempts' => $d->attempts,
                'created_at' => $d->created_at->toIso8601String(),
            ]);

        return $this->success($deliveries, 'Delivery history retrieved');
    }

    /**
     * Transform subscription for API response
     */
    protected function transformSubscription(WebhookSubscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'name' => $subscription->name,
            'url' => $subscription->url,
            'events' => $subscription->events,
            'is_active' => $subscription->is_active,
            'secret' => $subscription->secret ? '***' . substr($subscription->secret, -4) : null,
            'stats' => [
                'success_count' => $subscription->success_count,
                'failure_count' => $subscription->failure_count,
                'last_triggered_at' => $subscription->last_triggered_at?->toIso8601String(),
                'last_error' => $subscription->last_error,
            ],
            'created_at' => $subscription->created_at->toIso8601String(),
            'updated_at' => $subscription->updated_at->toIso8601String(),
        ];
    }
}
