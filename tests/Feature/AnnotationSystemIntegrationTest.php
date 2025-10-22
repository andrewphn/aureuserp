<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Illuminate\Support\Facades\Storage;

/**
 * Comprehensive integration test verifying the entire annotation system
 * connects properly from Project → Document → Page → Annotation → Entities
 */
class AnnotationSystemIntegrationTest extends TestCase
{
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

        // Create test user
        $this->user = User::create([
            'name' => 'Integration Test User',
            'email' => 'integration-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create project
        $this->project = Project::create([
            'name' => 'Integration Test Project',
            'project_number' => 'INT-' . uniqid(),
            'description' => 'Testing complete annotation system integration',
            'visibility' => 'public',
            'is_active' => true,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Create PDF document linked to project
        Storage::fake('public');
        $this->pdfDocument = PdfDocument::create([
            'file_path' => 'test-pdfs/integration-test.pdf',
            'file_name' => 'integration-test.pdf',
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
            'name' => 'Master Bedroom',
            'sort_order' => 1,
        ]);

        // Create location
        $this->location = RoomLocation::create([
            'room_id' => $this->room->id,
            'name' => 'East Wall',
            'sort_order' => 1,
        ]);

        // Create cabinet run
        $this->cabinetRun = CabinetRun::create([
            'room_location_id' => $this->location->id,
            'name' => 'Upper Cabinets',
            'sort_order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up in reverse order
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
    public function project_connects_to_pdf_document()
    {
        // Verify polymorphic relationship: Document → Project
        $this->assertEquals('Webkul\Project\Models\Project', $this->pdfDocument->module_type);
        $this->assertEquals($this->project->id, $this->pdfDocument->module_id);

        // Verify we can query documents by project
        $documents = PdfDocument::where('module_type', 'Webkul\Project\Models\Project')
            ->where('module_id', $this->project->id)
            ->get();

        $this->assertCount(1, $documents);
        $this->assertEquals($this->pdfDocument->id, $documents->first()->id);
    }

    /** @test */
    public function pdf_document_connects_to_pdf_pages()
    {
        // Verify relationship: Page → Document
        $this->assertEquals($this->pdfDocument->id, $this->pdfPage->document_id);

        // Verify we can query pages from document
        $pages = PdfPage::where('document_id', $this->pdfDocument->id)->get();
        $this->assertCount(1, $pages);
        $this->assertEquals($this->pdfPage->id, $pages->first()->id);

        // Verify page relationship
        $page = PdfPage::with('document')->find($this->pdfPage->id);
        $this->assertNotNull($page->document);
        $this->assertEquals($this->pdfDocument->id, $page->document->id);
    }

    /** @test */
    public function annotations_connect_to_pdf_pages()
    {
        // Create annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_id' => $this->room->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.5,
            'height' => 0.3,
            'label' => 'Master Bedroom Area',
            'color' => '#3B82F6',
            'created_by' => $this->user->id,
        ]);

        // Verify relationship: Annotation → Page
        $this->assertEquals($this->pdfPage->id, $annotation->pdf_page_id);

        // Verify Eloquent relationship
        $loadedAnnotation = PdfPageAnnotation::with('pdfPage')->find($annotation->id);
        $this->assertNotNull($loadedAnnotation->pdfPage);
        $this->assertEquals($this->pdfPage->id, $loadedAnnotation->pdfPage->id);

        // Verify we can query annotations from page
        $pageAnnotations = PdfPageAnnotation::where('pdf_page_id', $this->pdfPage->id)->get();
        $this->assertCount(1, $pageAnnotations);
        $this->assertEquals($annotation->id, $pageAnnotations->first()->id);

        $annotation->forceDelete();
    }

    /** @test */
    public function annotations_connect_to_rooms()
    {
        // Create room annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_id' => $this->room->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.5,
            'height' => 0.3,
            'label' => 'Master Bedroom',
            'color' => '#3B82F6',
            'created_by' => $this->user->id,
        ]);

        // Verify connection to room
        $this->assertEquals($this->room->id, $annotation->room_id);

        // Verify we can query annotations by room
        $roomAnnotations = PdfPageAnnotation::where('room_id', $this->room->id)->get();
        $this->assertCount(1, $roomAnnotations);
        $this->assertEquals($annotation->id, $roomAnnotations->first()->id);

        $annotation->forceDelete();
    }

    /** @test */
    public function annotations_connect_to_cabinet_runs()
    {
        // Create cabinet run annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'cabinet_run',
            'cabinet_run_id' => $this->cabinetRun->id,
            'x' => 0.2,
            'y' => 0.2,
            'width' => 0.4,
            'height' => 0.2,
            'label' => 'Upper Cabinets',
            'color' => '#10B981',
            'created_by' => $this->user->id,
        ]);

        // Verify connection to cabinet run
        $this->assertEquals($this->cabinetRun->id, $annotation->cabinet_run_id);

        // Verify Eloquent relationship
        $loadedAnnotation = PdfPageAnnotation::with('cabinetRun')->find($annotation->id);
        $this->assertNotNull($loadedAnnotation->cabinetRun);
        $this->assertEquals($this->cabinetRun->id, $loadedAnnotation->cabinetRun->id);

        // Verify we can query annotations by cabinet run
        $runAnnotations = PdfPageAnnotation::where('cabinet_run_id', $this->cabinetRun->id)->get();
        $this->assertCount(1, $runAnnotations);
        $this->assertEquals($annotation->id, $runAnnotations->first()->id);

        $annotation->forceDelete();
    }

