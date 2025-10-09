<?php

namespace Tests\Feature;

use App\Models\PdfAnnotationHistory;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use App\Models\PdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Webkul\User\Models\User;

class Phase6IntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected PdfDocument $pdfDocument;
    protected PdfPage $pdfPage;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test PDF document
        $this->pdfDocument = PdfDocument::create([
            'file_name' => 'test.pdf',
            'file_path' => 'test/test.pdf',
            'file_size' => 12345,
            'module_type' => 'App\Models\Project',
            'module_id' => 1,
        ]);

        // Create test PDF page
        $this->pdfPage = PdfPage::create([
            'document_id' => $this->pdfDocument->id,
            'page_number' => 1,
            'width' => 800,
            'height' => 600,
        ]);
    }

    /** @test */
    public function test_pdf_annotation_history_model_can_log_action()
    {
        $this->actingAs($this->user);

        $historyEntry = PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'created',
            beforeData: null,
            afterData: ['id' => 1, 'text' => 'Kitchen'],
            annotationId: null,
            metadata: ['shift_key' => true]
        );

        $this->assertDatabaseHas('pdf_annotation_history', [
            'pdf_page_id' => $this->pdfPage->id,
            'action' => 'created',
            'user_id' => $this->user->id,
        ]);

        $this->assertNotNull($historyEntry->ip_address);
        $this->assertNotNull($historyEntry->user_agent);
        $this->assertEquals(['shift_key' => true], $historyEntry->metadata);
    }

    /** @test */
    public function test_history_for_page_returns_all_entries()
    {
        $this->actingAs($this->user);

        // Create multiple history entries
        PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'created',
            afterData: ['id' => 1]
        );

        PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'updated',
            beforeData: ['id' => 1, 'x' => 0.1],
            afterData: ['id' => 1, 'x' => 0.2]
        );

        PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'deleted',
            beforeData: ['id' => 1],
            annotationId: 1
        );

        $history = PdfAnnotationHistory::forPage($this->pdfPage->id);

        $this->assertCount(3, $history);
        $this->assertEquals('deleted', $history->first()->action); // Most recent first
        $this->assertEquals('created', $history->last()->action);
    }

    /** @test */
    public function test_history_for_annotation_filters_correctly()
    {
        $this->actingAs($this->user);

        // Create annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'x' => 0.1,
            'y' => 0.2,
            'width' => 0.3,
            'height' => 0.4,
            'label' => 'Kitchen',
        ]);

        // Log history
        PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'created',
            afterData: $annotation->toArray(),
            annotationId: $annotation->id
        );

        PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'updated',
            beforeData: ['x' => 0.1],
            afterData: ['x' => 0.2],
            annotationId: $annotation->id
        );

        $annotationHistory = PdfAnnotationHistory::forAnnotation($annotation->id);

        $this->assertCount(2, $annotationHistory);
        $this->assertEquals($annotation->id, $annotationHistory->first()->annotation_id);
    }

    /** @test */
    public function test_api_endpoint_returns_annotation_history()
    {
        $this->actingAs($this->user);

        // Create history
        PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'created',
            afterData: ['id' => 1, 'text' => 'Kitchen']
        );

        $response = $this->getJson("/api/pdf/page/{$this->pdfPage->id}/annotations/history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'history' => [
                    '*' => [
                        'id',
                        'annotation_id',
                        'action',
                        'user',
                        'before_data',
                        'after_data',
                        'metadata',
                        'created_at',
                        'created_at_human',
                    ]
                ],
                'count'
            ]);

        $this->assertEquals(1, $response->json('count'));
        $this->assertEquals('created', $response->json('history.0.action'));
    }

    /** @test */
    public function test_save_annotations_logs_history_for_created_annotations()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", [
            'annotations' => [
                [
                    'annotation_type' => 'room',
                    'x' => 0.1,
                    'y' => 0.2,
                    'width' => 0.3,
                    'height' => 0.4,
                    'text' => 'Kitchen',
                    'room_type' => 'kitchen',
                    'color' => '#FF0000',
                ]
            ]
        ]);

        $response->assertStatus(201);

        // Check history was logged
        $this->assertDatabaseHas('pdf_annotation_history', [
            'pdf_page_id' => $this->pdfPage->id,
            'action' => 'created',
        ]);

        $history = PdfAnnotationHistory::forPage($this->pdfPage->id);
        $this->assertGreaterThan(0, $history->count());
    }

    /** @test */
    public function test_save_annotations_logs_deletion_before_replacing()
    {
        $this->actingAs($this->user);

        // Create initial annotation
        $existingAnnotation = PdfPageAnnotation::create([
            'pdf_page_id' => $this->pdfPage->id,
            'annotation_type' => 'room',
            'x' => 0.1,
            'y' => 0.2,
            'width' => 0.3,
            'height' => 0.4,
            'label' => 'Old Kitchen',
        ]);

        // Save new annotations (which deletes old ones)
        $response = $this->postJson("/api/pdf/page/{$this->pdfPage->id}/annotations", [
            'annotations' => [
                [
                    'annotation_type' => 'room',
                    'x' => 0.5,
                    'y' => 0.6,
                    'width' => 0.2,
                    'height' => 0.3,
                    'text' => 'New Kitchen',
                    'room_type' => 'kitchen',
                    'color' => '#00FF00',
                ]
            ]
        ]);

        $response->assertStatus(201);

        // Check deletion was logged
        $this->assertDatabaseHas('pdf_annotation_history', [
            'pdf_page_id' => $this->pdfPage->id,
            'action' => 'deleted',
            'annotation_id' => $existingAnnotation->id,
        ]);

        // Check creation was logged
        $this->assertDatabaseHas('pdf_annotation_history', [
            'pdf_page_id' => $this->pdfPage->id,
            'action' => 'created',
        ]);
    }

    /** @test */
    public function test_pdf_page_has_chatter_trait()
    {
        $this->assertTrue(method_exists($this->pdfPage, 'messages'));
        $this->assertTrue(method_exists($this->pdfPage, 'addMessage'));
        $this->assertTrue(method_exists($this->pdfPage, 'activities'));
    }

    /** @test */
    public function test_pdf_page_can_add_chatter_message()
    {
        $this->actingAs($this->user);

        $message = $this->pdfPage->addMessage([
            'type' => 'comment',
            'subject' => 'Test Comment',
            'body' => 'This is a test comment on the PDF page',
        ]);

        $this->assertNotNull($message);
        $this->assertDatabaseHas('chatter_messages', [
            'messageable_type' => PdfPage::class,
            'messageable_id' => $this->pdfPage->id,
            'subject' => 'Test Comment',
            'body' => 'This is a test comment on the PDF page',
        ]);
    }

    /** @test */
    public function test_pdf_page_messages_polymorphic_relationship()
    {
        $this->actingAs($this->user);

        $this->pdfPage->addMessage([
            'type' => 'comment',
            'subject' => 'Message 1',
            'body' => 'First message',
        ]);

        $this->pdfPage->addMessage([
            'type' => 'comment',
            'subject' => 'Message 2',
            'body' => 'Second message',
        ]);

        $messages = $this->pdfPage->messages;

        $this->assertCount(2, $messages);
        $this->assertEquals('Message 2', $messages->first()->subject); // Newest first
        $this->assertEquals('Message 1', $messages->last()->subject);
    }

    /** @test */
    public function test_history_entry_includes_user_relationship()
    {
        $this->actingAs($this->user);

        $historyEntry = PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'created',
            afterData: ['id' => 1]
        );

        $historyEntry->load('user');

        $this->assertNotNull($historyEntry->user);
        $this->assertEquals($this->user->id, $historyEntry->user->id);
        $this->assertEquals($this->user->name, $historyEntry->user->name);
    }

    /** @test */
    public function test_history_action_enum_values_are_valid()
    {
        $this->actingAs($this->user);

        $validActions = ['created', 'updated', 'deleted', 'moved', 'resized', 'selected', 'copied', 'pasted'];

        foreach ($validActions as $action) {
            $historyEntry = PdfAnnotationHistory::logAction(
                pdfPageId: $this->pdfPage->id,
                action: $action,
                afterData: ['id' => 1]
            );

            $this->assertEquals($action, $historyEntry->action);
        }
    }

    /** @test */
    public function test_history_before_and_after_data_stored_as_json()
    {
        $this->actingAs($this->user);

        $beforeData = ['x' => 0.1, 'y' => 0.2, 'text' => 'Kitchen'];
        $afterData = ['x' => 0.15, 'y' => 0.25, 'text' => 'Kitchen Updated'];

        $historyEntry = PdfAnnotationHistory::logAction(
            pdfPageId: $this->pdfPage->id,
            action: 'updated',
            beforeData: $beforeData,
            afterData: $afterData,
            annotationId: 1
        );

        $this->assertEquals($beforeData, $historyEntry->before_data);
        $this->assertEquals($afterData, $historyEntry->after_data);
        $this->assertIsArray($historyEntry->before_data);
        $this->assertIsArray($historyEntry->after_data);
    }
}
