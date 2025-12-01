<?php

namespace Webkul\Project\Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Project\Models\PdfPage;
use Webkul\Project\Models\PdfDocument;
use Webkul\Project\Models\PdfPageAnnotation;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;

/**
 * End-to-End Test for Multi-Pass PDF Annotation System
 *
 * This test simulates a complete user workflow:
 * 1. Load PDF page with context API
 * 2. Create hierarchical annotations (room → location → run → cabinet)
 * 3. Save annotations with automatic entity creation
 * 4. Verify all entities were created and linked correctly
 * 5. Verify cascade filtering logic works
 */
class PdfAnnotationEndToEndTest extends TestCase
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
            'name' => 'E2E Test Project - TCS Woodwork',
        ]);

        // Create PDF document for the project
        $this->pdfDocument = PdfDocument::create([
            'module_type' => 'Webkul\Project\Models\Project',
            'module_id' => $this->project->id,
            'file_path' => 'projects/floorplans/kitchen-plan.pdf',
            'original_filename' => 'kitchen-plan.pdf',
            'page_count' => 3,
        ]);

        // Create PDF page
        $this->pdfPage = PdfPage::create([
            'pdf_document_id' => $this->pdfDocument->id,
            'page_number' => 1,
        ]);
    }

    /** @test */
    public function complete_annotation_workflow_from_empty_project()
    {
        // ========================================
        // STEP 1: Load Context API (empty state)
        // ========================================
        $contextResponse = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $contextResponse->assertOk();
        $contextResponse->assertJson([
            'success' => true,
            'context' => [
                'project_id' => $this->project->id,
                'rooms' => [],
                'room_locations' => [],
                'cabinet_runs' => [],
                'cabinets' => [],
            ],
        ]);

        // ========================================
        // STEP 2: User creates ROOM annotation
        // ========================================
        $roomAnnotationJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'room-kitchen',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [50, 100, 500, 600],
                    'pageIndex' => 0,
                    'strokeColor' => ['r' => 0, 'g' => 128, 'b' => 0],
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

        $saveResponse1 = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$roomAnnotationJson,
            'create_entities' => true,
        ]);

        $saveResponse1->assertOk();
        $saveResponse1->assertJson([
            'success' => true,
            'saved_count' => 1,
        ]);

        // Verify room entity created
        $this->assertDatabaseHas('projects_rooms', [
            'project_id' => $this->project->id,
            'name' => 'Main Kitchen',
        ]);

        $room = Room::where('name', 'Main Kitchen')->first();
        $this->assertNotNull($room);

        // Verify created_entities feedback
        $createdEntities1 = $saveResponse1->json('created_entities');
        $this->assertCount(1, $createdEntities1);
        $this->assertEquals('room', $createdEntities1[0]['type']);
        $this->assertEquals('Main Kitchen', $createdEntities1[0]['name']);

        // ========================================
        // STEP 3: Reload Context API (now has room)
        // ========================================
        $contextResponse2 = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $contextResponse2->assertOk();
        $contextResponse2->assertJsonCount(1, 'context.rooms');
        $contextResponse2->assertJson([
            'context' => [
                'rooms' => [
                    ['id' => $room->id, 'name' => 'Main Kitchen'],
                ],
            ],
        ]);

        // ========================================
        // STEP 4: User creates ROOM LOCATION annotation
        // (Frontend would filter locations by selected room)
        // ========================================
        $locationAnnotationJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'location-north-wall',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [60, 110, 240, 300],
                    'pageIndex' => 0,
                    'strokeColor' => ['r' => 0, 'g' => 0, 'b' => 255],
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

        $saveResponse2 = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$locationAnnotationJson,
            'create_entities' => true,
        ]);

        $saveResponse2->assertOk();

        // Verify room location created
        $this->assertDatabaseHas('projects_room_locations', [
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        $location = RoomLocation::where('name', 'North Wall')->first();
        $this->assertNotNull($location);

        // ========================================
        // STEP 5: User creates CABINET RUN annotation
        // ========================================
        $runAnnotationJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'run-upper-cabinets',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [70, 120, 230, 200],
                    'pageIndex' => 0,
                    'strokeColor' => ['r' => 255, 'g' => 165, 'b' => 0],
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

        $saveResponse3 = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$runAnnotationJson,
            'create_entities' => true,
        ]);

        $saveResponse3->assertOk();

        // Verify cabinet run created
        $this->assertDatabaseHas('projects_cabinet_runs', [
            'room_location_id' => $location->id,
            'name' => 'Upper Cabinets',
        ]);

        $run = CabinetRun::where('name', 'Upper Cabinets')->first();
        $this->assertNotNull($run);

        // ========================================
        // STEP 6: User creates CABINET annotation
        // ========================================
        $cabinetAnnotationJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'cabinet-w3018',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [80, 130, 150, 190],
                    'pageIndex' => 0,
                    'strokeColor' => ['r' => 255, 'g' => 0, 'b' => 0],
                    'customData' => [
                        'annotation_type' => 'cabinet',
                        'label' => 'W3018',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_id' => $room->id,
                            'room_location_id' => $location->id,
                            'cabinet_run_id' => $run->id,
                            'cabinet_label' => 'W3018',
                            'page_number' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $saveResponse4 = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$cabinetAnnotationJson,
            'create_entities' => true,
        ]);

        $saveResponse4->assertOk();

        // Verify cabinet created
        $this->assertDatabaseHas('projects_cabinets', [
            'cabinet_run_id' => $run->id,
            'label' => 'W3018',
        ]);

        $cabinet = Cabinet::where('label', 'W3018')->first();
        $this->assertNotNull($cabinet);

        // ========================================
        // STEP 7: Final Context API Load
        // (Verify all entities appear in context)
        // ========================================
        $finalContextResponse = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $finalContextResponse->assertOk();
        $finalContextResponse->assertJsonCount(1, 'context.rooms');
        $finalContextResponse->assertJsonCount(1, 'context.room_locations');
        $finalContextResponse->assertJsonCount(1, 'context.cabinet_runs');
        $finalContextResponse->assertJsonCount(1, 'context.cabinets');

        // ========================================
        // STEP 8: Verify Full Hierarchy
        // ========================================
        $this->assertEquals($this->project->id, $room->project_id);
        $this->assertEquals($room->id, $location->room_id);
        $this->assertEquals($location->id, $run->room_location_id);
        $this->assertEquals($run->id, $cabinet->cabinet_run_id);

        // ========================================
        // STEP 9: Verify All Annotations Linked
        // ========================================
        $roomAnnotation = PdfPageAnnotation::where('label', 'Main Kitchen')->first();
        $this->assertEquals($room->id, $roomAnnotation->room_id);

        $locationAnnotation = PdfPageAnnotation::where('label', 'North Wall')->first();
        $this->assertEquals($location->id, $locationAnnotation->room_location_id);

        $runAnnotation = PdfPageAnnotation::where('label', 'Upper Cabinets')->first();
        $this->assertEquals($run->id, $runAnnotation->cabinet_run_id);

        $cabinetAnnotation = PdfPageAnnotation::where('label', 'W3018')->first();
        $this->assertEquals($cabinet->id, $cabinetAnnotation->cabinet_id);

        // ========================================
        // VERIFICATION COMPLETE ✅
        // ========================================
    }

    /** @test */
    public function it_handles_multiple_annotations_in_single_save()
    {
        // User draws multiple annotations at once and saves them all
        $multiAnnotationJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                // Room
                [
                    'id' => 'room-1',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [50, 100, 500, 600],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'room',
                        'label' => 'Living Room',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_name' => 'Living Room',
                            'page_number' => 1,
                        ],
                    ],
                ],
                // Another room
                [
                    'id' => 'room-2',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [550, 100, 1000, 600],
                    'pageIndex' => 0,
                    'customData' => [
                        'annotation_type' => 'room',
                        'label' => 'Dining Room',
                        'context' => [
                            'project_id' => $this->project->id,
                            'room_name' => 'Dining Room',
                            'page_number' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$multiAnnotationJson,
            'create_entities' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'saved_count' => 2]);

        // Verify both rooms created
        $this->assertDatabaseHas('projects_rooms', ['name' => 'Living Room']);
        $this->assertDatabaseHas('projects_rooms', ['name' => 'Dining Room']);

        // Verify created_entities has both
        $createdEntities = $response->json('created_entities');
        $this->assertCount(2, $createdEntities);
    }

    /** @test */
    public function it_prevents_duplicate_entity_creation_on_re_save()
    {
        // Save annotation with entity creation
        $annotationJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => [
                [
                    'id' => 'room-kitchen',
                    'type' => 'pspdfkit/shape/rectangle',
                    'bbox' => [50, 100, 500, 600],
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

        // First save
        $response1 = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$annotationJson,
            'create_entities' => true,
        ]);

        $response1->assertOk();
        $this->assertCount(1, $response1->json('created_entities'));

        // Second save (user modifies annotation and re-saves)
        $response2 = $this->postJson("/api/pdf/annotations/page/{$this->pdfPage->id}", [
            ...$annotationJson,
            'create_entities' => true,
        ]);

        $response2->assertOk();

        // Should only have 1 room in database, not 2
        $this->assertEquals(1, Room::where('name', 'Kitchen')->count());
    }

    /** @test */
    public function cascade_filtering_logic_verification()
    {
        // Create multiple rooms with locations and runs
        $room1 = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        $room2 = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Bathroom',
            'room_type' => 'bathroom',
        ]);

        $loc1 = RoomLocation::create(['room_id' => $room1->id, 'name' => 'North Wall']);
        $loc2 = RoomLocation::create(['room_id' => $room1->id, 'name' => 'South Wall']);
        $loc3 = RoomLocation::create(['room_id' => $room2->id, 'name' => 'East Wall']);

        $run1 = CabinetRun::create(['room_location_id' => $loc1->id, 'name' => 'Upper Cabinets']);
        $run2 = CabinetRun::create(['room_location_id' => $loc2->id, 'name' => 'Lower Cabinets']);
        $run3 = CabinetRun::create(['room_location_id' => $loc3->id, 'name' => 'Vanity']);

        // Load context
        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertOk();

        // Verify counts
        $this->assertCount(2, $response->json('context.rooms'));
        $this->assertCount(3, $response->json('context.room_locations'));
        $this->assertCount(3, $response->json('context.cabinet_runs'));

        // Verify room_locations have correct room_id (for filtering)
        $locations = $response->json('context.room_locations');
        $kitchenLocations = array_filter($locations, fn($l) => $l['room_id'] == $room1->id);
        $bathroomLocations = array_filter($locations, fn($l) => $l['room_id'] == $room2->id);

        $this->assertCount(2, $kitchenLocations); // North Wall, South Wall
        $this->assertCount(1, $bathroomLocations); // East Wall

        // Verify cabinet_runs have correct room_location_id (for filtering)
        $runs = $response->json('context.cabinet_runs');
        $northWallRuns = array_filter($runs, fn($r) => $r['room_location_id'] == $loc1->id);
        $southWallRuns = array_filter($runs, fn($r) => $r['room_location_id'] == $loc2->id);

        $this->assertCount(1, $northWallRuns); // Upper Cabinets
        $this->assertCount(1, $southWallRuns); // Lower Cabinets
    }
}