    /** @test */
    public function project_tree_reflects_annotation_connections()
    {
        // Create annotations for different entities
        $roomAnnotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_id' => $this->room->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.5,
            'height' => 0.3,
            'label' => 'Room Area',
            'created_by' => $this->user->id,
        ]);

        $runAnnotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'cabinet_run',
            'cabinet_run_id' => $this->cabinetRun->id,
            'x' => 0.2,
            'y' => 0.2,
            'width' => 0.4,
            'height' => 0.2,
            'label' => 'Run Area',
            'created_by' => $this->user->id,
        ]);

        // Fetch project tree
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $response->assertOk();
        $tree = $response->json();

        // Verify tree structure includes our entities
        $this->assertCount(1, $tree, 'Should have 1 room');

        $room = $tree[0];
        $this->assertEquals($this->room->id, $room['id']);
        $this->assertEquals('Master Bedroom', $room['name']);
        $this->assertEquals('room', $room['type']);
        $this->assertEquals(1, $room['annotation_count'], 'Room should have 1 annotation');

        // Verify location in tree
        $this->assertCount(1, $room['children'], 'Room should have 1 location');
        $location = $room['children'][0];
        $this->assertEquals($this->location->id, $location['id']);
        $this->assertEquals('East Wall', $location['name']);
        $this->assertEquals('room_location', $location['type']);

        // Verify cabinet run in tree
        $this->assertCount(1, $location['children'], 'Location should have 1 cabinet run');
        $run = $location['children'][0];
        $this->assertEquals($this->cabinetRun->id, $run['id']);
        $this->assertEquals('Upper Cabinets', $run['name']);
        $this->assertEquals('cabinet_run', $run['type']);
        $this->assertEquals(1, $run['annotation_count'], 'Cabinet run should have 1 annotation');

        $roomAnnotation->forceDelete();
        $runAnnotation->forceDelete();
    }

    /** @test */
    public function complete_data_flow_from_project_to_annotation()
    {
        // This test verifies the complete chain:
        // Project → Room → Location → Cabinet Run → Annotation → PDF Page → PDF Document

        // Create annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'cabinet_run',
            'cabinet_run_id' => $this->cabinetRun->id,
            'room_id' => $this->room->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.5,
            'height' => 0.3,
            'label' => 'Complete Flow Test',
            'created_by' => $this->user->id,
        ]);

        // Load annotation with all relationships
        $loadedAnnotation = PdfPageAnnotation::with([
            'pdfPage.document',
            'cabinetRun.roomLocation.room'
        ])->find($annotation->id);

        // Verify complete chain works
        $this->assertNotNull($loadedAnnotation, 'Annotation should exist');
        $this->assertNotNull($loadedAnnotation->pdfPage, 'Annotation → PDF Page');
        $this->assertNotNull($loadedAnnotation->pdfPage->document, 'PDF Page → PDF Document');
        $this->assertNotNull($loadedAnnotation->cabinetRun, 'Annotation → Cabinet Run');
        $this->assertNotNull($loadedAnnotation->cabinetRun->roomLocation, 'Cabinet Run → Location');
        $this->assertNotNull($loadedAnnotation->cabinetRun->roomLocation->room, 'Location → Room');

        // Verify IDs match throughout the chain
        $this->assertEquals($this->pdfPage->id, $loadedAnnotation->pdfPage->id);
        $this->assertEquals($this->pdfDocument->id, $loadedAnnotation->pdfPage->document->id);
        $this->assertEquals($this->cabinetRun->id, $loadedAnnotation->cabinetRun->id);
        $this->assertEquals($this->location->id, $loadedAnnotation->cabinetRun->roomLocation->id);
        $this->assertEquals($this->room->id, $loadedAnnotation->cabinetRun->roomLocation->room->id);

        // Verify we can trace back to project
        $this->assertEquals($this->project->id, $loadedAnnotation->cabinetRun->roomLocation->room->project_id);

        // Verify document links to same project
        $this->assertEquals('Webkul\Project\Models\Project', $loadedAnnotation->pdfPage->document->module_type);
        $this->assertEquals($this->project->id, $loadedAnnotation->pdfPage->document->module_id);

        $annotation->forceDelete();
    }

    /** @test */
    public function api_endpoint_creates_annotations_with_correct_connections()
    {
        // Use the actual API endpoint to create annotations
        $response = $this->actingAs($this->user)
            ->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", [
                'annotations' => [
                    [
                        'annotation_type' => 'room',
                        'x' => 0.1,
                        'y' => 0.1,
                        'width' => 0.5,
                        'height' => 0.3,
                        'label' => 'Room via API',
                        'room_id' => $this->room->id,
                        'color' => '#3B82F6',
                    ],
                    [
                        'annotation_type' => 'cabinet_run',
                        'x' => 0.2,
                        'y' => 0.2,
                        'width' => 0.4,
                        'height' => 0.2,
                        'label' => 'Run via API',
                        'cabinet_run_id' => $this->cabinetRun->id,
                        'color' => '#10B981',
                    ],
                ],
                'create_entities' => false,
            ]);

        $response->assertStatus(201); // API returns 201 Created for new annotations

        // Verify annotations were created with correct connections
        $annotations = PdfPageAnnotation::where('pdf_page_id', $this->pdfPage->id)->get();
        $this->assertCount(2, $annotations);

        $roomAnnotation = $annotations->where('annotation_type', 'room')->first();
        $this->assertEquals($this->room->id, $roomAnnotation->room_id);
        $this->assertEquals($this->pdfPage->id, $roomAnnotation->pdf_page_id);

        $runAnnotation = $annotations->where('annotation_type', 'cabinet_run')->first();
        $this->assertEquals($this->cabinetRun->id, $runAnnotation->cabinet_run_id);
        $this->assertEquals($this->pdfPage->id, $runAnnotation->pdf_page_id);

        // Verify tree API reflects these annotations
        $treeResponse = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");

        $tree = $treeResponse->json();
        $this->assertEquals(1, $tree[0]['annotation_count'], 'Room should have 1 annotation');
        $this->assertEquals(1, $tree[0]['children'][0]['children'][0]['annotation_count'], 'Cabinet run should have 1 annotation');
    }

    /** @test */
    public function hierarchical_annotations_maintain_parent_child_relationships()
    {
        // Create parent annotation (room-level)
        $parentAnnotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_id' => $this->room->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.8,
            'height' => 0.6,
            'label' => 'Parent Room Annotation',
            'created_by' => $this->user->id,
        ]);

        // Create child annotation (location within room)
        $childAnnotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'parent_annotation_id' => $parentAnnotation->id,
            'annotation_type' => 'location',
            'room_id' => $this->room->id,
            'x' => 0.2,
            'y' => 0.2,
            'width' => 0.3,
            'height' => 0.2,
            'label' => 'Child Location Annotation',
            'created_by' => $this->user->id,
        ]);

        // Verify parent-child relationship
        $loadedChild = PdfPageAnnotation::with('parentAnnotation')->find($childAnnotation->id);
        $this->assertNotNull($loadedChild->parentAnnotation);
        $this->assertEquals($parentAnnotation->id, $loadedChild->parentAnnotation->id);

        // Verify parent can access children
        $loadedParent = PdfPageAnnotation::with('childAnnotations')->find($parentAnnotation->id);
        $this->assertCount(1, $loadedParent->childAnnotations);
        $this->assertEquals($childAnnotation->id, $loadedParent->childAnnotations->first()->id);

        // Verify isTopLevel method
        $this->assertTrue($parentAnnotation->isTopLevel());
        $this->assertFalse($childAnnotation->isTopLevel());

        $childAnnotation->forceDelete();
        $parentAnnotation->forceDelete();
    }

    /** @test */
    public function soft_delete_preserves_relationships_for_history()
    {
        // Create annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_id' => $this->room->id,
            'cabinet_run_id' => $this->cabinetRun->id,
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.5,
            'height' => 0.3,
            'label' => 'Soft Delete Test',
            'created_by' => $this->user->id,
        ]);

        $annotationId = $annotation->id;

        // Soft delete
        $annotation->delete();

        // Verify annotation is soft deleted
        $this->assertNull(PdfPageAnnotation::find($annotationId), 'Should not find soft-deleted annotation');

        // But can still retrieve with trashed
        $trashedAnnotation = PdfPageAnnotation::withTrashed()->find($annotationId);
        $this->assertNotNull($trashedAnnotation);
        $this->assertNotNull($trashedAnnotation->deleted_at);

        // Verify relationships still work on soft-deleted records
        $trashedWithRelations = PdfPageAnnotation::withTrashed()
            ->with(['pdfPage', 'cabinetRun'])
            ->find($annotationId);

        $this->assertNotNull($trashedWithRelations->pdfPage);
        $this->assertEquals($this->pdfPage->id, $trashedWithRelations->pdfPage->id);
        $this->assertNotNull($trashedWithRelations->cabinetRun);
        $this->assertEquals($this->cabinetRun->id, $trashedWithRelations->cabinetRun->id);

        // Verify it doesn't appear in tree counts
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/tree");
        $tree = $response->json();
        $this->assertEquals(0, $tree[0]['annotation_count'], 'Soft-deleted annotations should not be counted');

        $trashedAnnotation->forceDelete();
    }
}
