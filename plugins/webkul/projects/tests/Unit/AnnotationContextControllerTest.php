<?php

namespace Webkul\Project\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Project\Models\PdfPage;
use Webkul\Project\Models\PdfDocument;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;

/**
 * Annotation Context Controller Test test case
 *
 */
class AnnotationContextControllerTest extends TestCase
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
            'name' => 'Test Project',
        ]);

        // Create PDF document for the project
        $this->pdfDocument = PdfDocument::create([
            'module_type' => 'Webkul\Project\Models\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/path/document.pdf',
            'original_filename' => 'test.pdf',
            'page_count' => 3,
        ]);

        // Create PDF page
        $this->pdfPage = PdfPage::create([
            'pdf_document_id' => $this->pdfDocument->id,
            'page_number' => 1,
        ]);
    }

    /** @test */
    public function it_returns_empty_context_for_project_with_no_entities()
    {
        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'context' => [
                'project_id' => $this->project->id,
                'rooms' => [],
                'room_locations' => [],
                'cabinet_runs' => [],
                'cabinets' => [],
            ],
        ]);
    }

    /** @test */
    public function it_returns_rooms_for_project()
    {
        // Create test rooms
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

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'context' => [
                'project_id' => $this->project->id,
                'rooms' => [
                    ['id' => $room1->id, 'name' => 'Kitchen'],
                    ['id' => $room2->id, 'name' => 'Bathroom'],
                ],
            ],
        ]);

        $this->assertCount(2, $response->json('context.rooms'));
    }

    /** @test */
    public function it_returns_room_locations_for_project()
    {
        // Create room with locations
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        $location1 = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        $location2 = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'South Wall',
        ]);

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'context' => [
                'room_locations' => [
                    ['id' => $location1->id, 'name' => 'North Wall', 'room_id' => $room->id],
                    ['id' => $location2->id, 'name' => 'South Wall', 'room_id' => $room->id],
                ],
            ],
        ]);

        $this->assertCount(2, $response->json('context.room_locations'));
    }

    /** @test */
    public function it_returns_cabinet_runs_for_project()
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

        $run1 = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Upper Cabinets',
        ]);

        $run2 = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Lower Cabinets',
        ]);

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'context' => [
                'cabinet_runs' => [
                    ['id' => $run1->id, 'name' => 'Upper Cabinets', 'room_location_id' => $location->id],
                    ['id' => $run2->id, 'name' => 'Lower Cabinets', 'room_location_id' => $location->id],
                ],
            ],
        ]);

        $this->assertCount(2, $response->json('context.cabinet_runs'));
    }

    /** @test */
    public function it_returns_cabinets_for_project()
    {
        // Create full hierarchy
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        $run = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Upper Cabinets',
        ]);

        $cabinet1 = CabinetSpecification::create([
            'cabinet_run_id' => $run->id,
            'label' => 'W3018',
            'cabinet_type' => 'wall',
        ]);

        $cabinet2 = CabinetSpecification::create([
            'cabinet_run_id' => $run->id,
            'label' => 'W3612',
            'cabinet_type' => 'wall',
        ]);

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'context' => [
                'cabinets' => [
                    ['id' => $cabinet1->id, 'label' => 'W3018', 'cabinet_run_id' => $run->id],
                    ['id' => $cabinet2->id, 'label' => 'W3612', 'cabinet_run_id' => $run->id],
                ],
            ],
        ]);

        $this->assertCount(2, $response->json('context.cabinets'));
    }

    /** @test */
    public function it_returns_full_hierarchical_context()
    {
        // Create complete hierarchy
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        $run = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Upper Cabinets',
        ]);

        $cabinet = CabinetSpecification::create([
            'cabinet_run_id' => $run->id,
            'label' => 'W3018',
            'cabinet_type' => 'wall',
        ]);

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'context' => [
                'project_id' => $this->project->id,
            ],
        ]);

        // Verify all levels are present
        $this->assertCount(1, $response->json('context.rooms'));
        $this->assertCount(1, $response->json('context.room_locations'));
        $this->assertCount(1, $response->json('context.cabinet_runs'));
        $this->assertCount(1, $response->json('context.cabinets'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_pdf_page()
    {
        $response = $this->getJson('/api/pdf/page/999999/context');

        $response->assertNotFound();
        $response->assertJson([
            'success' => false,
            'error' => 'PDF page not found',
        ]);
    }

    /** @test */
    public function it_only_returns_entities_for_the_specific_project()
    {
        // Create another project with entities
        $otherProject = \Webkul\Project\Models\Project::factory()->create([
            'name' => 'Other Project',
        ]);

        Room::create([
            'project_id' => $otherProject->id,
            'name' => 'Other Kitchen',
            'room_type' => 'kitchen',
        ]);

        // Create room in our test project
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Test Kitchen',
            'room_type' => 'kitchen',
        ]);

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertOk();

        // Should only return 1 room (from test project)
        $this->assertCount(1, $response->json('context.rooms'));
        $this->assertEquals('Test Kitchen', $response->json('context.rooms.0.name'));
    }
}
