<?php

namespace Tests\Unit\Models;

use App\Models\PdfAnnotation;
use App\Models\PdfDocument;
use App\Models\PdfDocumentActivity;
use App\Models\PdfPage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PdfDocumentTest extends TestCase
{
    use DatabaseTransactions;

    protected PdfDocument $document;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->document = PdfDocument::factory()->create([
            'uploaded_by' => $this->user->id,
            'file_size' => 1048576, // 1MB
        ]);
    }

    /** @test */
    public function it_can_be_created_with_valid_attributes(): void
    {
        $document = PdfDocument::create([
            'module_type' => 'App\\Models\\Project',
            'module_id' => 1,
            'file_name' => 'test.pdf',
            'file_path' => 'pdfs/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 5,
            'uploaded_by' => $this->user->id,
            'tags' => ['project', 'drawing'],
            'metadata' => ['version' => '1.0'],
        ]);

        $this->assertInstanceOf(PdfDocument::class, $document);
        $this->assertEquals('test.pdf', $document->file_name);
        $this->assertIsArray($document->tags);
        $this->assertIsArray($document->metadata);
    }

    /** @test */
    public function it_casts_tags_to_array(): void
    {
        $document = PdfDocument::factory()->create([
            'tags' => ['tag1', 'tag2', 'tag3'],
        ]);

        $this->assertIsArray($document->tags);
        $this->assertCount(3, $document->tags);
        $this->assertEquals(['tag1', 'tag2', 'tag3'], $document->tags);
    }

    /** @test */
    public function it_casts_metadata_to_array(): void
    {
        $metadata = ['version' => '1.0', 'author' => 'Test User'];
        $document = PdfDocument::factory()->create([
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($document->metadata);
        $this->assertEquals($metadata, $document->metadata);
    }

    /** @test */
    public function it_has_many_pages(): void
    {
        PdfPage::factory()->count(3)->create([
            'document_id' => $this->document->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->document->pages);
        $this->assertCount(3, $this->document->pages);
        $this->assertInstanceOf(PdfPage::class, $this->document->pages->first());
    }

    /** @test */
    public function it_has_many_annotations(): void
    {
        PdfAnnotation::factory()->count(5)->create([
            'document_id' => $this->document->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->document->annotations);
        $this->assertCount(5, $this->document->annotations);
        $this->assertInstanceOf(PdfAnnotation::class, $this->document->annotations->first());
    }

    /** @test */
    public function it_has_many_activities(): void
    {
        PdfDocumentActivity::factory()->count(3)->create([
            'document_id' => $this->document->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->document->activities);
        $this->assertCount(3, $this->document->activities);
        $this->assertInstanceOf(PdfDocumentActivity::class, $this->document->activities->first());
    }

    /** @test */
    public function it_belongs_to_uploader(): void
    {
        $this->assertInstanceOf(User::class, $this->document->uploader);
        $this->assertEquals($this->user->id, $this->document->uploader->id);
    }

    /** @test */
    public function it_has_polymorphic_module_relationship(): void
    {
        // Skip if Project plugin tables don't exist
        if (!\Illuminate\Support\Facades\Schema::hasTable('projects_project_stages')) {
            $this->markTestSkipped('Projects plugin tables not available');
        }

        $project = \Webkul\Project\Models\Project::factory()->create();

        $document = PdfDocument::factory()->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        $this->assertInstanceOf(\Webkul\Project\Models\Project::class, $document->module);
        $this->assertEquals($project->id, $document->module->id);
    }

    /** @test */
    public function scope_for_module_filters_by_type_and_id(): void
    {
        // Skip if Project plugin tables don't exist
        if (!\Illuminate\Support\Facades\Schema::hasTable('projects_project_stages')) {
            $this->markTestSkipped('Projects plugin tables not available');
        }

        $project = \Webkul\Project\Models\Project::factory()->create();

        PdfDocument::factory()->count(3)->create([
            'module_type' => get_class($project),
            'module_id' => $project->id,
        ]);

        PdfDocument::factory()->count(2)->create([
            'module_type' => 'App\\Models\\Other',
            'module_id' => 999,
        ]);

        $documents = PdfDocument::forModule(get_class($project), $project->id)->get();

        $this->assertCount(3, $documents);
        $documents->each(function ($doc) use ($project) {
            $this->assertEquals(get_class($project), $doc->module_type);
            $this->assertEquals($project->id, $doc->module_id);
        });
    }

    /** @test */
    public function scope_by_uploader_filters_by_user(): void
    {
        $otherUser = User::factory()->create();

        PdfDocument::factory()->count(3)->create([
            'uploaded_by' => $this->user->id,
        ]);

        PdfDocument::factory()->count(2)->create([
            'uploaded_by' => $otherUser->id,
        ]);

        $documents = PdfDocument::byUploader($this->user->id)->get();

        $this->assertCount(4, $documents); // 3 + 1 from setUp
        $documents->each(function ($doc) {
            $this->assertEquals($this->user->id, $doc->uploaded_by);
        });
    }

    /** @test */
    public function scope_recent_orders_by_created_at_desc(): void
    {
        // Use a fixed base time to avoid timezone issues
        $baseTime = \Carbon\Carbon::parse('2024-01-15 12:00:00');

        $old = PdfDocument::factory()->create([
            'created_at' => $baseTime->copy()->subDays(10),
        ]);

        $recent = PdfDocument::factory()->create([
            'created_at' => $baseTime->copy()->subDays(1),
        ]);

        $newest = PdfDocument::factory()->create([
            'created_at' => $baseTime,
        ]);

        $documents = PdfDocument::recent(3)->get();

        // Verify ordering by checking timestamps are in descending order
        $this->assertCount(3, $documents);
        $this->assertTrue($documents[0]->created_at >= $documents[1]->created_at);
        $this->assertTrue($documents[1]->created_at >= $documents[2]->created_at);

        // Verify our created documents are in the results
        $ids = $documents->pluck('id')->toArray();
        $this->assertContains($newest->id, $ids);
        $this->assertContains($recent->id, $ids);
        $this->assertContains($old->id, $ids);
    }

    /** @test */
    public function scope_recent_limits_results(): void
    {
        PdfDocument::factory()->count(15)->create();

        $documents = PdfDocument::recent(5)->get();

        $this->assertCount(5, $documents);
    }

    /** @test */
    public function accessor_formatted_file_size_returns_bytes(): void
    {
        $document = PdfDocument::factory()->create([
            'file_size' => 512,
        ]);

        $this->assertEquals('512 B', $document->formatted_file_size);
    }

    /** @test */
    public function accessor_formatted_file_size_returns_kilobytes(): void
    {
        $document = PdfDocument::factory()->create([
            'file_size' => 1024 * 5, // 5KB
        ]);

        $this->assertEquals('5 KB', $document->formatted_file_size);
    }

    /** @test */
    public function accessor_formatted_file_size_returns_megabytes(): void
    {
        $document = PdfDocument::factory()->create([
            'file_size' => 1024 * 1024 * 3, // 3MB
        ]);

        $this->assertEquals('3 MB', $document->formatted_file_size);
    }

    /** @test */
    public function accessor_formatted_file_size_returns_gigabytes(): void
    {
        $document = PdfDocument::factory()->create([
            'file_size' => 1024 * 1024 * 1024 * 2, // 2GB
        ]);

        $this->assertEquals('2 GB', $document->formatted_file_size);
    }

    /** @test */
    public function accessor_formatted_file_size_rounds_decimals(): void
    {
        $document = PdfDocument::factory()->create([
            'file_size' => 1024 * 1024 * 1.5, // 1.5MB
        ]);

        $this->assertEquals('1.5 MB', $document->formatted_file_size);
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $document = PdfDocument::factory()->create();
        $documentId = $document->id;

        $document->delete();

        $this->assertSoftDeleted('pdf_documents', ['id' => $documentId]);
        $this->assertNotNull($document->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete(): void
    {
        $document = PdfDocument::factory()->create();
        $documentId = $document->id;

        $document->delete();
        $this->assertSoftDeleted('pdf_documents', ['id' => $documentId]);

        $document->restore();

        $this->assertDatabaseHas('pdf_documents', [
            'id' => $documentId,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function mass_assignment_protects_non_fillable_attributes(): void
    {
        $document = PdfDocument::create([
            'id' => 999, // Not fillable
            'file_name' => 'test.pdf',
            'file_path' => 'pdfs/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'page_count' => 1,
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertNotEquals(999, $document->id);
    }
}
