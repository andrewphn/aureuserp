<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // Retries handled by WebhookDelivery

    public int $timeout = 30;

    public function __construct(
        public string $event,
        public array $data
    ) {}

    public function handle(): void
    {
        // Find all active subscriptions for this event
        $subscriptions = WebhookSubscription::forEvent($this->event)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        foreach ($subscriptions as $subscription) {
            $this->deliverToSubscription($subscription);
        }
    }

    protected function deliverToSubscription(WebhookSubscription $subscription): void
    {
        // Build payload
        $payload = [
            'event' => $this->event,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String(),
            'webhook_id' => $subscription->id,
        ];

        $jsonPayload = json_encode($payload);

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_subscription_id' => $subscription->id,
            'event' => $this->event,
            'payload' => $payload,
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        // Build headers
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'AureusERP-Webhook/1.0',
            'X-Webhook-Event' => $this->event,
            'X-Webhook-Delivery' => (string) $delivery->id,
            'X-Webhook-Signature' => $subscription->generateSignature($jsonPayload),
        ];

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->post($subscription->url, $payload);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $delivery->markSuccess(
                    $response->status(),
                    $response->body(),
                    $durationMs
                );
            } else {
                $delivery->markFailed(
                    $response->status(),
                    $response->body(),
                    $durationMs
                );
            }
        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::error('Webhook delivery failed', [
                'subscription_id' => $subscription->id,
                'event' => $this->event,
                'error' => $e->getMessage(),
            ]);

            $delivery->markFailed(
                0,
                $e->getMessage(),
                $durationMs
            );
        }
    }
}
