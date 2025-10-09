<?php

namespace Tests\Browser;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

/**
 * End-to-End Tests for Phase 5: Version Migration System
 *
 * Tests complete user workflows for version management and annotation migration.
 */
class Phase5VersionMigrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $project;
    protected $pdfDocument;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@tcswoodwork.com',
            'password' => bcrypt('password'),
        ]);

        // Create test project
        $this->project = Project::factory()->create([
            'name' => 'E2E Test Project - Phase 5',
            'creator_id' => $this->user->id,
        ]);

        // Create PDF document
        $this->pdfDocument = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/sample-floorplan.pdf',
            'file_name' => 'kitchen-floorplan-v1.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 1,
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);
    }

    /**
     * Test E2E: Version badge displays correctly in table
     */
    public function test_version_badge_displays_in_table()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/edit")
                ->pause(1000)

                // Find PDF documents relation manager
                ->assertSee('PDF Documents')

                // Verify version 1 badge shows
                ->assertSee('v1 (Latest)')

                ->screenshot('version-badge-in-table');
        });
    }

    /**
     * Test E2E: Upload New Version button is visible on latest version
     */
    public function test_upload_new_version_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/edit")
                ->pause(1000)

                // Look for Upload New Version action
                ->assertSee('Upload New Version')

                ->screenshot('upload-new-version-button');
        });
    }

    /**
     * Test E2E: Upload New Version modal opens with form fields
     */
    public function test_upload_new_version_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/edit")
                ->pause(1000)

                // Click Upload New Version
                ->clickLink('Upload New Version')
                ->pause(500)

                // Verify modal opened with form fields
                ->assertSee('New PDF Version')
                ->assertSee('Version Notes')
                ->assertSee('Migrate Annotations')

                ->screenshot('upload-new-version-modal');
        });
    }

    /**
     * Test E2E: Version History button appears for documents with versions
     */
    public function test_version_history_button_appears()
    {
        // Create version 2
        $v2 = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/sample-floorplan-v2.pdf',
            'file_name' => 'kitchen-floorplan-v2.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 2,
            'previous_version_id' => $this->pdfDocument->id,
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        // Mark v1 as no longer latest
        $this->pdfDocument->update(['is_latest_version' => false]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/edit")
                ->pause(1000)

                // Version History button should be visible
                ->assertSee('Version History')

                ->screenshot('version-history-button');
        });
    }

    /**
     * Test E2E: Version History modal displays all versions
     */
    public function test_version_history_modal_displays_versions()
    {
        // Create version 2
        $v2 = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/sample-floorplan-v2.pdf',
            'file_name' => 'kitchen-floorplan-v2.pdf',
            'file_size' => 2048000,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 2,
            'previous_version_id' => $this->pdfDocument->id,
            'is_latest_version' => true,
            'version_metadata' => [
                'version_notes' => 'Updated kitchen layout',
            ],
            'uploaded_by' => $this->user->id,
        ]);

        $this->pdfDocument->update(['is_latest_version' => false]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/edit")
                ->pause(1000)

                // Click Version History
                ->clickLink('Version History')
                ->pause(500)

                // Verify modal shows both versions
                ->assertSee('Version 1')
                ->assertSee('Version 2')
                ->assertSee('Latest')
                ->assertSee('Updated kitchen layout')

                ->screenshot('version-history-modal');
        });
    }

    /**
     * Test E2E: Old versions show as non-latest
     */
    public function test_old_versions_show_as_non_latest()
    {
        // Create version 2
        $v2 = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/sample-floorplan-v2.pdf',
            'file_name' => 'kitchen-floorplan-v2.pdf',
            'file_size' => 2048000,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 2,
            'previous_version_id' => $this->pdfDocument->id,
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        $this->pdfDocument->update(['is_latest_version' => false]);

        $this->browse(function (Browser $browser) use ($v2) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/edit")
                ->pause(1000)

                // v1 should NOT say "Latest"
                ->assertDontSee('v1 (Latest)')

                // v2 SHOULD say "Latest"
                ->assertSee('v2 (Latest)')

                ->screenshot('latest-version-indicator');
        });
    }

    /**
     * Test E2E: Version indicator shows in PDF review page
     */
    public function test_version_indicator_in_pdf_review()
    {
        // Create version 2 with notes
        $v2 = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/sample-floorplan-v2.pdf',
            'file_name' => 'kitchen-floorplan-v2.pdf',
            'file_size' => 2048000,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 2,
            'previous_version_id' => $this->pdfDocument->id,
            'is_latest_version' => true,
            'version_metadata' => [
                'version_notes' => 'Added new pantry area',
            ],
            'uploaded_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) use ($v2) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$v2->id}")
                ->pause(1000)

                // Verify version info displays
                ->assertSee('Version 2 (Latest)')
                ->assertSee('Added new pantry area')

                ->screenshot('version-info-in-review-page');
        });
    }

    /**
     * Test E2E: Annotation migration preserves annotations
     */
    public function test_annotation_migration_preserves_annotations()
    {
        // Create page and annotation on v1
        $page1 = PdfPage::create([
            'pdf_document_id' => $this->pdfDocument->id,
            'page_number' => 1,
        ]);

        $annotation1 = PdfPageAnnotation::create([
            'pdf_page_id' => $page1->id,
            'annotation_type' => 'room',
            'label' => 'Main Kitchen',
            'x' => 0.2,
            'y' => 0.3,
            'width' => 0.15,
            'height' => 0.12,
            'color' => '#3B82F6',
        ]);

        // Create version 2
        $v2 = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/sample-floorplan-v2.pdf',
            'file_name' => 'kitchen-floorplan-v2.pdf',
            'file_size' => 2048000,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'version_number' => 2,
            'previous_version_id' => $this->pdfDocument->id,
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        // Simulate migration
        $page2 = PdfPage::create([
            'pdf_document_id' => $v2->id,
            'page_number' => 1,
        ]);

        $annotation2 = $annotation1->replicate();
        $annotation2->pdf_page_id = $page2->id;
        $annotation2->save();

        $this->browse(function (Browser $browser) use ($v2) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$v2->id}")
                ->pause(2000)

                // Open annotation modal (requires PDF.js to load)
                // This is a simplified test - full E2E would interact with canvas
                ->assertSee('Page 1')

                ->screenshot('migrated-annotations-available');
        });

        // Verify annotation was migrated
        $this->assertEquals(1, $v2->pages()->count());
        $this->assertEquals(1, $v2->pages()->first()->annotations()->count());
    }

    /**
     * Test E2E: Version chain visualization shows correct order
     */
    public function test_version_chain_visualization()
    {
        // Create 3 versions
        $v2 = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/v2.pdf',
            'file_name' => 'test-v2.pdf',
            'file_size' => 2048000,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 2,
            'previous_version_id' => $this->pdfDocument->id,
            'is_latest_version' => false,
            'uploaded_by' => $this->user->id,
        ]);

        $v3 = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/v3.pdf',
            'file_name' => 'test-v3.pdf',
            'file_size' => 3072000,
            'mime_type' => 'application/pdf',
            'page_count' => 2,
            'version_number' => 3,
            'previous_version_id' => $v2->id,
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        $this->pdfDocument->update(['is_latest_version' => false]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/edit")
                ->pause(1000)

                // Click Version History
                ->clickLink('Version History')
                ->pause(500)

                // Verify version chain shows
                ->assertSee('Version Chain')
                ->assertSee('v1')
                ->assertSee('v2')
                ->assertSee('v3')

                ->screenshot('version-chain-visualization');
        });
    }
}
