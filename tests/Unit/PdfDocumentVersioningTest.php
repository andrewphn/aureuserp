<?php

namespace Tests\Unit;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

/**
 * Unit Tests for Phase 5: PDF Document Versioning
 *
 * Tests version management, relationships, and annotation migration logic.
 */
class PdfDocumentVersioningTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing user from database to avoid factory issues
        $this->user = User::first() ?? User::factory()->create();

        // Create project without using factory to avoid complex dependencies
        $this->project = new Project();
        $this->project->name = 'Test Project';
        $this->project->creator_id = $this->user->id;
        $this->project->save();
    }

    /**
     * Test: New PDF document has default version number of 1
     */
    public function test_new_pdf_has_version_number_one()
    {
        $pdf = PdfDocument::create([
            'module_type' => get_class($this->project),
            'module_id' => $this->project->id,
            'file_name' => 'test.pdf',
            'file_path' => 'test/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'uploaded_by' => $this->user->id,
        ]);

        // Refresh to get database defaults
        $pdf->refresh();

        $this->assertEquals(1, $pdf->version_number);
        $this->assertTrue($pdf->is_latest_version);
        $this->assertNull($pdf->previous_version_id);
    }

    /**
     * Test: Can create a new version of an existing PDF
     */
    public function test_can_create_new_version()
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

        // Mark v1 as no longer latest
        $v1->update(['is_latest_version' => false]);

        // Create version 2
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
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertEquals(2, $v2->version_number);
        $this->assertEquals($v1->id, $v2->previous_version_id);
        $this->assertTrue($v2->is_latest_version);
        $this->assertFalse($v1->fresh()->is_latest_version);
    }

    /**
     * Test: previousVersion() relationship works correctly
     */
    public function test_previous_version_relationship()
    {
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
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertNotNull($v2->previousVersion);
        $this->assertEquals($v1->id, $v2->previousVersion->id);
        $this->assertEquals('test-v1.pdf', $v2->previousVersion->file_name);
    }

    /**
     * Test: nextVersions() relationship works correctly
     */
    public function test_next_versions_relationship()
    {
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
            'uploaded_by' => $this->user->id,
        ]);

        $nextVersions = $v1->nextVersions;
        $this->assertCount(1, $nextVersions);
        $this->assertEquals($v2->id, $nextVersions->first()->id);
    }

    /**
     * Test: getAllVersions() returns complete version chain
     */
    public function test_get_all_versions_returns_complete_chain()
    {
        // Create 3 versions
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
            'uploaded_by' => $this->user->id,
        ]);

        // Call from any version should return all 3
        $allVersions = $v2->getAllVersions();

        $this->assertCount(3, $allVersions);
        $this->assertEquals($v1->id, $allVersions[0]->id);
        $this->assertEquals($v2->id, $allVersions[1]->id);
        $this->assertEquals($v3->id, $allVersions[2]->id);
    }

    /**
     * Test: getAllVersions() works from first version
     */
    public function test_get_all_versions_from_first_version()
    {
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
            'uploaded_by' => $this->user->id,
        ]);

        $allVersions = $v1->getAllVersions();

        $this->assertCount(2, $allVersions);
        $this->assertEquals($v1->id, $allVersions[0]->id);
        $this->assertEquals($v2->id, $allVersions[1]->id);
    }

    /**
     * Test: getAllVersions() works from last version
     */
    public function test_get_all_versions_from_last_version()
    {
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
            'uploaded_by' => $this->user->id,
        ]);

        $allVersions = $v2->getAllVersions();

        $this->assertCount(2, $allVersions);
        $this->assertEquals($v1->id, $allVersions[0]->id);
        $this->assertEquals($v2->id, $allVersions[1]->id);
    }

    /**
     * Test: Version metadata is stored correctly
     */
    public function test_version_metadata_storage()
    {
        $metadata = [
            'version_notes' => 'Updated kitchen layout',
            'migrate_annotations' => true,
            'migration_date' => now()->toIso8601String(),
            'migrated_by' => $this->user->id,
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

        $this->assertIsArray($pdf->version_metadata);
        $this->assertEquals('Updated kitchen layout', $pdf->version_metadata['version_notes']);
        $this->assertTrue($pdf->version_metadata['migrate_annotations']);
        $this->assertEquals($this->user->id, $pdf->version_metadata['migrated_by']);
    }

    /**
     * Test: Can query latest version only
     */
    public function test_can_query_latest_version_only()
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
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        $latestOnly = PdfDocument::where('is_latest_version', true)->get();

        $this->assertCount(1, $latestOnly);
        $this->assertEquals($v2->id, $latestOnly->first()->id);
    }
}
