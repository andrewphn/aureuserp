<?php

namespace Tests\Feature\Api\V1;

use App\Models\WebhookSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

class WebhookApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();
    }

    /** @test */
    public function can_subscribe_to_webhook(): void
    {
        $response = $this->apiPost('/webhooks/subscribe', [
            'url' => 'https://n8n.example.com/webhook/erp',
            'events' => ['project.created', 'project.updated'],
            'secret' => 'webhook-secret-key',
        ]);

        $this->assertApiSuccess($response, 201);
        $response->assertJsonStructure([
            'data' => ['id', 'url', 'events', 'is_active', 'created_at'],
        ]);

        $this->assertDatabaseHas('webhook_subscriptions', [
            'url' => 'https://n8n.example.com/webhook/erp',
        ]);
    }

    /** @test */
    public function webhook_subscription_requires_url(): void
    {
        $response = $this->apiPost('/webhooks/subscribe', [
            'events' => ['project.created'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function webhook_subscription_requires_events(): void
    {
        $response = $this->apiPost('/webhooks/subscribe', [
            'url' => 'https://example.com/webhook',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['events']);
    }

    /** @test */
    public function webhook_subscription_validates_url_format(): void
    {
        $response = $this->apiPost('/webhooks/subscribe', [
            'url' => 'not-a-valid-url',
            'events' => ['project.created'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function can_list_webhook_subscriptions(): void
    {
        WebhookSubscription::factory()->count(3)->create(['user_id' => $this->apiUser->id]);

        $response = $this->apiGet('/webhooks');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function only_lists_own_subscriptions(): void
    {
        WebhookSubscription::factory()->count(2)->create(['user_id' => $this->apiUser->id]);
        WebhookSubscription::factory()->count(3)->create(); // Other user's subscriptions

        $response = $this->apiGet('/webhooks');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_show_webhook_subscription(): void
    {
        $subscription = WebhookSubscription::factory()->create(['user_id' => $this->apiUser->id]);

        $response = $this->apiGet("/webhooks/{$subscription->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.id', $subscription->id);
    }

    /** @test */
    public function cannot_show_other_users_subscription(): void
    {
        $subscription = WebhookSubscription::factory()->create(); // Different user

        $response = $this->apiGet("/webhooks/{$subscription->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function can_update_webhook_subscription(): void
    {
        $subscription = WebhookSubscription::factory()->create([
            'user_id' => $this->apiUser->id,
            'url' => 'https://old-url.com/webhook',
        ]);

        $response = $this->apiPut("/webhooks/{$subscription->id}", [
            'url' => 'https://new-url.com/webhook',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.url', 'https://new-url.com/webhook');
    }

    /** @test */
    public function can_unsubscribe_from_webhook(): void
    {
        $subscription = WebhookSubscription::factory()->create(['user_id' => $this->apiUser->id]);

        $response = $this->apiDelete("/webhooks/{$subscription->id}");

        $this->assertApiSuccess($response);
        $this->assertDatabaseMissing('webhook_subscriptions', ['id' => $subscription->id]);
    }

    /** @test */
    public function cannot_delete_other_users_subscription(): void
    {
        $subscription = WebhookSubscription::factory()->create(); // Different user

        $response = $this->apiDelete("/webhooks/{$subscription->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('webhook_subscriptions', ['id' => $subscription->id]);
    }

    /** @test */
    public function can_test_webhook_subscription(): void
    {
        $subscription = WebhookSubscription::factory()->create(['user_id' => $this->apiUser->id]);

        $response = $this->apiPost("/webhooks/{$subscription->id}/test");

        // This may fail if the webhook URL doesn't exist, but the endpoint should work
        $response->assertStatus(200);
    }

    /** @test */
    public function can_subscribe_with_wildcard_events(): void
    {
        $response = $this->apiPost('/webhooks/subscribe', [
            'url' => 'https://example.com/webhook',
            'events' => ['project.*', 'cabinet.*'],
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function webhook_deliveries_are_logged(): void
    {
        $subscription = WebhookSubscription::factory()->create(['user_id' => $this->apiUser->id]);

        $response = $this->apiGet("/webhooks/{$subscription->id}/deliveries");

        $this->assertApiSuccess($response);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function can_toggle_webhook_active_status(): void
    {
        $subscription = WebhookSubscription::factory()->create([
            'user_id' => $this->apiUser->id,
            'is_active' => true,
        ]);

        $response = $this->apiPut("/webhooks/{$subscription->id}", [
            'is_active' => false,
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.is_active', false);
    }
}
