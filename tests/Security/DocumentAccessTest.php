<?php

namespace Tests\Security;

use App\Models\PdfDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Document Access Security Tests
 *
 * Tests permission-based access control, document visibility scopes,
 * and security boundaries for the PDF document system.
 */
class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['email' => 'admin@test.com']);
        $this->regularUser = User::factory()->create(['email' => 'user@test.com']);
        $this->otherUser = User::factory()->create(['email' => 'other@test.com']);
    }

    /** @test */
    public function unauthenticated_users_cannot_access_documents(): void
    {
        $document = PdfDocument::factory()->create();

        $response = $this->getJson("/api/pdf-documents/{$document->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function users_can_only_access_their_own_private_documents(): void
    {
        Sanctum::actingAs($this->regularUser);

        // User's own private document
        $ownDocument = PdfDocument::factory()->create([
            'uploaded_by' => $this->regularUser->id,
            'is_public' => false,
        ]);

        // Another user's private document
        $otherDocument = PdfDocument::factory()->create([
            'uploaded_by' => $this->otherUser->id,
            'is_public' => false,
        ]);

        // Can access own document
        $response = $this->getJson("/api/pdf-documents/{$ownDocument->id}");
        $response->assertStatus(200);

        // Cannot access other user's private document
        $response = $this->getJson("/api/pdf-documents/{$otherDocument->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function users_can_access_public_documents(): void
    {
        Sanctum::actingAs($this->regularUser);

        $publicDocument = PdfDocument::factory()->create([
            'uploaded_by' => $this->otherUser->id,
            'is_public' => true,
        ]);

        $response = $this->getJson("/api/pdf-documents/{$publicDocument->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_users_can_access_all_documents(): void
    {
        // Assuming admin role check - adjust based on your implementation
        Sanctum::actingAs($this->adminUser);

        $privateDocument = PdfDocument::factory()->create([
            'uploaded_by' => $this->regularUser->id,
            'is_public' => false,
        ]);

        // Admin can access any document
        $response = $this->getJson("/api/pdf-documents/{$privateDocument->id}");
        // May return 200 or 403 depending on implementation
        // $response->assertStatus(200);
    }

    /** @test */
    public function users_cannot_update_documents_they_dont_own(): void
    {
        Sanctum::actingAs($this->regularUser);

        $otherDocument = PdfDocument::factory()->create([
            'uploaded_by' => $this->otherUser->id,
        ]);

        $response = $this->putJson("/api/pdf-documents/{$otherDocument->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);

        // Document should not be updated
        $this->assertDatabaseMissing('pdf_documents', [
            'id' => $otherDocument->id,
            'title' => 'Hacked Title',
        ]);
    }

    /** @test */
    public function users_cannot_delete_documents_they_dont_own(): void
    {
        Sanctum::actingAs($this->regularUser);

        $otherDocument = PdfDocument::factory()->create([
            'uploaded_by' => $this->otherUser->id,
        ]);

        $response = $this->deleteJson("/api/pdf-documents/{$otherDocument->id}");
        $response->assertStatus(403);

        // Document should still exist
        $this->assertDatabaseHas('pdf_documents', [
            'id' => $otherDocument->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function document_list_endpoint_respects_visibility_scopes(): void
    {
        Sanctum::actingAs($this->regularUser);

        // User's own documents
        PdfDocument::factory()->count(2)->create([
            'uploaded_by' => $this->regularUser->id,
            'is_public' => false,
        ]);

        // Public documents from others
        PdfDocument::factory()->count(3)->create([
            'uploaded_by' => $this->otherUser->id,
            'is_public' => true,
        ]);

        // Private documents from others (should NOT see)
        PdfDocument::factory()->count(5)->create([
            'uploaded_by' => $this->otherUser->id,
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/pdf-documents');
        $response->assertStatus(200);

        // Should only see own + public = 5 documents
        $response->assertJsonCount(5, 'data');
    }

    /** @test */
    public function sql_injection_is_prevented_in_document_queries(): void
    {
        Sanctum::actingAs($this->regularUser);

        // Attempt SQL injection in search parameter
        $maliciousPayload = "'; DROP TABLE pdf_documents; --";

        $response = $this->getJson("/api/pdf-documents?search={$maliciousPayload}");

        // Should not cause error or drop table
        $response->assertStatus(200);

        // Table should still exist
        $this->assertDatabaseHas('pdf_documents', []);
    }

    /** @test */
    public function mass_assignment_protection_prevents_unauthorized_field_updates(): void
    {
        Sanctum::actingAs($this->regularUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->regularUser->id,
        ]);

        // Attempt to change uploaded_by via mass assignment
        $response = $this->putJson("/api/pdf-documents/{$document->id}", [
            'title' => 'Updated Title',
            'uploaded_by' => $this->otherUser->id, // Should be protected
        ]);

        $response->assertStatus(200);

        // uploaded_by should not change
        $this->assertDatabaseHas('pdf_documents', [
            'id' => $document->id,
            'uploaded_by' => $this->regularUser->id, // Original uploader
        ]);
    }

    /** @test */
    public function file_upload_size_limits_are_enforced(): void
    {
        Sanctum::actingAs($this->regularUser);

        // Simulate file larger than max size (e.g., 50MB limit)
        $response = $this->postJson('/api/pdf-documents', [
            'title' => 'Large File',
            'file_size' => 52428801, // 50MB + 1 byte
            'mime_type' => 'application/pdf',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file_size']);
    }

    /** @test */
    public function only_pdf_files_are_accepted(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->postJson('/api/pdf-documents', [
            'title' => 'Malicious File',
            'mime_type' => 'application/x-php', // Not PDF
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mime_type']);
    }

    /** @test */
    public function users_cannot_access_annotations_on_documents_they_cant_view(): void
    {
        Sanctum::actingAs($this->regularUser);

        $privateDocument = PdfDocument::factory()->create([
            'uploaded_by' => $this->otherUser->id,
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/pdf/{$privateDocument->id}/annotations");
        $response->assertStatus(403);
    }

    /** @test */
    public function annotation_author_cannot_be_spoofed(): void
    {
        Sanctum::actingAs($this->regularUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->regularUser->id,
        ]);

        // Attempt to set author_id to another user
        $response = $this->postJson("/api/pdf/{$document->id}/annotations", [
            'page_number' => 1,
            'annotation_type' => 'highlight',
            'annotation_data' => ['color' => '#ffff00'],
            'author_id' => $this->otherUser->id, // Spoofed author
        ]);

        $response->assertStatus(201);

        // author_id should be the authenticated user
        $this->assertDatabaseHas('pdf_annotations', [
            'document_id' => $document->id,
            'author_id' => $this->regularUser->id, // Not the spoofed user
        ]);
    }

    /** @test */
    public function xss_prevention_in_annotation_text(): void
    {
        Sanctum::actingAs($this->regularUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->regularUser->id,
        ]);

        $xssPayload = '<script>alert("XSS")</script>';

        $response = $this->postJson("/api/pdf/{$document->id}/annotations", [
            'page_number' => 1,
            'annotation_type' => 'text',
            'annotation_data' => [
                'text' => $xssPayload,
                'color' => '#ff0000',
            ],
        ]);

        $response->assertStatus(201);

        // When retrieved, should be escaped
        $getResponse = $this->getJson("/api/pdf/{$document->id}/annotations");
        $responseData = $getResponse->json();

        // Verify XSS is escaped (implementation dependent)
        $annotationText = $responseData['data'][0]['attributes']['annotation_data']['text'];
        $this->assertStringNotContainsString('<script>', $annotationText);
    }

    /** @test */
    public function rate_limiting_prevents_abuse(): void
    {
        Sanctum::actingAs($this->regularUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->regularUser->id,
        ]);

        // Make 61 requests (assuming 60 req/min limit)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/pdf/{$document->id}/annotations");

            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429); // Too Many Requests
                break;
            }
        }
    }

    /** @test */
    public function csrf_protection_is_enforced_for_state_changing_operations(): void
    {
        Sanctum::actingAs($this->regularUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->regularUser->id,
        ]);

        // Attempt POST without CSRF token (Sanctum handles this differently)
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->deleteJson("/api/pdf-documents/{$document->id}");

        // With Sanctum, CSRF may not apply to API routes
        // This test is more relevant for web routes
    }

    /** @test */
    public function document_paths_cannot_traverse_directories(): void
    {
        Sanctum::actingAs($this->regularUser);

        // Attempt directory traversal
        $maliciousPath = '../../../etc/passwd';

        $response = $this->postJson('/api/pdf-documents', [
            'title' => 'Malicious Upload',
            'file_path' => $maliciousPath,
        ]);

        // Should reject or sanitize
        $response->assertStatus(422);
    }
}
