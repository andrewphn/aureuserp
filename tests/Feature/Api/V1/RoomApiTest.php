<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;

class RoomApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();
        $this->project = Project::factory()->create();
    }

    /** @test */
    public function can_list_rooms_for_project(): void
    {
        Room::factory()->count(3)->create(['project_id' => $this->project->id]);

        $response = $this->apiGet("/projects/{$this->project->id}/rooms");

        $this->assertApiSuccess($response);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function can_list_all_rooms(): void
    {
        Room::factory()->count(5)->create();

        $response = $this->apiGet('/rooms');

        $this->assertApiSuccess($response);
        $this->assertPaginatedResponse($response);
    }

    /** @test */
    public function can_create_room_for_project(): void
    {
        $data = [
            'name' => 'Kitchen',
            'description' => 'Main kitchen area',
        ];

        $response = $this->apiPost("/projects/{$this->project->id}/rooms", $data);

        $this->assertApiSuccess($response, 201);
        $response->assertJsonPath('data.name', 'Kitchen');
        $response->assertJsonPath('data.project_id', $this->project->id);
    }

    /** @test */
    public function can_show_single_room(): void
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        $response = $this->apiGet("/rooms/{$room->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.id', $room->id);
    }

    /** @test */
    public function can_update_room(): void
    {
        $room = Room::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Original Room',
        ]);

        $response = $this->apiPut("/rooms/{$room->id}", [
            'name' => 'Updated Room',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.name', 'Updated Room');
    }

    /** @test */
    public function can_delete_room(): void
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        $response = $this->apiDelete("/rooms/{$room->id}");

        $this->assertApiSuccess($response);
        $this->assertDatabaseMissing('projects_rooms', ['id' => $room->id]);
    }

    /** @test */
    public function can_filter_rooms_by_project(): void
    {
        Room::factory()->count(2)->create(['project_id' => $this->project->id]);
        $otherProject = Project::factory()->create();
        Room::factory()->count(3)->create(['project_id' => $otherProject->id]);

        $response = $this->apiGet("/rooms?filter[project_id]={$this->project->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_search_rooms(): void
    {
        Room::factory()->create(['project_id' => $this->project->id, 'name' => 'Kitchen']);
        Room::factory()->create(['project_id' => $this->project->id, 'name' => 'Bathroom']);
        Room::factory()->create(['project_id' => $this->project->id, 'name' => 'Kitchen Nook']);

        $response = $this->apiGet('/rooms?search=Kitchen');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_include_project_relation(): void
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        $response = $this->apiGet("/rooms/{$room->id}?include=project");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.project.id', $this->project->id);
    }

    /** @test */
    public function can_include_locations_relation(): void
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        $response = $this->apiGet("/rooms/{$room->id}?include=locations");

        $this->assertApiSuccess($response);
        $response->assertJsonStructure(['data' => ['locations']]);
    }
}
