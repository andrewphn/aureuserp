<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Project\Models\Project;
use Webkul\Partner\Models\Partner;

class BatchApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();
    }

    /** @test */
    public function can_batch_create_resources(): void
    {
        $response = $this->apiPost('/batch/projects', [
            'operations' => [
                [
                    'method' => 'create',
                    'data' => [
                        'name' => 'Project 1',
                    ],
                ],
                [
                    'method' => 'create',
                    'data' => [
                        'name' => 'Project 2',
                    ],
                ],
            ],
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data.results');

        $this->assertDatabaseHas('projects_projects', ['name' => 'Project 1']);
        $this->assertDatabaseHas('projects_projects', ['name' => 'Project 2']);
    }

    /** @test */
    public function can_batch_update_resources(): void
    {
        $project1 = Project::factory()->create(['name' => 'Original 1']);
        $project2 = Project::factory()->create(['name' => 'Original 2']);

        $response = $this->apiPost('/batch/projects', [
            'operations' => [
                [
                    'method' => 'update',
                    'id' => $project1->id,
                    'data' => [
                        'name' => 'Updated 1',
                    ],
                ],
                [
                    'method' => 'update',
                    'id' => $project2->id,
                    'data' => [
                        'name' => 'Updated 2',
                    ],
                ],
            ],
        ]);

        $this->assertApiSuccess($response);

        $this->assertDatabaseHas('projects_projects', ['id' => $project1->id, 'name' => 'Updated 1']);
        $this->assertDatabaseHas('projects_projects', ['id' => $project2->id, 'name' => 'Updated 2']);
    }

    /** @test */
    public function can_batch_delete_resources(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $response = $this->apiPost('/batch/projects', [
            'operations' => [
                [
                    'method' => 'delete',
                    'id' => $project1->id,
                ],
                [
                    'method' => 'delete',
                    'id' => $project2->id,
                ],
            ],
        ]);

        $this->assertApiSuccess($response);

        $this->assertDatabaseMissing('projects_projects', ['id' => $project1->id]);
        $this->assertDatabaseMissing('projects_projects', ['id' => $project2->id]);
    }

    /** @test */
    public function can_batch_mixed_operations(): void
    {
        $existingProject = Project::factory()->create(['name' => 'Existing']);
        $projectToDelete = Project::factory()->create();

        $response = $this->apiPost('/batch/projects', [
            'operations' => [
                [
                    'method' => 'create',
                    'data' => [
                        'name' => 'New Project',
                    ],
                ],
                [
                    'method' => 'update',
                    'id' => $existingProject->id,
                    'data' => [
                        'name' => 'Updated Existing',
                    ],
                ],
                [
                    'method' => 'delete',
                    'id' => $projectToDelete->id,
                ],
            ],
        ]);

        $this->assertApiSuccess($response);

        $this->assertDatabaseHas('projects_projects', ['name' => 'New Project']);
        $this->assertDatabaseHas('projects_projects', ['id' => $existingProject->id, 'name' => 'Updated Existing']);
        $this->assertDatabaseMissing('projects_projects', ['id' => $projectToDelete->id]);
    }

    /** @test */
    public function batch_operation_validates_operations_array(): void
    {
        $response = $this->apiPost('/batch/projects', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['operations']);
    }

    /** @test */
    public function batch_operation_validates_method(): void
    {
        $response = $this->apiPost('/batch/projects', [
            'operations' => [
                [
                    'method' => 'invalid_method',
                    'data' => [],
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function batch_update_requires_id(): void
    {
        $response = $this->apiPost('/batch/projects', [
            'operations' => [
                [
                    'method' => 'update',
                    'data' => ['name' => 'Test'],
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function batch_delete_requires_id(): void
    {
        $response = $this->apiPost('/batch/projects', [
            'operations' => [
                [
                    'method' => 'delete',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function batch_returns_individual_results(): void
    {
        $project = Project::factory()->create();

        $response = $this->apiPost('/batch/projects', [
            'operations' => [
                [
                    'method' => 'create',
                    'data' => ['name' => 'New One'],
                ],
                [
                    'method' => 'update',
                    'id' => 99999, // Non-existent
                    'data' => ['name' => 'Update Failed'],
                ],
                [
                    'method' => 'delete',
                    'id' => $project->id,
                ],
            ],
        ]);

        // Should complete but with partial success
        $data = $response->json('data.results');

        // First operation should succeed
        $this->assertTrue($data[0]['success'] ?? false);

        // Second operation should fail (not found)
        $this->assertFalse($data[1]['success'] ?? true);

        // Third operation should succeed
        $this->assertTrue($data[2]['success'] ?? false);
    }

    /** @test */
    public function batch_limits_operations_per_request(): void
    {
        $operations = [];
        for ($i = 0; $i < 150; $i++) {
            $operations[] = [
                'method' => 'create',
                'data' => ['name' => "Project {$i}"],
            ];
        }

        $response = $this->apiPost('/batch/projects', [
            'operations' => $operations,
        ]);

        // Should either reject or limit to max (usually 100)
        $response->assertStatus(422); // Assuming validation rejects > 100
    }

    /** @test */
    public function batch_works_for_partners(): void
    {
        $response = $this->apiPost('/batch/partners', [
            'operations' => [
                [
                    'method' => 'create',
                    'data' => [
                        'name' => 'Partner 1',
                        'sub_type' => 'customer',
                    ],
                ],
                [
                    'method' => 'create',
                    'data' => [
                        'name' => 'Partner 2',
                        'sub_type' => 'vendor',
                    ],
                ],
            ],
        ]);

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function batch_returns_error_for_invalid_resource(): void
    {
        $response = $this->apiPost('/batch/invalid-resource', [
            'operations' => [
                ['method' => 'create', 'data' => []],
            ],
        ]);

        $response->assertStatus(404);
    }
}
