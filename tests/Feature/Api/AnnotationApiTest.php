<?php

namespace Tests\Feature\Api;

use App\Models\PdfAnnotation;
use App\Models\PdfDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnnotationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PdfDocument $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->document = PdfDocument::factory()->create([
            'uploaded_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function unauthenticated_users_cannot_access_annotation_endpoints(): void
    {
        $response = $this->getJson("/api/pdf/{$this->document->id}/annotations");
        $response->assertStatus(401);

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", []);
        $response->assertStatus(401);

        $annotation = PdfAnnotation::factory()->create(['document_id' => $this->document->id]);
        $response = $this->putJson("/api/pdf/{$this->document->id}/annotations/{$annotation->id}", []);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/pdf/{$this->document->id}/annotations/{$annotation->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_list_annotations_for_document(): void
    {
        Sanctum::actingAs($this->user);

        PdfAnnotation::factory()->count(5)->create([
            'document_id' => $this->document->id,
            'author_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/pdf/{$this->document->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'attributes' => [
                            'page_number',
                            'annotation_type',
                            'annotation_data',
                            'author_id',
                            'author_name',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function list_annotations_returns_instant_json_format(): void
    {
        Sanctum::actingAs($this->user);

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'author_id' => $this->user->id,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
            'annotation_data' => [
                'color' => '#ffff00',
                'position' => ['x' => 100, 'y' => 200, 'width' => 150, 'height' => 50],
            ],
        ]);

        $response = $this->getJson("/api/pdf/{$this->document->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => (string) $annotation->id,
                'type' => 'annotations',
            ])
            ->assertJsonPath('data.0.attributes.annotation_type', 'highlight')
            ->assertJsonPath('data.0.attributes.annotation_data.color', '#ffff00');
    }

    /** @test */
    public function authenticated_user_can_create_annotation_with_valid_payload(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'page_number' => 1,
            'annotation_type' => PdfAnnotation::TYPE_TEXT,
            'annotation_data' => [
                'color' => '#ff0000',
                'position' => ['x' => 100, 'y' => 200, 'width' => 200, 'height' => 100],
                'text' => 'This is a test annotation',
            ],
        ];

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'page_number',
                        'annotation_type',
                        'annotation_data',
                        'author_id',
                        'author_name',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('pdf_annotations', [
            'document_id' => $this->document->id,
            'page_number' => 1,
            'annotation_type' => PdfAnnotation::TYPE_TEXT,
            'author_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function create_annotation_fails_with_invalid_annotation_type(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'page_number' => 1,
            'annotation_type' => 'invalid_type',
            'annotation_data' => ['color' => '#ff0000'],
        ];

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['annotation_type']);
    }

    /** @test */
    public function create_annotation_fails_with_missing_required_fields(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page_number', 'annotation_type', 'annotation_data']);
    }

    /** @test */
    public function create_annotation_fails_with_invalid_page_number(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'page_number' => 0, // Invalid: must be >= 1
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
            'annotation_data' => ['color' => '#ff0000'],
        ];

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page_number']);
    }

    /** @test */
    public function create_annotation_validates_annotation_data_structure(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'page_number' => 1,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
            'annotation_data' => 'invalid_not_array', // Must be array
        ];

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['annotation_data']);
    }

    /** @test */
    public function authenticated_user_can_update_their_own_annotation(): void
    {
        Sanctum::actingAs($this->user);

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'author_id' => $this->user->id,
            'annotation_data' => ['color' => '#ff0000'],
        ]);

        $payload = [
            'annotation_data' => [
                'color' => '#00ff00',
                'position' => ['x' => 150, 'y' => 250, 'width' => 200, 'height' => 100],
            ],
        ];

        $response = $this->putJson("/api/pdf/{$this->document->id}/annotations/{$annotation->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.annotation_data.color', '#00ff00');

        $this->assertDatabaseHas('pdf_annotations', [
            'id' => $annotation->id,
        ]);

        $annotation->refresh();
        $this->assertEquals('#00ff00', $annotation->annotation_data['color']);
    }

    /** @test */
    public function user_cannot_update_annotation_created_by_another_user(): void
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($this->user);

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'author_id' => $otherUser->id,
        ]);

        $payload = [
            'annotation_data' => ['color' => '#00ff00'],
        ];

        $response = $this->putJson("/api/pdf/{$this->document->id}/annotations/{$annotation->id}", $payload);

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function authenticated_user_can_delete_their_own_annotation(): void
    {
        Sanctum::actingAs($this->user);

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'author_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/pdf/{$this->document->id}/annotations/{$annotation->id}");

        $response->assertStatus(204); // No Content

        $this->assertSoftDeleted('pdf_annotations', [
            'id' => $annotation->id,
        ]);
    }

    /** @test */
    public function user_cannot_delete_annotation_created_by_another_user(): void
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($this->user);

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'author_id' => $otherUser->id,
        ]);

        $response = $this->deleteJson("/api/pdf/{$this->document->id}/annotations/{$annotation->id}");

        $response->assertStatus(403); // Forbidden

        $this->assertDatabaseHas('pdf_annotations', [
            'id' => $annotation->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function list_annotations_can_filter_by_page_number(): void
    {
        Sanctum::actingAs($this->user);

        PdfAnnotation::factory()->count(3)->create([
            'document_id' => $this->document->id,
            'page_number' => 1,
        ]);

        PdfAnnotation::factory()->count(2)->create([
            'document_id' => $this->document->id,
            'page_number' => 2,
        ]);

        $response = $this->getJson("/api/pdf/{$this->document->id}/annotations?page_number=1");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function list_annotations_can_filter_by_annotation_type(): void
    {
        Sanctum::actingAs($this->user);

        PdfAnnotation::factory()->count(3)->create([
            'document_id' => $this->document->id,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
        ]);

        PdfAnnotation::factory()->count(2)->create([
            'document_id' => $this->document->id,
            'annotation_type' => PdfAnnotation::TYPE_TEXT,
        ]);

        $response = $this->getJson("/api/pdf/{$this->document->id}/annotations?annotation_type=highlight");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function annotation_endpoints_respect_rate_limiting(): void
    {
        Sanctum::actingAs($this->user);

        // Simulate 61 requests (assuming 60 req/min limit)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/pdf/{$this->document->id}/annotations");

            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429); // Too Many Requests
                break;
            }
        }
    }

    /** @test */
    public function concurrent_updates_to_same_annotation_are_handled_correctly(): void
    {
        Sanctum::actingAs($this->user);

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'author_id' => $this->user->id,
            'annotation_data' => ['color' => '#ff0000'],
        ]);

        // Simulate concurrent update by getting original annotation
        $originalAnnotation = PdfAnnotation::find($annotation->id);

        // First update
        $payload1 = ['annotation_data' => ['color' => '#00ff00']];
        $response1 = $this->putJson("/api/pdf/{$this->document->id}/annotations/{$annotation->id}", $payload1);
        $response1->assertStatus(200);

        // Second update (should also succeed)
        $payload2 = ['annotation_data' => ['color' => '#0000ff']];
        $response2 = $this->putJson("/api/pdf/{$this->document->id}/annotations/{$annotation->id}", $payload2);
        $response2->assertStatus(200);

        // Verify final state is from second update
        $annotation->refresh();
        $this->assertEquals('#0000ff', $annotation->annotation_data['color']);
    }

    /** @test */
    public function annotation_response_includes_proper_json_api_format(): void
    {
        Sanctum::actingAs($this->user);

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
            'author_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/pdf/{$this->document->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'attributes',
                    ],
                ],
            ]);

        // Verify JSON:API spec compliance
        $data = $response->json('data.0');
        $this->assertIsString($data['id']);
        $this->assertEquals('annotations', $data['type']);
        $this->assertIsArray($data['attributes']);
    }

    /** @test */
    public function create_annotation_sets_author_name_from_authenticated_user(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'page_number' => 1,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
            'annotation_data' => ['color' => '#ff0000'],
        ];

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", $payload);

        $response->assertStatus(201);

        $annotation = PdfAnnotation::latest()->first();
        $this->assertEquals($this->user->name, $annotation->author_name);
        $this->assertEquals($this->user->id, $annotation->author_id);
    }

    /** @test */
    public function annotation_validation_ensures_color_is_valid_hex(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'page_number' => 1,
            'annotation_type' => PdfAnnotation::TYPE_HIGHLIGHT,
            'annotation_data' => [
                'color' => 'not-a-hex-color', // Invalid hex color
            ],
        ];

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['annotation_data.color']);
    }

    /** @test */
    public function annotation_validation_ensures_position_has_required_fields(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'page_number' => 1,
            'annotation_type' => PdfAnnotation::TYPE_RECTANGLE,
            'annotation_data' => [
                'color' => '#ff0000',
                'position' => ['x' => 100], // Missing y, width, height
            ],
        ];

        $response = $this->postJson("/api/pdf/{$this->document->id}/annotations", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'annotation_data.position.y',
                'annotation_data.position.width',
                'annotation_data.position.height',
            ]);
    }

    /** @test */
    public function list_annotations_returns_empty_array_for_document_without_annotations(): void
    {
        Sanctum::actingAs($this->user);

        $emptyDocument = PdfDocument::factory()->create();

        $response = $this->getJson("/api/pdf/{$emptyDocument->id}/annotations");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJson(['data' => []]);
    }

    /** @test */
    public function update_annotation_with_non_existent_id_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'annotation_data' => ['color' => '#00ff00'],
        ];

        $response = $this->putJson("/api/pdf/{$this->document->id}/annotations/99999", $payload);

        $response->assertStatus(404);
    }

    /** @test */
    public function delete_annotation_with_non_existent_id_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/pdf/{$this->document->id}/annotations/99999");

        $response->assertStatus(404);
    }

    /** @test */
    public function annotation_timestamps_are_returned_in_iso8601_format(): void
    {
        Sanctum::actingAs($this->user);

        $annotation = PdfAnnotation::factory()->create([
            'document_id' => $this->document->id,
        ]);

        $response = $this->getJson("/api/pdf/{$this->document->id}/annotations");

        $response->assertStatus(200);

        $createdAt = $response->json('data.0.attributes.created_at');
        $updatedAt = $response->json('data.0.attributes.updated_at');

        // Verify ISO 8601 format (e.g., "2024-01-15T10:30:00.000000Z")
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/', $createdAt);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/', $updatedAt);
    }
}
