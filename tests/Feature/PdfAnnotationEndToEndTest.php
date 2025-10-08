<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PdfPage;
use App\Models\PdfDocument;
use App\Models\PdfPageAnnotation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PdfAnnotationEndToEndTest extends TestCase
{
    // Note: Not using RefreshDatabase to avoid migration order issues
    // Tests clean up after themselves

    protected $user;
    protected $project;
    protected $pdfDocument;
    protected $pdfPage;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $this->user = \Webkul\Security\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Create test project
        $this->project = Project::factory()->create([
            'name' => '15 Correia Ln - Residential',
            'project_number' => 'TFW-0001'
        ]);

        // Create PDF document
        $this->pdfDocument = PdfDocument::create([
            'module_type' => Project::class,
            'module_id' => $this->project->id,
            'filename' => 'test-plan.pdf',
            'original_filename' => 'test-plan.pdf',
            'file_path' => '/storage/pdfs/test-plan.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024000,
            'page_count' => 5,
            'version_number' => 1,
            'is_current_version' => true,
        ]);

        // Create PDF page
        $this->pdfPage = PdfPage::create([
            'pdf_document_id' => $this->pdfDocument->id,
            'page_number' => 1,
        ]);
    }

    /** @test */
    public function it_can_load_annotation_context_for_pdf_page()
    {
        // Create some test data
        Room::factory()->count(3)->create(['project_id' => $this->project->id]);

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/context");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'context' => [
                    'project_id' => $this->project->id,
                    'project_name' => '15 Correia Ln - Residential',
                ]
            ])
            ->assertJsonStructure([
                'success',
                'context' => [
                    'project_id',
                    'project_name',
                    'rooms',
                    'room_locations',
                    'cabinet_runs',
                    'cabinets',
                ]
            ]);

        $this->assertEquals(3, count($response->json('context.rooms')));
    }

    /** @test */
    public function it_can_create_annotation_with_entity()
    {
        $annotationData = [
            'annotations' => [
                [
                    'type' => 'rectangle',
                    'x' => 0.2,
                    'y' => 0.3,
                    'width' => 0.15,
                    'height' => 0.12,
                    'text' => 'TFW-0001-K',
                    'room_type' => 'kitchen',
                    'annotation_type' => 'room',
                    'notes' => 'Test kitchen annotation',
                ]
            ],
            'create_entities' => true
        ];

        $response = $this->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", $annotationData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);

        // Verify annotation was created
        $this->assertDatabaseHas('pdf_page_annotations', [
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'room_type' => 'kitchen',
        ]);

        // Verify room entity was created
        $this->assertDatabaseHas('projects_rooms', [
            'project_id' => $this->project->id,
            'room_type' => 'kitchen',
        ]);
    }

    /** @test */
    public function it_can_load_existing_annotations()
    {
        // Create test annotation
        PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'label' => 'Test Room',
            'room_type' => 'kitchen',
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
        ]);

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'annotations' => [
                    '*' => [
                        'id',
                        'annotation_type',
                        'label',
                        'x',
                        'y',
                        'width',
                        'height',
                    ]
                ]
            ]);

        $this->assertEquals(1, count($response->json('annotations')));
    }

    /** @test */
    public function it_deletes_old_annotations_when_saving_new_ones()
    {
        // Create old annotation
        PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'label' => 'Old Room',
            'room_type' => 'kitchen',
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.1,
            'height' => 0.1,
        ]);

        $this->assertEquals(1, PdfPageAnnotation::where('pdf_page_id', $this->pdfPage->id)->count());

        // Save new annotations
        $annotationData = [
            'annotations' => [
                [
                    'type' => 'rectangle',
                    'x' => 0.5,
                    'y' => 0.5,
                    'width' => 0.2,
                    'height' => 0.2,
                    'text' => 'New Room',
                    'room_type' => 'pantry',
                    'annotation_type' => 'room',
                ]
            ]
        ];

        $this->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", $annotationData);

        // Should only have 1 annotation (old one deleted)
        $this->assertEquals(1, PdfPageAnnotation::where('pdf_page_id', $this->pdfPage->id)->count());

        // Verify it's the new one
        $this->assertDatabaseHas('pdf_page_annotations', [
            'pdf_page_id' => $this->pdfPage->id,
            'room_type' => 'pantry',
        ]);

        $this->assertDatabaseMissing('pdf_page_annotations', [
            'pdf_page_id' => $this->pdfPage->id,
            'room_type' => 'kitchen',
        ]);
    }

    /** @test */
    public function it_validates_required_annotation_fields()
    {
        $invalidData = [
            'annotations' => [
                [
                    'type' => 'rectangle',
                    // Missing x, y, width, height
                ]
            ]
        ];

        $response = $this->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", $invalidData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_normalizes_annotation_coordinates()
    {
        $annotationData = [
            'annotations' => [
                [
                    'type' => 'rectangle',
                    'x' => 0.25,
                    'y' => 0.35,
                    'width' => 0.18,
                    'height' => 0.14,
                    'text' => 'Test',
                    'annotation_type' => 'room',
                ]
            ]
        ];

        $this->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", $annotationData);

        $annotation = PdfPageAnnotation::where('pdf_page_id', $this->pdfPage->id)->first();

        $this->assertTrue($annotation->x >= 0 && $annotation->x <= 1);
        $this->assertTrue($annotation->y >= 0 && $annotation->y <= 1);
        $this->assertTrue($annotation->width >= 0 && $annotation->width <= 1);
        $this->assertTrue($annotation->height >= 0 && $annotation->height <= 1);
    }
}
