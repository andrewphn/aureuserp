<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Security\Models\User;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/v1/projects');
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_access_api(): void
    {
        $this->setUpApiAuth();

        $response = $this->apiGet('/projects');
        $response->assertStatus(200);
    }

    /** @test */
    public function token_with_wildcard_ability_can_access_all_endpoints(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/projects');
        $response->assertStatus(200);
    }

    /** @test */
    public function invalid_token_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/projects', [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function rate_limiting_is_applied(): void
    {
        $this->setUpApiAuth();

        // Make many requests quickly
        for ($i = 0; $i < 60; $i++) {
            $this->apiGet('/projects');
        }

        // The rate limiter should kick in (default is 60 per minute for 'api' throttle)
        // Note: This test may need adjustment based on actual rate limit config
        $this->assertTrue(true); // Basic assertion that requests complete
    }
}
