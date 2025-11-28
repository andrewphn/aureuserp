<?php

namespace Webkul\Project\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Project\Models\PdfPage;
use Webkul\Project\Models\PdfDocument;
use Webkul\Project\Models\PdfPageAnnotation;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;

/**
 * Annotation Save Integration Test test case
 *
 */
class AnnotationSaveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $project;
    protected $pdfDocument;
    protected $pdfPage;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user first (required for migrations)
        \App\Models\User::factory()->create([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@test.com',
        ]);

        // Create test project
        $this->project = \Webkul\Project\Models\Project::factory()->create([
            'name' => 'Integration Test Project',
        ]);

        // Create PDF document
        $this->pdfDocument = PdfDocument::create([
            'module_type' => 'Webkul\Project\Models\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/path/document.pdf',
            'original_filename' => 'test.pdf',
            'page_count' => 1,
        ]);

        // Create PDF page
        $this->pdfPage = PdfPage::create([
            'pdf_document_id' => $this->pdfDocument->id,
            'page_number' => 1,
        ]);
    }

    /** @test */
    public function it_saves_annotation_and_creates_room_entity()
    {
        // Prepare InstantJSON payload with room annotation
        $instantJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'test-annotation-1',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [100, 200, 300, 400],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'room',
                        'label' => 'Main Kitchen',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_name' => 'Main Kitchen',
                            'page_number' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Send save request with entity creation enabled
        $response = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$instantJson,
            'create_entities' => true,
        ]);

        // Assert response
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'saved_count' => 1,
        ]);

        // Verify annotation was saved
        $this->assertDatabaseHas('pdf_page_annotations', [
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'label' => 'Main Kitchen',
        ]);

        // Verify room entity was created
        $this->assertDatabaseHas('projects_rooms', [
            'project_id' => $this->project->id,
            'name' => 'Main Kitchen',
        ]);

        // Verify annotation is linked to room
        $annotation = PdfPageAnnotation::where('label', 'Main Kitchen')->first();
        $this->assertNotNull($annotation->room_id);

        // Verify created_entities in response
        $createdEntities = $response->json('created_entities');
        $this->assertCount(1, $createdEntities);
        $this->assertEquals('room', $createdEntities[0]['type']);
        $this->assertEquals('Main Kitchen', $createdEntities[0]['name']);
    }

    /** @test */
    public function it_saves_annotation_and_creates_room_location_entity()
    {
        // Create parent room first
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        // Prepare InstantJSON with room location annotation
        $instantJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'test-annotation-2',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [100, 200, 300, 400],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'room_location',
                        'label' => 'North Wall',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_id' => $room->id,
                            'location_name' => 'North Wall',
                            'page_number' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Send save request
        $response = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$instantJson,
            'create_entities' => true,
        ]);

        // Assert response
        $response->assertOk();
        $response->assertJson(['success' => true, 'saved_count' => 1]);

        // Verify room location was created
        $this->assertDatabaseHas('projects_room_locations', [
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        // Verify annotation is linked
        $annotation = PdfPageAnnotation::where('label', 'North Wall')->first();
        $this->assertNotNull($annotation->room_location_id);

        // Verify created_entities
        $createdEntities = $response->json('created_entities');
        $this->assertCount(1, $createdEntities);
        $this->assertEquals('room_location', $createdEntities[0]['type']);
    }

    /** @test */
    public function it_saves_annotation_and_creates_cabinet_run_entity()
    {
        // Create hierarchy
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        // Prepare InstantJSON with cabinet run annotation
        $instantJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'test-annotation-3',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [100, 200, 300, 400],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'cabinet_run',
                        'label' => 'Upper Cabinets',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_id' => $room->id,
                            'room_location_id' => $location->id,
                            'run_name' => 'Upper Cabinets',
                            'page_number' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Send save request
        $response = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$instantJson,
            'create_entities' => true,
        ]);

        // Assert response
        $response->assertOk();
        $response->assertJson(['success' => true, 'saved_count' => 1]);

        // Verify cabinet run was created
        $this->assertDatabaseHas('projects_cabinet_runs', [
            'room_location_id' => $location->id,
            'name' => 'Upper Cabinets',
        ]);

        // Verify annotation is linked
        $annotation = PdfPageAnnotation::where('label', 'Upper Cabinets')->first();
        $this->assertNotNull($annotation->cabinet_run_id);
    }

    /** @test */
    public function it_saves_multiple_annotations_with_mixed_entity_creation()
    {
        // Create parent entities
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        // Prepare InstantJSON with multiple annotations
        $instantJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'annotation-room',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [100, 200, 300, 400],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'room',
                        'label' => 'Bathroom',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_name' => 'Bathroom',
                            'page_number' => 1,
                        ],
                    ],
                ],
                [
                    'id' => 'annotation-location',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [400, 200, 600, 400],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'room_location',
                        'label' => 'South Wall',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_id' => $room->id,
                            'location_name' => 'South Wall',
                            'page_number' => 1,
                        ],
                    ],
                ],
                [
                    'id' => 'annotation-run',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [100, 500, 300, 700],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'cabinet_run',
                        'label' => 'Lower Cabinets',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_id' => $room->id,
                            'room_location_id' => $location->id,
                            'run_name' => 'Lower Cabinets',
                            'page_number' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Send save request
        $response = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$instantJson,
            'create_entities' => true,
        ]);

        // Assert response
        $response->assertOk();
        $response->assertJson(['success' => true, 'saved_count' => 3]);

        // Verify all entities were created
        $this->assertDatabaseHas('projects_rooms', ['name' => 'Bathroom']);
        $this->assertDatabaseHas('projects_room_locations', ['name' => 'South Wall']);
        $this->assertDatabaseHas('projects_cabinet_runs', ['name' => 'Lower Cabinets']);

        // Verify created_entities contains all 3
        $createdEntities = $response->json('created_entities');
        $this->assertCount(3, $createdEntities);
    }

    /** @test */
    public function it_does_not_create_entities_when_flag_is_false()
    {
        // Prepare InstantJSON with room annotation
        $instantJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'test-annotation-no-create',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [100, 200, 300, 400],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'room',
                        'label' => 'Kitchen',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_name' => 'Kitchen',
                            'page_number' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Send save request WITHOUT entity creation
        $response = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$instantJson,
            'create_entities' => false,
        ]);

        // Assert response
        $response->assertOk();
        $response->assertJson(['success' => true, 'saved_count' => 1]);

        // Verify annotation was saved
        $this->assertDatabaseHas('pdf_page_annotations', [
            'label' => 'Kitchen',
        ]);

        // Verify room entity was NOT created
        $this->assertDatabaseMissing('projects_rooms', [
            'name' => 'Kitchen',
        ]);

        // Verify no created_entities in response
        $this->assertEmpty($response->json('created_entities'));
    }

    /** @test */
    public function it_handles_errors_gracefully_and_continues_processing()
    {
        // Prepare InstantJSON with valid and invalid annotations
        $instantJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'valid-annotation',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [100, 200, 300, 400],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'room',
                        'label' => 'Valid Room',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_name' => 'Valid Room',
                            'page_number' => 1,
                        ],
                    ],
                ],
                [
                    'id' => 'invalid-annotation',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [400, 200, 600, 400],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'cabinet_run',
                        'label' => 'Invalid Run',
                        'context' => [
                            'project_id' => $this->project->id,
                            // Missing required room_location_id - should fail
                            'run_name' => 'Invalid Run',
                            'page_number' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Send save request
        $response = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$instantJson,
            'create_entities' => true,
        ]);

        // Should still succeed for valid annotation
        $response->assertOk();
        $response->assertJson(['success' => true, 'saved_count' => 2]);

        // Verify valid room was created
        $this->assertDatabaseHas('projects_rooms', ['name' => 'Valid Room']);

        // Verify invalid cabinet run was NOT created
        $this->assertDatabaseMissing('projects_cabinet_runs', ['name' => 'Invalid Run']);

        // Verify only 1 entity was created
        $createdEntities = $response->json('created_entities');
        $this->assertCount(1, $createdEntities);
    }
}
