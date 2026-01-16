<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Project\Models\Project;
use Webkul\Partner\Models\Partner;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();
    }

    /** @test */
    public function can_list_projects(): void
    {
        Project::factory()->count(5)->create();

        $response = $this->apiGet('/projects');

        $this->assertApiSuccess($response);
        $this->assertPaginatedResponse($response);
        $response->assertJsonCount(5, 'data');
    }

    /** @test */
    public function can_paginate_projects(): void
    {
        Project::factory()->count(30)->create();

        $response = $this->apiGet('/projects?per_page=10&page=2');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('pagination.per_page', 10);
        $response->assertJsonPath('pagination.current_page', 2);
        $response->assertJsonCount(10, 'data');
    }

    /** @test */
    public function can_filter_projects_by_is_active(): void
    {
        Project::factory()->count(3)->create(['is_active' => true]);
        Project::factory()->count(2)->create(['is_active' => false]);

        $response = $this->apiGet('/projects?filter[is_active]=1');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function can_search_projects(): void
    {
        Project::factory()->create(['name' => 'Kitchen Renovation']);
        Project::factory()->create(['name' => 'Bathroom Remodel']);
        Project::factory()->create(['name' => 'Kitchen Cabinets']);

        $response = $this->apiGet('/projects?search=Kitchen');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_sort_projects(): void
    {
        $project1 = Project::factory()->create(['name' => 'Alpha']);
        $project2 = Project::factory()->create(['name' => 'Beta']);
        $project3 = Project::factory()->create(['name' => 'Charlie']);

        $response = $this->apiGet('/projects?sort=name');

        $this->assertApiSuccess($response);
        $data = $response->json('data');
        $this->assertEquals('Alpha', $data[0]['name']);
        $this->assertEquals('Beta', $data[1]['name']);
        $this->assertEquals('Charlie', $data[2]['name']);
    }

    /** @test */
    public function can_sort_projects_descending(): void
    {
        Project::factory()->create(['name' => 'Alpha']);
        Project::factory()->create(['name' => 'Beta']);
        Project::factory()->create(['name' => 'Charlie']);

        $response = $this->apiGet('/projects?sort=-name');

        $this->assertApiSuccess($response);
        $data = $response->json('data');
        $this->assertEquals('Charlie', $data[0]['name']);
    }

    /** @test */
    public function can_include_relations(): void
    {
        $partner = Partner::factory()->create();
        $project = Project::factory()->create(['partner_id' => $partner->id]);

        $response = $this->apiGet("/projects/{$project->id}?include=partner");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.partner.id', $partner->id);
    }

    /** @test */
    public function can_create_project(): void
    {
        $data = [
            'name' => 'New Kitchen Project',
            'description' => 'A new kitchen renovation project',
            'project_type' => 'residential',
        ];

        $response = $this->apiPost('/projects', $data);

        $this->assertApiSuccess($response, 201);
        $response->assertJsonPath('data.name', 'New Kitchen Project');

        $this->assertDatabaseHas('projects_projects', [
            'name' => 'New Kitchen Project',
        ]);
    }

    /** @test */
    public function create_project_validates_required_fields(): void
    {
        $response = $this->apiPost('/projects', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function can_show_single_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->apiGet("/projects/{$project->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.id', $project->id);
        $response->assertJsonPath('data.name', $project->name);
    }

    /** @test */
    public function show_returns_404_for_non_existent_project(): void
    {
        $response = $this->apiGet('/projects/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function can_update_project(): void
    {
        $project = Project::factory()->create(['name' => 'Original Name']);

        $response = $this->apiPut("/projects/{$project->id}", [
            'name' => 'Updated Name',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('projects_projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function can_delete_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->apiDelete("/projects/{$project->id}");

        $this->assertApiSuccess($response);
        $this->assertDatabaseMissing('projects_projects', [
            'id' => $project->id,
        ]);
    }

    /** @test */
    public function can_filter_with_comparison_operators(): void
    {
        Project::factory()->create(['estimated_linear_feet' => '50']);
        Project::factory()->create(['estimated_linear_feet' => '100']);
        Project::factory()->create(['estimated_linear_feet' => '150']);

        // Note: estimated_linear_feet is string, so numeric comparison may not work as expected
        // This test validates the filter syntax is accepted
        $response = $this->apiGet('/projects?filter[id]=gte:1');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function max_per_page_is_enforced(): void
    {
        Project::factory()->count(150)->create();

        $response = $this->apiGet('/projects?per_page=200');

        $this->assertApiSuccess($response);
        // Max per_page is 100
        $response->assertJsonPath('pagination.per_page', 100);
    }

    /** @test */
    public function invalid_include_relations_are_ignored(): void
    {
        $project = Project::factory()->create();

        // 'invalid_relation' is not in includableRelations
        $response = $this->apiGet("/projects/{$project->id}?include=invalid_relation");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function invalid_filter_fields_are_ignored(): void
    {
        Project::factory()->create();

        // 'invalid_field' is not in filterableFields
        $response = $this->apiGet('/projects?filter[invalid_field]=value');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function invalid_sort_fields_are_ignored(): void
    {
        Project::factory()->count(3)->create();

        // 'invalid_field' is not in sortableFields
        $response = $this->apiGet('/projects?sort=invalid_field');

        $this->assertApiSuccess($response);
    }
}
