<?php

namespace Tests\Unit\Models;

use App\Models\PdfAnnotation;
use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PdfAnnotationTest extends TestCase
{
    use DatabaseTransactions;

    protected PdfDocument $document;
    protected PdfPage $page;
    protected User $user;
    protected PdfAnnotation $annotation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->document = PdfDocument::factory()->create();
        $this->page = PdfPage::factory()->create([
            'document_id' => $this->document->id,
            'page_number' => 1,
        ]);

        $this->annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'page_number' => 1,
            'author_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_be_created_with_valid_attributes(): void
    {
        $annotation = PdfAnnotation::create([
            'document_id' => $this->document->id,
            'page_number' => 1,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
            'annotation_data' => [
                'color' => '#ffeb3b',
                'position' => ['x' => 100, 'y' => 200],
                'text' => 'Highlighted text',
            ],
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
        ]);

        $this->assertInstanceOf(PdfAnnotation::class, $annotation);
        $this->assertEquals(PdfAnnotation::TYPE_HIGHLIGHT, $annotation->annotation_type);
        $this->assertIsArray($annotation->annotation_data);
    }

    /** @test */
    public function it_has_annotation_type_constants(): void
    {
        $this->assertEquals('highlight', PdfAnnotation::TYPE_HIGHLIGHT);
        $this->assertEquals('text', PdfAnnotation::TYPE_TEXT);
        $this->assertEquals('drawing', PdfAnnotation::TYPE_DRAWING);
        $this->assertEquals('arrow', PdfAnnotation::TYPE_ARROW);
        $this->assertEquals('rectangle', PdfAnnotation::TYPE_RECTANGLE);
        $this->assertEquals('circle', PdfAnnotation::TYPE_CIRCLE);
        $this->assertEquals('stamp', PdfAnnotation::TYPE_STAMP);
    }

    /** @test */
    public function it_casts_annotation_data_to_array(): void
    {
        $data = [
            'color' => '#ff0000',
            'text' => 'Test annotation',
            'position' => ['x' => 50, 'y' => 100],
        ];

        $annotation = PdfAnnotation::factory()->create([
            'annotation_data' => $data,
        ]);

        $this->assertIsArray($annotation->annotation_data);
        $this->assertEquals($data, $annotation->annotation_data);
    }

    /** @test */
    public function it_belongs_to_document(): void
    {
        $this->assertInstanceOf(PdfDocument::class, $this->annotation->document);
        $this->assertEquals($this->document->id, $this->annotation->document->id);
    }

    /** @test */
    public function it_belongs_to_page(): void
    {
        $page = $this->annotation->page;

        $this->assertInstanceOf(PdfPage::class, $page);
        $this->assertEquals($this->document->id, $page->document_id);
        $this->assertEquals(1, $page->page_number);
    }

    /** @test */
    public function it_belongs_to_author(): void
    {
        $this->assertInstanceOf(User::class, $this->annotation->author);
        $this->assertEquals($this->user->id, $this->annotation->author->id);
    }

    /** @test */
    public function scope_by_author_filters_by_user(): void
    {
        $otherUser = User::factory()->create();

        PdfAnnotation::factory()->count(3)->create([
            'document_id' => $this->document->id,
            'author_id' => $this->user->id,
        ]);

        PdfAnnotation::factory()->count(2)->create([
            'document_id' => $this->document->id,
            'author_id' => $otherUser->id,
        ]);

        $annotations = PdfAnnotation::byAuthor($this->user->id)->get();

        $this->assertCount(4, $annotations); // 3 + 1 from setUp
        $annotations->each(function ($annotation) {
            $this->assertEquals($this->user->id, $annotation->author_id);
        });
    }

    /** @test */
    public function scope_by_type_filters_by_annotation_type(): void
    {
        PdfAnnotation::factory()->count(3)->create([
            'document_id' => $this->document->id,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
        ]);

        PdfAnnotation::factory()->count(2)->create([
            'document_id' => $this->document->id,
            'annotation_type' => PdfAnnotation::TYPE_TEXT,
        ]);

        $highlights = PdfAnnotation::byType(PdfAnnotation::TYPE_HIGHLIGHT)->get();

        $this->assertCount(3, $highlights);
        $highlights->each(function ($annotation) {
            $this->assertEquals(PdfAnnotation::TYPE_HIGHLIGHT, $annotation->annotation_type);
        });
    }

    /** @test */
    public function scope_for_page_filters_by_page_number(): void
    {
        PdfAnnotation::factory()->count(3)->create([
            'document_id' => $this->document->id,
            'page_number' => 1,
        ]);

        PdfAnnotation::factory()->count(2)->create([
            'document_id' => $this->document->id,
            'page_number' => 2,
        ]);

        $page1Annotations = PdfAnnotation::forPage(1)->get();

        $this->assertCount(4, $page1Annotations); // 3 + 1 from setUp
        $page1Annotations->each(function ($annotation) {
            $this->assertEquals(1, $annotation->page_number);
        });
    }

    /** @test */
    public function is_type_returns_true_for_matching_type(): void
    {
        $highlight = PdfAnnotation::factory()->create([
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
        ]);

        $this->assertTrue($highlight->isType(PdfAnnotation::TYPE_HIGHLIGHT));
    }

    /** @test */
    public function is_type_returns_false_for_non_matching_type(): void
    {
        $highlight = PdfAnnotation::factory()->create([
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
        ]);

        $this->assertFalse($highlight->isType(PdfAnnotation::TYPE_TEXT));
    }

    /** @test */
    public function get_color_returns_color_from_annotation_data(): void
    {
        $annotation = PdfAnnotation::factory()->create([
            'annotation_data' => ['color' => '#ff0000'],
        ]);

        $this->assertEquals('#ff0000', $annotation->getColor());
    }

    /** @test */
    public function get_color_returns_null_when_not_present(): void
    {
        $annotation = PdfAnnotation::factory()->create([
            'annotation_data' => ['text' => 'No color'],
        ]);

        $this->assertNull($annotation->getColor());
    }

    /** @test */
    public function get_position_returns_position_array(): void
    {
        $position = ['x' => 100, 'y' => 200, 'width' => 50, 'height' => 30];
        $annotation = PdfAnnotation::factory()->create([
            'annotation_data' => ['position' => $position],
        ]);

        $this->assertEquals($position, $annotation->getPosition());
    }

    /** @test */
    public function get_position_returns_null_when_not_present(): void
    {
        $annotation = PdfAnnotation::factory()->create([
            'annotation_data' => ['color' => '#000000'],
        ]);

        $this->assertNull($annotation->getPosition());
    }

    /** @test */
    public function get_text_returns_text_from_annotation_data(): void
    {
        $annotation = PdfAnnotation::factory()->create([
            'annotation_data' => ['text' => 'Sample annotation text'],
        ]);

        $this->assertEquals('Sample annotation text', $annotation->getText());
    }

    /** @test */
    public function get_text_returns_null_when_not_present(): void
    {
        $annotation = PdfAnnotation::factory()->create([
            'annotation_data' => ['color' => '#000000'],
        ]);

        $this->assertNull($annotation->getText());
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $annotation = PdfAnnotation::factory()->create();
        $annotationId = $annotation->id;

        $annotation->delete();

        $this->assertSoftDeleted('pdf_annotations', ['id' => $annotationId]);
        $this->assertNotNull($annotation->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete(): void
    {
        $annotation = PdfAnnotation::factory()->create();
        $annotationId = $annotation->id;

        $annotation->delete();
        $this->assertSoftDeleted('pdf_annotations', ['id' => $annotationId]);

        $annotation->restore();

        $this->assertDatabaseHas('pdf_annotations', [
            'id' => $annotationId,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function mass_assignment_protects_non_fillable_attributes(): void
    {
        $annotation = PdfAnnotation::create([
            'id' => 999, // Not fillable
            'document_id' => $this->document->id,
            'page_number' => 1,
            'annotation_type' => PdfAnnotation::TYPE_TEXT,
            'annotation_data' => ['text' => 'Test'],
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
        ]);

        $this->assertNotEquals(999, $annotation->id);
    }

    /** @test */
    public function it_can_have_multiple_annotation_types(): void
    {
        $types = [
            PdfAnnotation::TYPE_HIGHLIGHT,
            PdfAnnotation::TYPE_TEXT,
            PdfAnnotation::TYPE_DRAWING,
            PdfAnnotation::TYPE_ARROW,
            PdfAnnotation::TYPE_RECTANGLE,
            PdfAnnotation::TYPE_CIRCLE,
            PdfAnnotation::TYPE_STAMP,
        ];

        foreach ($types as $type) {
            $annotation = PdfAnnotation::factory()->create([
                'document_id' => $this->document->id,
                'annotation_type' => $type,
            ]);

            $this->assertEquals($type, $annotation->annotation_type);
            $this->assertTrue($annotation->isType($type));
        }
    }

    /** @test */
    public function scopes_can_be_combined(): void
    {
        // Create annotations for different pages and authors
        $otherUser = User::factory()->create();

        PdfAnnotation::factory()->count(2)->create([
            'document_id' => $this->document->id,
            'page_number' => 1,
            'author_id' => $this->user->id,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
        ]);

        PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'page_number' => 1,
            'author_id' => $otherUser->id,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
        ]);

        PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'page_number' => 2,
            'author_id' => $this->user->id,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
        ]);

        // Combine scopes: page 1, by this user, highlight type
        $annotations = PdfAnnotation::forPage(1)
            ->byAuthor($this->user->id)
            ->byType(PdfAnnotation::TYPE_HIGHLIGHT)
            ->get();

        $this->assertCount(2, $annotations);
        $annotations->each(function ($annotation) {
            $this->assertEquals(1, $annotation->page_number);
            $this->assertEquals($this->user->id, $annotation->author_id);
            $this->assertEquals(PdfAnnotation::TYPE_HIGHLIGHT, $annotation->annotation_type);
        });
    }
}
