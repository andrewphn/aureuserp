<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Illuminate\Support\Facades\Storage;

class AnnotationCountE2ETest extends TestCase
{
    // NOTE: Not using RefreshDatabase to avoid migration issues with plugin tables
    // Manual cleanup is done in tearDown()

    private User $user;
    private Project $project;
    private PdfDocument $pdfDocument;
    private PdfPage $pdfPage;
    private Room $room;
    private RoomLocation $location;
    private CabinetRun $cabinetRun;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with unique email
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create project directly without factory to avoid ProjectStage dependencies
        $this->project = Project::create([
            'name' => 'Test Project - Annotation Counts',
            'project_number' => 'TEST-' . uniqid(),
            'description' => 'Test project for annotation counts',
            'visibility' => 'public',
            'is_active' => true,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Create PDF document
        Storage::fake('public');
        $this->pdfDocument = PdfDocument::create([
            'file_path' => 'test-pdfs/test.pdf',
            'file_name' => 'test.pdf',
            'module_type' => 'Webkul\Project\Models\Project',
            'module_id' => $this->project->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create PDF page
        $this->pdfPage = PdfPage::create([
            'document_id' => $this->pdfDocument->id,
            'page_number' => 1,
            'page_type' => 'floor_plan',
        ]);

        // Create room
        $this->room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'sort_order' => 1,
        ]);

        // Create room location
        $this->location = RoomLocation::create([
            'room_id' => $this->room->id,
            'name' => 'North Wall',
            'sort_order' => 1,
        ]);

        // Create cabinet run
        $this->cabinetRun = CabinetRun::create([
            'room_location_id' => $this->location->id,
            'name' => 'Base Run 1',
            'sort_order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test data in reverse order of creation
        if (isset($this->cabinetRun)) {
            $this->cabinetRun->forceDelete();
        }
        if (isset($this->location)) {
            $this->location->forceDelete();
        }
        if (isset($this->room)) {
            $this->room->forceDelete();
        }
        if (isset($this->pdfPage)) {
            // Delete annotations first
            PdfPageAnnotation::where('pdf_page_id', $this->pdfPage->id)->forceDelete();
            $this->pdfPage->forceDelete();
        }
        if (isset($this->pdfDocument)) {
            $this->pdfDocument->forceDelete();
        }
        if (isset($this->project)) {
            $this->project->forceDelete();
        }
        if (isset($this->user)) {
            $this->user->forceDelete();
        }

        parent::tearDown();
    }

    /** @test */
    public function it_shows_zero_annotation_counts_for_new_project()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $response->assertOk();

        $tree = $response->json();

        // Verify tree structure
        $this->assertCount(1, $tree);
        $this->assertEquals('Kitchen', $tree[0]['name']);
        $this->assertEquals('room', $tree[0]['type']);
        $this->assertEquals(0, $tree[0]['annotation_count'], 'Room should have 0 annotations initially');

        $this->assertCount(1, $tree[0]['children']);
        $this->assertEquals('North Wall', $tree[0]['children'][0]['name']);
        $this->assertEquals('room_location', $tree[0]['children'][0]['type']);
        $this->assertEquals(0, $tree[0]['children'][0]['annotation_count'], 'Location should have 0 annotations initially');

        $this->assertCount(1, $tree[0]['children'][0]['children']);
        $this->assertEquals('Base Run 1', $tree[0]['children'][0]['children'][0]['name']);
        $this->assertEquals('cabinet_run', $tree[0]['children'][0]['children'][0]['type']);
        $this->assertEquals(0, $tree[0]['children'][0]['children'][0]['annotation_count'], 'Cabinet run should have 0 annotations initially');
    }

    /** @test */
    public function it_increments_room_annotation_count_when_creating_room_annotation()
    {
        // Create room annotation
        PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_id' => $this->room->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.3,
            'height' => 0.3,
            'label' => 'Kitchen Area',
            'color' => '#3B82F6',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $response->assertOk();
        $tree = $response->json();

        $this->assertEquals(1, $tree[0]['annotation_count'], 'Room should have 1 annotation after creating room annotation');
    }

    /** @test */
    public function it_increments_room_annotation_count_when_creating_multiple_room_annotations()
    {
        // Create 3 room annotations
        for ($i = 1; $i <= 3; $i++) {
            PdfPageAnnotation::create([
                'pdf_page_id' => $this->pdfPage->id,
                'annotation_type' => 'room',
                'room_id' => $this->room->id,
                'x' => 0.1 * $i,
                'y' => 0.1 * $i,
                'width' => 0.2,
                'height' => 0.2,
                'label' => "Room Annotation {$i}",
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $response->assertOk();
        $tree = $response->json();

        $this->assertEquals(3, $tree[0]['annotation_count'], 'Room should have 3 annotations');
    }

    /** @test */
    public function it_increments_cabinet_run_annotation_count_when_creating_run_annotation()
    {
        // Create cabinet run annotation
        PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'cabinet_run',
            'cabinet_run_id' => $this->cabinetRun->id,
            'x' => 0.5,
            'y' => 0.5,
            'width' => 0.2,
            'height' => 0.1,
            'label' => 'Base Run Box',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $response->assertOk();
        $tree = $response->json();

        $runNode = $tree[0]['children'][0]['children'][0];
        $this->assertEquals(1, $runNode['annotation_count'], 'Cabinet run should have 1 annotation');
    }

    /** @test */
    public function it_handles_mixed_annotations_across_different_entities()
    {
        // Create room annotation
        PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_id' => $this->room->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.8,
            'height' => 0.8,
            'label' => 'Kitchen',
            'created_by' => $this->user->id,
        ]);

        // Create cabinet run annotations
        for ($i = 1; $i <= 2; $i++) {
            PdfPageAnnotation::create([
                'pdf_page_id' => $this->pdfPage->id,
                'annotation_type' => 'cabinet_run',
                'cabinet_run_id' => $this->cabinetRun->id,
                'x' => 0.5,
                'y' => 0.5 + ($i * 0.1),
                'width' => 0.2,
                'height' => 0.1,
                'label' => "Run Box {$i}",
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $response->assertOk();
        $tree = $response->json();

        $this->assertEquals(1, $tree[0]['annotation_count'], 'Room should have 1 annotation');

        $runNode = $tree[0]['children'][0]['children'][0];
        $this->assertEquals(2, $runNode['annotation_count'], 'Cabinet run should have 2 annotations');
    }

    /** @test */
    public function it_counts_annotations_correctly_for_multiple_rooms()
    {
        // Create second room
        $bathroom = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Bathroom',
            'room_type' => 'bathroom',
            'sort_order' => 2,
        ]);

        // Create 2 annotations for kitchen
        for ($i = 1; $i <= 2; $i++) {
            PdfPageAnnotation::create([
                'pdf_page_id' => $this->pdfPage->id,
                'annotation_type' => 'room',
                'room_id' => $this->room->id,
                'x' => 0.1 * $i,
                'y' => 0.1 * $i,
                'width' => 0.2,
                'height' => 0.2,
                'label' => "Kitchen Annotation {$i}",
                'created_by' => $this->user->id,
            ]);
        }

        // Create 3 annotations for bathroom
        for ($i = 1; $i <= 3; $i++) {
            PdfPageAnnotation::create([
                'pdf_page_id' => $this->pdfPage->id,
                'annotation_type' => 'room',
                'room_id' => $bathroom->id,
                'x' => 0.6 + (0.05 * $i),
                'y' => 0.6 + (0.05 * $i),
                'width' => 0.15,
                'height' => 0.15,
                'label' => "Bathroom Annotation {$i}",
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $response->assertOk();
        $tree = $response->json();

        $this->assertCount(2, $tree);

        // Find kitchen and bathroom in tree
        $kitchen = collect($tree)->firstWhere('name', 'Kitchen');
        $bathroomNode = collect($tree)->firstWhere('name', 'Bathroom');

        $this->assertEquals(2, $kitchen['annotation_count'], 'Kitchen should have 2 annotations');
        $this->assertEquals(3, $bathroomNode['annotation_count'], 'Bathroom should have 3 annotations');
    }

    /** @test */
    public function it_updates_counts_after_deleting_annotations()
    {
        // Create 3 room annotations
        $annotations = [];
        for ($i = 1; $i <= 3; $i++) {
            $annotations[] = PdfPageAnnotation::create([
                'pdf_page_id' => $this->pdfPage->id,
                'annotation_type' => 'room',
                'room_id' => $this->room->id,
                'x' => 0.1 * $i,
                'y' => 0.1 * $i,
                'width' => 0.2,
                'height' => 0.2,
                'label' => "Room Annotation {$i}",
                'created_by' => $this->user->id,
            ]);
        }

        // Verify count is 3
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");
        $tree = $response->json();
        $this->assertEquals(3, $tree[0]['annotation_count']);

        // Delete one annotation
        $annotations[0]->delete();

        // Verify count is now 2
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");
        $tree = $response->json();
        $this->assertEquals(2, $tree[0]['annotation_count'], 'Room should have 2 annotations after deleting 1');

        // Delete all remaining annotations
        $annotations[1]->delete();
        $annotations[2]->delete();

        // Verify count is now 0
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");
        $tree = $response->json();
        $this->assertEquals(0, $tree[0]['annotation_count'], 'Room should have 0 annotations after deleting all');
    }

    /** @test */
    public function it_correctly_counts_annotations_via_save_annotations_endpoint()
    {
        // Use the actual save annotations API endpoint
        $response = $this->actingAs($this->user)
            ->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", [
                'annotations' => [
                    [
                        'annotation_type' => 'room',
                        'x' => 0.1,
                        'y' => 0.1,
                        'width' => 0.8,
                        'height' => 0.8,
                        'text' => 'Kitchen',
                        'room_type' => 'kitchen',
                        'color' => '#3B82F6',
                        'room_id' => $this->room->id,
                        'context' => [],
                    ],
                    [
                        'annotation_type' => 'cabinet_run',
                        'x' => 0.2,
                        'y' => 0.2,
                        'width' => 0.3,
                        'height' => 0.1,
                        'text' => 'Base Run 1',
                        'cabinet_run_id' => $this->cabinetRun->id,
                        'context' => [],
                    ],
                    [
                        'annotation_type' => 'cabinet_run',
                        'x' => 0.2,
                        'y' => 0.4,
                        'width' => 0.3,
                        'height' => 0.1,
                        'text' => 'Upper Cabinets',
                        'cabinet_run_id' => $this->cabinetRun->id,
                        'context' => [],
                    ],
                ],
                'create_entities' => false,
            ]);

        $response->assertStatus(201);

        // Verify tree counts
        $treeResponse = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $treeResponse->assertOk();
        $tree = $treeResponse->json();

        $this->assertEquals(1, $tree[0]['annotation_count'], 'Room should have 1 annotation');

        $runNode = $tree[0]['children'][0]['children'][0];
        $this->assertEquals(2, $runNode['annotation_count'], 'Cabinet run should have 2 annotations');
    }

    /** @test */
    public function it_handles_replace_strategy_correctly_for_annotation_counts()
    {
        // Create initial annotations
        $this->actingAs($this->user)
            ->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", [
                'annotations' => [
                    [
                        'annotation_type' => 'room',
                        'x' => 0.1,
                        'y' => 0.1,
                        'width' => 0.5,
                        'height' => 0.5,
                        'text' => 'Kitchen',
                        'room_id' => $this->room->id,
                        'context' => [],
                    ],
                ],
                'create_entities' => false,
            ]);

        // Verify count is 1
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");
        $tree = $response->json();
        $this->assertEquals(1, $tree[0]['annotation_count']);

        // Replace with 3 annotations
        $this->actingAs($this->user)
            ->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", [
                'annotations' => [
                    [
                        'annotation_type' => 'room',
                        'x' => 0.1,
                        'y' => 0.1,
                        'width' => 0.2,
                        'height' => 0.2,
                        'text' => 'Kitchen 1',
                        'room_id' => $this->room->id,
                        'context' => [],
                    ],
                    [
                        'annotation_type' => 'room',
                        'x' => 0.3,
                        'y' => 0.3,
                        'width' => 0.2,
                        'height' => 0.2,
                        'text' => 'Kitchen 2',
                        'room_id' => $this->room->id,
                        'context' => [],
                    ],
                    [
                        'annotation_type' => 'room',
                        'x' => 0.5,
                        'y' => 0.5,
                        'width' => 0.2,
                        'height' => 0.2,
                        'text' => 'Kitchen 3',
                        'room_id' => $this->room->id,
                        'context' => [],
                    ],
                ],
                'create_entities' => false,
            ]);

        // Verify count is now 3
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");
        $tree = $response->json();
        $this->assertEquals(3, $tree[0]['annotation_count'], 'Room should have 3 annotations after replace');
    }

    /** @test */
    public function it_does_not_count_soft_deleted_annotations()
    {
        // Create annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_id' => $this->room->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.3,
            'height' => 0.3,
            'label' => 'Kitchen',
            'created_by' => $this->user->id,
        ]);

        // Verify count is 1
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");
        $tree = $response->json();
        $this->assertEquals(1, $tree[0]['annotation_count']);

        // Soft delete the annotation
        $annotation->delete();

        // Verify count is now 0
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");
        $tree = $response->json();
        $this->assertEquals(0, $tree[0]['annotation_count'], 'Soft deleted annotations should not be counted');
    }
}
