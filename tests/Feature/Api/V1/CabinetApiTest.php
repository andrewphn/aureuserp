<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;

class CabinetApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected Project $project;
    protected Room $room;
    protected ?RoomLocation $location;
    protected CabinetRun $cabinetRun;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();

        $this->project = Project::factory()->create();
        $this->room = Room::factory()->create(['project_id' => $this->project->id]);

        // Create location if model exists
        if (class_exists(RoomLocation::class)) {
            $this->location = RoomLocation::factory()->create(['room_id' => $this->room->id]);
            $this->cabinetRun = CabinetRun::factory()->create(['room_location_id' => $this->location->id]);
        } else {
            $this->cabinetRun = CabinetRun::factory()->create(['room_id' => $this->room->id]);
        }
    }

    /** @test */
    public function can_list_cabinets_for_cabinet_run(): void
    {
        Cabinet::factory()->count(3)->create(['cabinet_run_id' => $this->cabinetRun->id]);

        $response = $this->apiGet("/cabinet-runs/{$this->cabinetRun->id}/cabinets");

        $this->assertApiSuccess($response);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function can_list_all_cabinets(): void
    {
        Cabinet::factory()->count(5)->create();

        $response = $this->apiGet('/cabinets');

        $this->assertApiSuccess($response);
        $this->assertPaginatedResponse($response);
    }

    /** @test */
    public function can_create_cabinet(): void
    {
        $data = [
            'cabinet_number' => 'B24-1',
            'length_inches' => 24.0,
            'depth_inches' => 24.0,
            'height_inches' => 34.75,
            'door_count' => 2,
        ];

        $response = $this->apiPost("/cabinet-runs/{$this->cabinetRun->id}/cabinets", $data);

        $this->assertApiSuccess($response, 201);
        $response->assertJsonPath('data.cabinet_number', 'B24-1');
    }

    /** @test */
    public function can_show_single_cabinet(): void
    {
        $cabinet = Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id]);

        $response = $this->apiGet("/cabinets/{$cabinet->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.id', $cabinet->id);
    }

    /** @test */
    public function can_update_cabinet(): void
    {
        $cabinet = Cabinet::factory()->create([
            'cabinet_run_id' => $this->cabinetRun->id,
            'length_inches' => 24.0,
        ]);

        $response = $this->apiPut("/cabinets/{$cabinet->id}", [
            'length_inches' => 30.0,
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.length_inches', 30.0);
    }

    /** @test */
    public function can_delete_cabinet(): void
    {
        $cabinet = Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id]);

        $response = $this->apiDelete("/cabinets/{$cabinet->id}");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_filter_cabinets_by_door_count(): void
    {
        Cabinet::factory()->count(2)->create(['cabinet_run_id' => $this->cabinetRun->id, 'door_count' => 1]);
        Cabinet::factory()->count(3)->create(['cabinet_run_id' => $this->cabinetRun->id, 'door_count' => 2]);

        $response = $this->apiGet('/cabinets?filter[door_count]=2');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function can_search_cabinets_by_number(): void
    {
        Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id, 'cabinet_number' => 'B24-1']);
        Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id, 'cabinet_number' => 'B30-2']);
        Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id, 'cabinet_number' => 'U24-1']);

        $response = $this->apiGet('/cabinets?search=B24');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function can_include_cabinet_run_relation(): void
    {
        $cabinet = Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id]);

        $response = $this->apiGet("/cabinets/{$cabinet->id}?include=cabinetRun");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_include_sections_relation(): void
    {
        $cabinet = Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id]);

        $response = $this->apiGet("/cabinets/{$cabinet->id}?include=sections");

        $this->assertApiSuccess($response);
        $response->assertJsonStructure(['data' => ['sections']]);
    }

    /** @test */
    public function can_include_drawers_relation(): void
    {
        $cabinet = Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id]);

        $response = $this->apiGet("/cabinets/{$cabinet->id}?include=drawers");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_include_doors_relation(): void
    {
        $cabinet = Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id]);

        $response = $this->apiGet("/cabinets/{$cabinet->id}?include=doors");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_sort_cabinets_by_length(): void
    {
        Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id, 'length_inches' => 24.0]);
        Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id, 'length_inches' => 30.0]);
        Cabinet::factory()->create(['cabinet_run_id' => $this->cabinetRun->id, 'length_inches' => 18.0]);

        $response = $this->apiGet('/cabinets?sort=length_inches');

        $this->assertApiSuccess($response);
        $data = $response->json('data');
        $this->assertEquals(18.0, $data[0]['length_inches']);
    }

    /** @test */
    public function cabinet_creation_validates_dimensions(): void
    {
        $response = $this->apiPost("/cabinet-runs/{$this->cabinetRun->id}/cabinets", [
            'cabinet_number' => 'TEST-1',
            'length_inches' => -10, // Invalid negative
        ]);

        $response->assertStatus(422);
    }
}
