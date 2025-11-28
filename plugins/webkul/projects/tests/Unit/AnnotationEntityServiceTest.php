<?php

namespace Webkul\Project\Tests\Unit;

use Webkul\Project\Tests\TestCase;
use App\Services\AnnotationEntityService;
use Webkul\Project\Models\PdfPageAnnotation;
use Webkul\Project\Models\PdfPage;
use Webkul\Project\Models\PdfDocument;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;

/**
 * Annotation Entity Service Test test case
 *
 */
class AnnotationEntityServiceTest extends TestCase
{
    protected AnnotationEntityService $service;
    protected $project;
    protected $pdfDocument;
    protected $pdfPage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AnnotationEntityService();

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
            'page_count' => 1,
        ]);

        // Create PDF page
        $this->pdfPage = PdfPage::create([
            'pdf_document_id' => $this->pdfDocument->id,
            'page_number' => 1,
        ]);
    }

    /** @test */
    public function it_creates_room_from_annotation()
    {
        // Create room annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'label' => 'Test Kitchen',
            'room_type' => 'kitchen',
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
        ]);

        // Call service to create entity
        $result = $this->service->createOrLinkEntityFromAnnotation($annotation, [
            'project_id' => $this->project->id,
            'room_name' => 'Test Kitchen',
            'page_number' => 1,
        ]);

        // Assert success
        $this->assertTrue($result['success']);
        $this->assertEquals('room', $result['entity_type']);
        $this->assertInstanceOf(Room::class, $result['entity']);

        // Verify room was created
        $room = Room::where('name', 'Test Kitchen')->first();
        $this->assertNotNull($room);
        $this->assertEquals($this->project->id, $room->project_id);

        // Verify annotation was linked
        $annotation->refresh();
        $this->assertEquals($room->id, $annotation->room_id);
    }

    /** @test */
    public function it_creates_room_location_from_annotation()
    {
        // Create parent room
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Main Kitchen',
            'room_type' => 'kitchen',
        ]);

        // Create room location annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room_location',
            'label' => 'North Wall',
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
        ]);

        // Call service to create entity
        $result = $this->service->createOrLinkEntityFromAnnotation($annotation, [
            'project_id' => $this->project->id,
            'room_id' => $room->id,
            'location_name' => 'North Wall',
            'page_number' => 1,
        ]);

        // Assert success
        $this->assertTrue($result['success']);
        $this->assertEquals('room_location', $result['entity_type']);
        $this->assertInstanceOf(RoomLocation::class, $result['entity']);

        // Verify room location was created
        $location = RoomLocation::where('name', 'North Wall')->first();
        $this->assertNotNull($location);
        $this->assertEquals($room->id, $location->room_id);

        // Verify annotation was linked
        $annotation->refresh();
        $this->assertEquals($location->id, $annotation->room_location_id);
    }

    /** @test */
    public function it_creates_cabinet_run_from_annotation()
    {
        // Create parent room and location
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Main Kitchen',
            'room_type' => 'kitchen',
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        // Create cabinet run annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'cabinet_run',
            'label' => 'Upper Cabinets',
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
        ]);

        // Call service to create entity
        $result = $this->service->createOrLinkEntityFromAnnotation($annotation, [
            'project_id' => $this->project->id,
            'room_id' => $room->id,
            'room_location_id' => $location->id,
            'run_name' => 'Upper Cabinets',
            'page_number' => 1,
        ]);

        // Assert success
        $this->assertTrue($result['success']);
        $this->assertEquals('cabinet_run', $result['entity_type']);
        $this->assertInstanceOf(CabinetRun::class, $result['entity']);

        // Verify cabinet run was created
        $run = CabinetRun::where('name', 'Upper Cabinets')->first();
        $this->assertNotNull($run);
        $this->assertEquals($location->id, $run->room_location_id);

        // Verify annotation was linked
        $annotation->refresh();
        $this->assertEquals($run->id, $annotation->cabinet_run_id);
    }

    /** @test */
    public function it_returns_error_for_invalid_annotation_type()
    {
        // Create annotation with invalid type
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'invalid_type',
            'label' => 'Test',
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
        ]);

        // Call service
        $result = $this->service->createOrLinkEntityFromAnnotation($annotation, [
            'project_id' => $this->project->id,
        ]);

        // Assert error
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown annotation type', $result['error']);
    }

    /** @test */
    public function it_returns_error_for_missing_required_context()
    {
        // Create cabinet run annotation (requires room_id and room_location_id)
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'cabinet_run',
            'label' => 'Upper Cabinets',
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
        ]);

        // Call service without required room_location_id
        $result = $this->service->createOrLinkEntityFromAnnotation($annotation, [
            'project_id' => $this->project->id,
            'run_name' => 'Upper Cabinets',
            // Missing room_id and room_location_id
        ]);

        // Assert error
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_prevents_duplicate_room_creation()
    {
        // Create initial room
        $existingRoom = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        // Create annotation for same room
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'label' => 'Kitchen',
            'room_type' => 'kitchen',
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
        ]);

        // Call service
        $result = $this->service->createOrLinkEntityFromAnnotation($annotation, [
            'project_id' => $this->project->id,
            'room_name' => 'Kitchen',
            'page_number' => 1,
        ]);

        // Should succeed by linking to existing room
        $this->assertTrue($result['success']);
        $this->assertEquals($existingRoom->id, $result['entity']->id);

        // Verify only one room exists
        $this->assertEquals(1, Room::where('name', 'Kitchen')->count());
    }
}
