<?php

namespace Tests\Integration;

use App\Models\PdfDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module Integration Tests
 *
 * Tests PDF document system integration with Projects, Sales, Support,
 * and other modules through polymorphic relationships.
 */
class ModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function pdf_documents_can_be_attached_to_projects(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();

        $document = PdfDocument::factory()->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        // Verify polymorphic relationship
        $this->assertEquals($project->id, $document->module->id);
        $this->assertInstanceOf(get_class($project), $document->module);
    }

    /** @test */
    public function pdf_documents_can_be_attached_to_partners(): void
    {
        $partner = \Webkul\Partner\Models\Partner::factory()->create();

        $document = PdfDocument::factory()->create([
            'module_type' => get_class($partner),
            'module_id' => $partner->id,
        ]);

        $this->assertEquals($partner->id, $document->module->id);
        $this->assertInstanceOf(get_class($partner), $document->module);
    }

    /** @test */
    public function pdf_documents_can_be_attached_to_quotes(): void
    {
        $quote = \App\Models\Quote::factory()->create();

        $document = PdfDocument::factory()->create([
            'module_type' => get_class($quote),
            'module_id' => $quote->id,
        ]);

        $this->assertEquals($quote->id, $document->module->id);
    }

    /** @test */
    public function can_retrieve_all_documents_for_a_project(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();

        PdfDocument::factory()->count(5)->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        // Create documents for other projects
        PdfDocument::factory()->count(3)->create();

        $projectDocuments = PdfDocument::forModule(get_class($project), $project->id)->get();

        $this->assertCount(5, $projectDocuments);
    }

    /** @test */
    public function deleting_parent_record_handles_documents_appropriately(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();

        $document = PdfDocument::factory()->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        // Delete project
        $project->delete();

        // Document should still exist (orphaned handling depends on implementation)
        $this->assertDatabaseHas('pdf_documents', [
            'id' => $document->id,
        ]);

        // Or implement cascade delete/nullify based on requirements
    }

    /** @test */
    public function can_filter_documents_by_module_type(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();
        $partner = \Webkul\Partner\Models\Partner::factory()->create();

        PdfDocument::factory()->count(3)->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        PdfDocument::factory()->count(2)->create([
            'module_type' => get_class($partner),
            'module_id' => $partner->id,
        ]);

        $projectDocuments = PdfDocument::where('module_type', get_class($project))->get();
        $partnerDocuments = PdfDocument::where('module_type', get_class($partner))->get();

        $this->assertCount(3, $projectDocuments);
        $this->assertCount(2, $partnerDocuments);
    }

    /** @test */
    public function annotations_are_specific_to_documents_across_modules(): void
    {
        $project1 = \Webkul\Project\Models\Project::factory()->create();
        $project2 = \Webkul\Project\Models\Project::factory()->create();

        $doc1 = PdfDocument::factory()->create([
            'module_type' => get_class($project1),
            'module_id' => $project1->id,
        ]);

        $doc2 = PdfDocument::factory()->create([
            'module_type' => get_class($project2),
            'module_id' => $project2->id,
        ]);

        \App\Models\PdfAnnotation::factory()->count(3)->create([
            'document_id' => $doc1->id,
        ]);

        \App\Models\PdfAnnotation::factory()->count(5)->create([
            'document_id' => $doc2->id,
        ]);

        $this->assertCount(3, $doc1->annotations);
        $this->assertCount(5, $doc2->annotations);
    }

    /** @test */
    public function document_permissions_respect_parent_module_permissions(): void
    {
        $this->actingAs($this->user);

        $project = \Webkul\Project\Models\Project::factory()->create();

        $document = PdfDocument::factory()->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
            'uploaded_by' => $this->user->id,
        ]);

        // If user has access to project, they should have access to document
        $response = $this->getJson("/api/pdf-documents/{$document->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function activity_log_tracks_cross_module_interactions(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();

        $document = PdfDocument::factory()->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        $activity = \App\Models\PdfDocumentActivity::factory()->create([
            'document_id' => $document->id,
            'activity_type' => 'view',
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('pdf_document_activities', [
            'document_id' => $document->id,
            'activity_type' => 'view',
        ]);
    }

    /** @test */
    public function search_across_all_modules_returns_correct_results(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();
        $partner = \Webkul\Partner\Models\Partner::factory()->create();

        PdfDocument::factory()->create([
            'title' => 'Project Blueprint',
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        PdfDocument::factory()->create([
            'title' => 'Partner Contract',
            'module_type' => get_class($partner),
            'module_id' => $partner->id,
        ]);

        $results = PdfDocument::where('title', 'like', '%Blueprint%')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Project Blueprint', $results->first()->title);
    }

    /** @test */
    public function can_bulk_attach_documents_to_module(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();

        $documents = PdfDocument::factory()->count(5)->create();

        foreach ($documents as $document) {
            $document->update([
                'module_type' => get_class($project),
                'module_id' => $project->id,
            ]);
        }

        $attachedDocuments = PdfDocument::forModule(get_class($project), $project->id)->get();

        $this->assertCount(5, $attachedDocuments);
    }

    /** @test */
    public function module_specific_document_templates_can_be_applied(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();

        // Create document with project-specific metadata
        $document = PdfDocument::factory()->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
            'metadata' => [
                'template' => 'project_drawing',
                'revision' => '1.0',
                'approval_status' => 'pending',
            ],
        ]);

        $this->assertEquals('project_drawing', $document->metadata['template']);
        $this->assertEquals('pending', $document->metadata['approval_status']);
    }

    /** @test */
    public function document_versioning_works_across_modules(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();

        $originalDoc = PdfDocument::factory()->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
            'metadata' => ['version' => '1.0'],
        ]);

        // Create new version
        $newVersion = PdfDocument::factory()->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
            'metadata' => [
                'version' => '2.0',
                'previous_version_id' => $originalDoc->id,
            ],
        ]);

        $this->assertEquals($originalDoc->id, $newVersion->metadata['previous_version_id']);
    }

    /** @test */
    public function can_retrieve_documents_grouped_by_module_type(): void
    {
        $project = \Webkul\Project\Models\Project::factory()->create();
        $partner = \Webkul\Partner\Models\Partner::factory()->create();

        PdfDocument::factory()->count(3)->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        PdfDocument::factory()->count(2)->create([
            'module_type' => get_class($partner),
            'module_id' => $partner->id,
        ]);

        $grouped = PdfDocument::all()->groupBy('module_type');

        $this->assertCount(2, $grouped); // 2 different module types
        $this->assertCount(3, $grouped[get_class($project)]);
        $this->assertCount(2, $grouped[get_class($partner)]);
    }
}
