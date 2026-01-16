<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Partner\Models\Partner;

class PartnerApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();
    }

    /** @test */
    public function can_list_partners(): void
    {
        Partner::factory()->count(5)->create();

        $response = $this->apiGet('/partners');

        $this->assertApiSuccess($response);
        $this->assertPaginatedResponse($response);
    }

    /** @test */
    public function can_paginate_partners(): void
    {
        Partner::factory()->count(30)->create();

        $response = $this->apiGet('/partners?per_page=10&page=2');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('pagination.per_page', 10);
        $response->assertJsonPath('pagination.current_page', 2);
    }

    /** @test */
    public function can_create_partner(): void
    {
        $response = $this->apiPost('/partners', [
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            'phone' => '555-1234',
            'sub_type' => 'customer',
        ]);

        $this->assertApiSuccess($response, 201);
        $response->assertJsonPath('data.name', 'Acme Corp');
    }

    /** @test */
    public function can_show_partner(): void
    {
        $partner = Partner::factory()->create();

        $response = $this->apiGet("/partners/{$partner->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.id', $partner->id);
    }

    /** @test */
    public function show_returns_404_for_non_existent_partner(): void
    {
        $response = $this->apiGet('/partners/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function can_update_partner(): void
    {
        $partner = Partner::factory()->create(['name' => 'Old Company']);

        $response = $this->apiPut("/partners/{$partner->id}", [
            'name' => 'New Company',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.name', 'New Company');
    }

    /** @test */
    public function can_delete_partner(): void
    {
        $partner = Partner::factory()->create();

        $response = $this->apiDelete("/partners/{$partner->id}");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_search_partners(): void
    {
        Partner::factory()->create(['name' => 'Acme Corporation']);
        Partner::factory()->create(['name' => 'Beta Industries']);
        Partner::factory()->create(['name' => 'Acme Tools']);

        $response = $this->apiGet('/partners?search=Acme');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_partners_by_sub_type(): void
    {
        Partner::factory()->count(2)->create(['sub_type' => 'customer']);
        Partner::factory()->count(3)->create(['sub_type' => 'vendor']);

        $response = $this->apiGet('/partners?filter[sub_type]=customer');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_partners_by_is_company(): void
    {
        Partner::factory()->count(2)->create(['is_company' => true]);
        Partner::factory()->count(3)->create(['is_company' => false]);

        $response = $this->apiGet('/partners?filter[is_company]=1');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_sort_partners_by_name(): void
    {
        Partner::factory()->create(['name' => 'Charlie']);
        Partner::factory()->create(['name' => 'Alpha']);
        Partner::factory()->create(['name' => 'Beta']);

        $response = $this->apiGet('/partners?sort=name');

        $this->assertApiSuccess($response);
        $data = $response->json('data');
        $this->assertEquals('Alpha', $data[0]['name']);
    }

    /** @test */
    public function can_include_projects_relation(): void
    {
        $partner = Partner::factory()->create();

        $response = $this->apiGet("/partners/{$partner->id}?include=projects");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_include_contacts_relation(): void
    {
        $partner = Partner::factory()->create(['is_company' => true]);

        $response = $this->apiGet("/partners/{$partner->id}?include=contacts");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function create_partner_validates_email_format(): void
    {
        $response = $this->apiPost('/partners', [
            'name' => 'Test Partner',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
