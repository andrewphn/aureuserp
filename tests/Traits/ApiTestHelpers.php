<?php

namespace Tests\Traits;

use Laravel\Sanctum\Sanctum;
use Webkul\Security\Models\User;

trait ApiTestHelpers
{
    protected User $apiUser;
    protected string $apiToken;

    /**
     * Set up API authentication for tests
     */
    protected function setUpApiAuth(array $abilities = ['*']): void
    {
        $this->apiUser = User::factory()->create();
        Sanctum::actingAs($this->apiUser, $abilities);
    }

    /**
     * Make authenticated API request
     */
    protected function apiGet(string $uri, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->getJson("/api/v1{$uri}", $headers);
    }

    /**
     * Make authenticated API POST request
     */
    protected function apiPost(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson("/api/v1{$uri}", $data, $headers);
    }

    /**
     * Make authenticated API PUT request
     */
    protected function apiPut(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->putJson("/api/v1{$uri}", $data, $headers);
    }

    /**
     * Make authenticated API PATCH request
     */
    protected function apiPatch(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->patchJson("/api/v1{$uri}", $data, $headers);
    }

    /**
     * Make authenticated API DELETE request
     */
    protected function apiDelete(string $uri, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->deleteJson("/api/v1{$uri}", [], $headers);
    }

    /**
     * Assert successful API response
     */
    protected function assertApiSuccess(\Illuminate\Testing\TestResponse $response, int $statusCode = 200): void
    {
        $response->assertStatus($statusCode)
            ->assertJson(['success' => true]);
    }

    /**
     * Assert API error response
     */
    protected function assertApiError(\Illuminate\Testing\TestResponse $response, int $statusCode): void
    {
        $response->assertStatus($statusCode)
            ->assertJson(['success' => false]);
    }

    /**
     * Assert paginated response structure
     */
    protected function assertPaginatedResponse(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'pagination' => [
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages',
                'links' => [
                    'first',
                    'last',
                    'next',
                    'previous',
                ],
            ],
            'timestamp',
        ]);
    }
}
