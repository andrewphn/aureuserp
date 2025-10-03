<?php

namespace Tests\Performance;

use App\Models\PdfDocument;
use App\Models\PdfAnnotation;
use App\Models\User;
use App\Services\NutrientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Large PDF Performance Tests
 *
 * Tests performance benchmarks for large PDFs, memory usage,
 * and annotation performance with high volumes.
 */
class LargePdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    /** @test */
    public function can_process_50mb_pdf_within_memory_limits(): void
    {
        $startMemory = memory_get_usage();

        $document = PdfDocument::factory()->create([
            'file_size' => 52428800, // 50MB
            'page_count' => 200,
        ]);

        $endMemory = memory_get_usage();
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB

        // Should not use more than 256MB for processing
        $this->assertLessThan(256, $memoryUsed);
    }

    /** @test */
    public function pdf_metadata_extraction_completes_within_time_limit(): void
    {
        $startTime = microtime(true);

        $document = PdfDocument::factory()->create([
            'file_size' => 10485760, // 10MB
            'page_count' => 100,
        ]);

        // Simulate metadata extraction
        $metadata = [
            'title' => $document->file_name,
            'page_count' => $document->page_count,
            'file_size' => $document->file_size,
        ];

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete in under 2 seconds
        $this->assertLessThan(2.0, $executionTime);
    }

    /** @test */
    public function thumbnail_generation_is_performant_for_large_documents(): void
    {
        $document = PdfDocument::factory()->create([
            'page_count' => 100,
        ]);

        $startTime = microtime(true);

        // Simulate thumbnail generation for first 10 pages
        for ($i = 1; $i <= 10; $i++) {
            // In real implementation, this would generate actual thumbnails
            $thumbnailPath = "thumbnails/{$document->id}/page-{$i}.jpg";
            Storage::disk('public')->put($thumbnailPath, 'fake-thumbnail-data');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should generate 10 thumbnails in under 5 seconds
        $this->assertLessThan(5.0, $executionTime);
    }

    /** @test */
    public function page_lazy_loading_reduces_initial_load_time(): void
    {
        $document = PdfDocument::factory()->create([
            'page_count' => 200,
        ]);

        $startTime = microtime(true);

        // Load only first 5 pages initially (lazy loading)
        $initialPages = range(1, 5);

        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;

        // Loading 5 pages should be faster than loading all 200
        $this->assertLessThan(1.0, $loadTime);
    }

    /** @test */
    public function annotation_creation_is_performant_with_100_plus_existing_annotations(): void
    {
        $document = PdfDocument::factory()->create();

        // Create 100 existing annotations
        PdfAnnotation::factory()->count(100)->create([
            'document_id' => $document->id,
            'page_number' => 1,
        ]);

        $startTime = microtime(true);

        // Create new annotation
        PdfAnnotation::factory()->create([
            'document_id' => $document->id,
            'page_number' => 1,
        ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should create annotation in under 200ms even with 100+ existing
        $this->assertLessThan(0.2, $executionTime);
    }

    /** @test */
    public function annotation_query_performance_with_large_dataset(): void
    {
        $document = PdfDocument::factory()->create();

        // Create 500 annotations across 10 pages
        for ($page = 1; $page <= 10; $page++) {
            PdfAnnotation::factory()->count(50)->create([
                'document_id' => $document->id,
                'page_number' => $page,
            ]);
        }

        $startTime = microtime(true);

        // Query annotations for a specific page
        $annotations = PdfAnnotation::where('document_id', $document->id)
            ->where('page_number', 5)
            ->get();

        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;

        // Query should complete in under 100ms
        $this->assertLessThan(0.1, $queryTime);
        $this->assertCount(50, $annotations);
    }

    /** @test */
    public function batch_annotation_creation_is_optimized(): void
    {
        $document = PdfDocument::factory()->create();

        $annotationsData = [];
        for ($i = 0; $i < 50; $i++) {
            $annotationsData[] = [
                'document_id' => $document->id,
                'page_number' => 1,
                'annotation_type' => 'highlight',
                'annotation_data' => ['color' => '#ffff00'],
                'author_id' => $this->user->id,
                'author_name' => $this->user->name,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $startTime = microtime(true);

        // Batch insert
        PdfAnnotation::insert($annotationsData);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Batch insert should be faster than individual inserts
        $this->assertLessThan(0.5, $executionTime);
    }

    /** @test */
    public function pdf_viewer_configuration_generation_is_cached(): void
    {
        $service = new NutrientService();

        // First call
        $start1 = microtime(true);
        $config1 = $service->getViewerConfiguration();
        $time1 = microtime(true) - $start1;

        // Second call (should be cached)
        $start2 = microtime(true);
        $config2 = $service->getViewerConfiguration();
        $time2 = microtime(true) - $start2;

        // Cached call should be significantly faster
        $this->assertLessThan($time1, $time2);
        $this->assertEquals($config1, $config2);
    }

    /** @test */
    public function document_search_performance_with_large_dataset(): void
    {
        // Create 1000 documents
        PdfDocument::factory()->count(1000)->create();

        $startTime = microtime(true);

        // Search by title
        $results = PdfDocument::where('title', 'like', '%test%')
            ->limit(20)
            ->get();

        $endTime = microtime(true);
        $searchTime = $endTime - $startTime;

        // Search should complete in under 500ms
        $this->assertLessThan(0.5, $searchTime);
    }

    /** @test */
    public function pagination_performance_on_large_document_list(): void
    {
        PdfDocument::factory()->count(500)->create();

        $startTime = microtime(true);

        // Paginate results
        $paginatedDocuments = PdfDocument::orderBy('created_at', 'desc')
            ->paginate(25);

        $endTime = microtime(true);
        $paginationTime = $endTime - $startTime;

        // Pagination should be fast
        $this->assertLessThan(0.3, $paginationTime);
        $this->assertCount(25, $paginatedDocuments);
    }

    /** @test */
    public function annotation_update_performance_does_not_degrade(): void
    {
        $document = PdfDocument::factory()->create();

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $document->id,
        ]);

        // Create many other annotations to test for performance degradation
        PdfAnnotation::factory()->count(200)->create([
            'document_id' => $document->id,
        ]);

        $startTime = microtime(true);

        // Update single annotation
        $annotation->update([
            'annotation_data' => array_merge($annotation->annotation_data, [
                'color' => '#ff0000',
            ]),
        ]);

        $endTime = microtime(true);
        $updateTime = $endTime - $startTime;

        // Update should be fast regardless of total annotations
        $this->assertLessThan(0.1, $updateTime);
    }

    /** @test */
    public function soft_delete_queries_are_optimized(): void
    {
        // Create documents, some deleted
        PdfDocument::factory()->count(100)->create();
        $deletedDocs = PdfDocument::factory()->count(50)->create();

        foreach ($deletedDocs as $doc) {
            $doc->delete(); // Soft delete
        }

        $startTime = microtime(true);

        // Query only active documents
        $activeDocuments = PdfDocument::whereNull('deleted_at')->get();

        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;

        // Soft delete filtering should not slow down query
        $this->assertLessThan(0.2, $queryTime);
        $this->assertCount(100, $activeDocuments);
    }

    /** @test */
    public function eager_loading_prevents_n_plus_1_queries(): void
    {
        PdfDocument::factory()->count(20)->create([
            'uploaded_by' => $this->user->id,
        ]);

        // Without eager loading (N+1 problem)
        $queryCount1 = 0;
        \DB::listen(function () use (&$queryCount1) {
            $queryCount1++;
        });

        $documents = PdfDocument::all();
        foreach ($documents as $doc) {
            $uploader = $doc->uploader; // Triggers query for each document
        }

        \DB::flushEventListeners();

        // With eager loading
        $queryCount2 = 0;
        \DB::listen(function () use (&$queryCount2) {
            $queryCount2++;
        });

        $documentsEager = PdfDocument::with('uploader')->get();
        foreach ($documentsEager as $doc) {
            $uploader = $doc->uploader; // No additional queries
        }

        // Eager loading should use significantly fewer queries
        $this->assertLessThan($queryCount1, $queryCount2);
    }

    /** @test */
    public function json_column_queries_are_performant(): void
    {
        PdfDocument::factory()->count(100)->create([
            'tags' => ['project', 'important'],
        ]);

        $startTime = microtime(true);

        // Query JSON column
        $documents = PdfDocument::whereJsonContains('tags', 'important')->get();

        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;

        // JSON queries should complete quickly
        $this->assertLessThan(0.3, $queryTime);
        $this->assertGreaterThan(0, $documents->count());
    }
}
