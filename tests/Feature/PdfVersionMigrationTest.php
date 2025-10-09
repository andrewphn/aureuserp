<?php

namespace Tests\Feature;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Security\Models\User;

/**
 * Integration Tests for Phase 5: PDF Version Migration
 *
 * Tests complete workflows including annotation migration between versions.
 */
class PdfVersionMigrationTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Use existing user from database to avoid factory issues
        $this->user = User::first() ?? User::factory()->create();

        // Create project without using factory to avoid complex dependencies
        $this->project = new Project();
        $this->project->name = 'Test Project - Version Migration';
        $this->project->creator_id = $this->user->id;
        $this->project->save();
    }

    /**
     * Test: Complete version creation workflow
     */
    public function test_complete_version_creation_workflow()
    {
        // Create version 1 with pages
        $v1 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'kitchen-v1.pdf',
            'file_path' => 'test/kitchen-v1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'uploaded_by' => $this->user->id,
        ]);

        // Mark v1 as no longer latest
        $v1->update(['is_latest_version' => false]);

        // Create version 2
        $v2 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'kitchen-v2.pdf',
            'file_path' => 'test/kitchen-v2.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 2,
            'previous_version_id' => $v1->id,
            'is_latest_version' => true,
            'version_metadata' => [
                'version_notes' => 'Updated layout',
                'migrate_annotations' => true,
            ],
            'uploaded_by' => $this->user->id,
        ]);

        // Verify workflow
        $this->assertEquals(2, $v2->version_number);
        $this->assertTrue($v2->is_latest_version);
        $this->assertFalse($v1->fresh()->is_latest_version);
        $this->assertEquals($v1->id, $v2->previous_version_id);
        $this->assertEquals('Updated layout', $v2->version_metadata['version_notes']);
    }

    /**
     * Test: Annotation migration copies all pages
     */
    public function test_annotation_migration_copies_all_pages()
    {
        // Create version 1
        $v1 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v1.pdf',
            'file_path' => 'test/test-v1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 3,
            'uploaded_by' => $this->user->id,
        ]);

        // Create pages for v1
        $page1 = PdfPage::create([
            'pdf_document_id' => $v1->id,
            'page_number' => 1,
        ]);

        $page2 = PdfPage::create([
            'pdf_document_id' => $v1->id,
            'page_number' => 2,
        ]);

        $page3 = PdfPage::create([
            'pdf_document_id' => $v1->id,
            'page_number' => 3,
        ]);

        // Create version 2
        $v2 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v2.pdf',
            'file_path' => 'test/test-v2.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'page_count' => 3,
            'version_number' => 2,
            'previous_version_id' => $v1->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Simulate annotation migration
        foreach ($v1->pages as $oldPage) {
            PdfPage::create([
                'pdf_document_id' => $v2->id,
                'page_number' => $oldPage->page_number,
            ]);
        }

        // Verify all pages were copied
        $this->assertEquals(3, $v1->pages()->count());
        $this->assertEquals(3, $v2->pages()->count());

        // Verify page numbers match
        $v1PageNumbers = $v1->pages()->pluck('page_number')->sort()->values();
        $v2PageNumbers = $v2->pages()->pluck('page_number')->sort()->values();
        $this->assertEquals($v1PageNumbers->toArray(), $v2PageNumbers->toArray());
    }

    /**
     * Test: Annotation migration preserves annotation data
     */
    public function test_annotation_migration_preserves_annotation_data()
    {
        // Create version 1
        $v1 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v1.pdf',
            'file_path' => 'test/test-v1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'uploaded_by' => $this->user->id,
        ]);

        // Create page for v1
        $page1 = PdfPage::create([
            'pdf_document_id' => $v1->id,
            'page_number' => 1,
        ]);

        // Create room for annotation
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        // Create annotation on v1
        $annotation1 = PdfPageAnnotation::create([
            'pdf_page_id' => $page1->id,
            'annotation_type' => 'room',
            'label' => 'Main Kitchen',
            'room_id' => $room->id,
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
            'color' => '#3B82F6',
            'notes' => 'Primary cooking area',
        ]);

        // Create version 2
        $v2 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v2.pdf',
            'file_path' => 'test/test-v2.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'version_number' => 2,
            'previous_version_id' => $v1->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Migrate pages and annotations
        $page2 = PdfPage::create([
            'pdf_document_id' => $v2->id,
            'page_number' => $page1->page_number,
        ]);

        $annotation2 = $annotation1->replicate();
        $annotation2->pdf_page_id = $page2->id;
        $annotation2->save();

        // Verify annotation data preserved
        $this->assertEquals($annotation1->annotation_type, $annotation2->annotation_type);
        $this->assertEquals($annotation1->label, $annotation2->label);
        $this->assertEquals($annotation1->room_id, $annotation2->room_id);
        $this->assertEquals($annotation1->x, $annotation2->x);
        $this->assertEquals($annotation1->y, $annotation2->y);
        $this->assertEquals($annotation1->width, $annotation2->width);
        $this->assertEquals($annotation1->height, $annotation2->height);
        $this->assertEquals($annotation1->color, $annotation2->color);
        $this->assertEquals($annotation1->notes, $annotation2->notes);
    }

    /**
     * Test: Multiple annotations on same page are all migrated
     */
    public function test_multiple_annotations_migrated()
    {
        // Create version 1
        $v1 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v1.pdf',
            'file_path' => 'test/test-v1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'uploaded_by' => $this->user->id,
        ]);

        $page1 = PdfPage::create([
            'pdf_document_id' => $v1->id,
            'page_number' => 1,
        ]);

        // Create 3 annotations
        PdfPageAnnotation::create([
            'pdf_page_id' => $page1->id,
            'annotation_type' => 'room',
            'label' => 'Kitchen',
            'x' => 0.1,
            'y' => 0.1,
            'width' => 0.2,
            'height' => 0.15,
        ]);

        PdfPageAnnotation::create([
            'pdf_page_id' => $page1->id,
            'annotation_type' => 'room',
            'label' => 'Living Room',
            'x' => 0.5,
            'y' => 0.1,
            'width' => 0.3,
            'height' => 0.2,
        ]);

        PdfPageAnnotation::create([
            'pdf_page_id' => $page1->id,
            'annotation_type' => 'dimension',
            'label' => '10 ft',
            'x' => 0.2,
            'y' => 0.5,
            'width' => 0.1,
            'height' => 0.05,
        ]);

        // Create version 2
        $v2 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v2.pdf',
            'file_path' => 'test/test-v2.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'version_number' => 2,
            'previous_version_id' => $v1->id,
            'uploaded_by' => $this->user->id,
        ]);

        $page2 = PdfPage::create([
            'pdf_document_id' => $v2->id,
            'page_number' => 1,
        ]);

        // Migrate all annotations
        foreach ($page1->annotations as $oldAnnotation) {
            $newAnnotation = $oldAnnotation->replicate();
            $newAnnotation->pdf_page_id = $page2->id;
            $newAnnotation->save();
        }

        // Verify all 3 annotations were migrated
        $this->assertEquals(3, $page1->annotations()->count());
        $this->assertEquals(3, $page2->annotations()->count());
    }

    /**
     * Test: Version chain traversal works with 5 versions
     */
    public function test_version_chain_with_five_versions()
    {
        $versions = [];

        // Create 5 versions
        for ($i = 1; $i <= 5; $i++) {
            $versions[] = PdfDocument::create([
                'module_type' => get_class($this->project),
                'module_id' => $this->project->id,
                'file_name' => "test-v{$i}.pdf",
                'file_path' => "test/test-v{$i}.pdf",
                'file_size' => 1024 * $i,
                'mime_type' => 'application/pdf',
                'page_count' => $i,
                'version_number' => $i,
                'previous_version_id' => $i > 1 ? $versions[$i - 2]->id : null,
                'is_latest_version' => $i === 5,
                'uploaded_by' => $this->user->id,
            ]);
        }

        // Get all versions from middle version (v3)
        $allVersions = $versions[2]->getAllVersions();

        $this->assertCount(5, $allVersions);
        $this->assertEquals(1, $allVersions[0]->version_number);
        $this->assertEquals(2, $allVersions[1]->version_number);
        $this->assertEquals(3, $allVersions[2]->version_number);
        $this->assertEquals(4, $allVersions[3]->version_number);
        $this->assertEquals(5, $allVersions[4]->version_number);
    }

    /**
     * Test: Only latest version is marked as latest
     */
    public function test_only_latest_version_marked_as_latest()
    {
        $v1 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v1.pdf',
            'file_path' => 'test/test-v1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'is_latest_version' => false,
            'uploaded_by' => $this->user->id,
        ]);

        $v2 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v2.pdf',
            'file_path' => 'test/test-v2.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 2,
            'previous_version_id' => $v1->id,
            'is_latest_version' => false,
            'uploaded_by' => $this->user->id,
        ]);

        $v3 = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test-v3.pdf',
            'file_path' => 'test/test-v3.pdf',
            'file_size' => 3072,
            'mime_type' => 'application/pdf',
            'page_count' => 3,
            'version_number' => 3,
            'previous_version_id' => $v2->id,
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        $latestVersions = PdfDocument::where('is_latest_version', true)->get();

        $this->assertCount(1, $latestVersions);
        $this->assertEquals($v3->id, $latestVersions->first()->id);
    }

    /**
     * Test: Version metadata with all fields
     */
    public function test_version_metadata_with_all_fields()
    {
        $metadata = [
            'version_notes' => 'Major layout revision',
            'migrate_annotations' => true,
            'migration_date' => now()->toIso8601String(),
            'migrated_by' => $this->user->id,
            'changes' => [
                'Added new room',
                'Removed old pantry',
                'Resized kitchen',
            ],
        ];

        $pdf = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test.pdf',
            'file_path' => 'test/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'version_number' => 2,
            'version_metadata' => $metadata,
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertEquals('Major layout revision', $pdf->version_metadata['version_notes']);
        $this->assertTrue($pdf->version_metadata['migrate_annotations']);
        $this->assertIsArray($pdf->version_metadata['changes']);
        $this->assertCount(3, $pdf->version_metadata['changes']);
    }
}
