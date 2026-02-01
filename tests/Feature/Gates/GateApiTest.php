<?php

namespace Tests\Feature\Gates;

use Tests\TestCase;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Security\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * API tests for gate status endpoints.
 */
class GateApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ProjectStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->stage = ProjectStage::factory()->create([
            'name' => 'Discovery',
            'stage_key' => 'discovery',
        ]);
    }

    /** @test */
    public function gate_status_endpoint_returns_gate_information()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
        ]);

        Room::factory()->create(['project_id' => $project->id]);

        $response = $this->getJson("/api/v1/projects/{$project->id}/gate-status");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [],
        ]);
    }

    /** @test */
    public function gate_status_endpoint_returns_404_for_nonexistent_project()
    {
        $response = $this->getJson('/api/v1/projects/999999/gate-status');

        $response->assertStatus(404);
    }

    /** @test */
    public function gate_status_endpoint_requires_authentication()
    {
        // Clear authentication
        $this->app['auth']->forgetGuards();

        $project = Project::factory()->create();

        $response = $this->getJson("/api/v1/projects/{$project->id}/gate-status");

        $response->assertStatus(401);
    }
}
