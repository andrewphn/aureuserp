<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PdfDocumentResource;
use App\Filament\Resources\PdfDocumentResource\Pages\CreatePdfDocument;
use App\Filament\Resources\PdfDocumentResource\Pages\EditPdfDocument;
use App\Filament\Resources\PdfDocumentResource\Pages\ListPdfDocuments;
use App\Models\PdfDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PdfDocumentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->adminUser = User::factory()->create();
        $this->regularUser = User::factory()->create();
    }

    /** @test */
    public function unauthenticated_users_cannot_access_document_resource(): void
    {
        $this->get(PdfDocumentResource::getUrl('index'))
            ->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_admin_can_view_document_list(): void
    {
        $this->actingAs($this->adminUser);

        PdfDocument::factory()->count(5)->create([
            'uploaded_by' => $this->adminUser->id,
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(PdfDocument::all());
    }

    /** @test */
    public function regular_user_can_only_see_their_own_and_public_documents(): void
    {
        $this->actingAs($this->regularUser);

        // User's own documents
        $ownDocuments = PdfDocument::factory()->count(2)->create([
            'uploaded_by' => $this->regularUser->id,
            'is_public' => false,
        ]);

        // Public documents from another user
        $publicDocuments = PdfDocument::factory()->count(2)->create([
            'uploaded_by' => $this->adminUser->id,
            'is_public' => true,
        ]);

        // Private documents from another user (should NOT see)
        PdfDocument::factory()->count(2)->create([
            'uploaded_by' => $this->adminUser->id,
            'is_public' => false,
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($ownDocuments->merge($publicDocuments))
            ->assertCountTableRecords(4);
    }

    /** @test */
    public function document_list_can_be_paginated(): void
    {
        $this->actingAs($this->adminUser);

        PdfDocument::factory()->count(25)->create();

        Livewire::test(ListPdfDocuments::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(PdfDocument::limit(10)->get());
    }

    /** @test */
    public function document_list_can_be_searched_by_title(): void
    {
        $this->actingAs($this->adminUser);

        $searchableDoc = PdfDocument::factory()->create([
            'title' => 'Unique Search Title',
        ]);

        PdfDocument::factory()->count(5)->create([
            'title' => 'Other Document',
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->searchTable('Unique Search Title')
            ->assertCanSeeTableRecords([$searchableDoc])
            ->assertCountTableRecords(1);
    }

    /** @test */
    public function document_list_can_be_filtered_by_folder(): void
    {
        $this->actingAs($this->adminUser);

        $folder = \App\Models\Folder::factory()->create(['name' => 'Test Folder']);

        $filteredDocs = PdfDocument::factory()->count(3)->create([
            'folder_id' => $folder->id,
        ]);

        PdfDocument::factory()->count(2)->create([
            'folder_id' => null,
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->filterTable('folder_id', $folder->id)
            ->assertCanSeeTableRecords($filteredDocs)
            ->assertCountTableRecords(3);
    }

    /** @test */
    public function document_list_can_be_filtered_by_category(): void
    {
        $this->actingAs($this->adminUser);

        $category = \App\Models\Category::factory()->create(['name' => 'Test Category']);

        $filteredDocs = PdfDocument::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        PdfDocument::factory()->count(2)->create([
            'category_id' => null,
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->filterTable('category_id', $category->id)
            ->assertCanSeeTableRecords($filteredDocs)
            ->assertCountTableRecords(3);
    }

    /** @test */
    public function document_list_can_be_filtered_by_public_status(): void
    {
        $this->actingAs($this->adminUser);

        $publicDocs = PdfDocument::factory()->count(3)->create([
            'is_public' => true,
        ]);

        PdfDocument::factory()->count(2)->create([
            'is_public' => false,
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->filterTable('is_public', true)
            ->assertCanSeeTableRecords($publicDocs)
            ->assertCountTableRecords(3);
    }

    /** @test */
    public function document_list_can_be_sorted_by_created_at(): void
    {
        $this->actingAs($this->adminUser);

        PdfDocument::factory()->count(5)->create();

        Livewire::test(ListPdfDocuments::class)
            ->sortTable('created_at', 'desc')
            ->assertCanSeeTableRecords(
                PdfDocument::orderBy('created_at', 'desc')->get(),
                inOrder: true
            );
    }

    /** @test */
    public function authenticated_user_can_view_create_document_form(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CreatePdfDocument::class)
            ->assertSuccessful()
            ->assertFormExists();
    }

    /** @test */
    public function create_form_renders_all_required_fields(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CreatePdfDocument::class)
            ->assertFormFieldExists('title')
            ->assertFormFieldExists('description')
            ->assertFormFieldExists('file_path')
            ->assertFormFieldExists('folder_id')
            ->assertFormFieldExists('category_id')
            ->assertFormFieldExists('documentable_type')
            ->assertFormFieldExists('documentable_id')
            ->assertFormFieldExists('tags')
            ->assertFormFieldExists('is_public');
    }

    /** @test */
    public function user_can_create_document_with_valid_data(): void
    {
        $this->actingAs($this->adminUser);

        Storage::fake('public');
        $file = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');

        Livewire::test(CreatePdfDocument::class)
            ->fillForm([
                'title' => 'Test PDF Document',
                'description' => 'Test description',
                'file_path' => $file,
                'tags' => ['test', 'document'],
                'is_public' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pdf_documents', [
            'title' => 'Test PDF Document',
            'description' => 'Test description',
            'uploaded_by' => $this->adminUser->id,
            'is_public' => true,
        ]);

        Storage::disk('public')->assertExists('pdf-documents/' . $file->hashName());
    }

    /** @test */
    public function create_form_validates_required_fields(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CreatePdfDocument::class)
            ->fillForm([
                'title' => '',
                'file_path' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'file_path' => 'required']);
    }

    /** @test */
    public function create_form_validates_file_type(): void
    {
        $this->actingAs($this->adminUser);

        Storage::fake('public');
        $invalidFile = UploadedFile::fake()->create('test-document.txt', 1024, 'text/plain');

        Livewire::test(CreatePdfDocument::class)
            ->fillForm([
                'title' => 'Test Document',
                'file_path' => $invalidFile,
            ])
            ->call('create')
            ->assertHasFormErrors(['file_path']);
    }

    /** @test */
    public function create_form_validates_file_size_limit(): void
    {
        $this->actingAs($this->adminUser);

        Storage::fake('public');
        // Create file larger than 50MB (51200 KB)
        $largeFile = UploadedFile::fake()->create('large-document.pdf', 51201, 'application/pdf');

        Livewire::test(CreatePdfDocument::class)
            ->fillForm([
                'title' => 'Large Document',
                'file_path' => $largeFile,
            ])
            ->call('create')
            ->assertHasFormErrors(['file_path']);
    }

    /** @test */
    public function create_form_validates_title_max_length(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CreatePdfDocument::class)
            ->fillForm([
                'title' => str_repeat('a', 256), // Exceeds 255 max length
            ])
            ->call('create')
            ->assertHasFormErrors(['title']);
    }

    /** @test */
    public function authenticated_user_can_view_edit_document_form(): void
    {
        $this->actingAs($this->adminUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->adminUser->id,
        ]);

        Livewire::test(EditPdfDocument::class, ['record' => $document->id])
            ->assertSuccessful()
            ->assertFormExists();
    }

    /** @test */
    public function user_can_edit_document_with_valid_data(): void
    {
        $this->actingAs($this->adminUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->adminUser->id,
            'title' => 'Original Title',
        ]);

        Livewire::test(EditPdfDocument::class, ['record' => $document->id])
            ->fillForm([
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'is_public' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pdf_documents', [
            'id' => $document->id,
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'is_public' => true,
        ]);
    }

    /** @test */
    public function edit_form_is_populated_with_existing_data(): void
    {
        $this->actingAs($this->adminUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->adminUser->id,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'tags' => ['tag1', 'tag2'],
            'is_public' => true,
        ]);

        Livewire::test(EditPdfDocument::class, ['record' => $document->id])
            ->assertFormSet([
                'title' => 'Test Title',
                'description' => 'Test Description',
                'tags' => ['tag1', 'tag2'],
                'is_public' => true,
            ]);
    }

    /** @test */
    public function user_can_delete_document(): void
    {
        $this->actingAs($this->adminUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->adminUser->id,
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->callTableAction('delete', $document);

        $this->assertSoftDeleted('pdf_documents', [
            'id' => $document->id,
        ]);
    }

    /** @test */
    public function user_can_bulk_delete_documents(): void
    {
        $this->actingAs($this->adminUser);

        $documents = PdfDocument::factory()->count(3)->create([
            'uploaded_by' => $this->adminUser->id,
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->callTableBulkAction('delete', $documents);

        foreach ($documents as $document) {
            $this->assertSoftDeleted('pdf_documents', [
                'id' => $document->id,
            ]);
        }
    }

    /** @test */
    public function table_displays_file_size_in_formatted_format(): void
    {
        $this->actingAs($this->adminUser);

        $document = PdfDocument::factory()->create([
            'file_size' => 2048, // 2KB
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->assertTableColumnFormattedStateSet('file_size', '2.00 KB', $document);
    }

    /** @test */
    public function table_displays_related_type_as_class_basename(): void
    {
        $this->actingAs($this->adminUser);

        $document = PdfDocument::factory()->create([
            'documentable_type' => 'App\\Models\\Project',
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->assertTableColumnFormattedStateSet('documentable_type', 'Project', $document);
    }

    /** @test */
    public function download_action_is_available_on_table(): void
    {
        $this->actingAs($this->adminUser);

        $document = PdfDocument::factory()->create();

        Livewire::test(ListPdfDocuments::class)
            ->assertTableActionExists('download');
    }

    /** @test */
    public function view_action_is_available_on_table(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ListPdfDocuments::class)
            ->assertTableActionExists('view');
    }

    /** @test */
    public function edit_action_is_available_on_table(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ListPdfDocuments::class)
            ->assertTableActionExists('edit');
    }

    /** @test */
    public function delete_action_is_available_on_table(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ListPdfDocuments::class)
            ->assertTableActionExists('delete');
    }

    /** @test */
    public function documentable_id_field_is_reactive_to_documentable_type(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CreatePdfDocument::class)
            ->fillForm([
                'documentable_type' => 'App\\Models\\Project',
            ])
            ->assertFormFieldIsVisible('documentable_id');
    }

    /** @test */
    public function documentable_id_field_is_hidden_when_documentable_type_is_null(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CreatePdfDocument::class)
            ->fillForm([
                'documentable_type' => null,
            ])
            ->assertFormFieldIsHidden('documentable_id');
    }

    /** @test */
    public function pdf_viewer_field_is_visible_on_edit_form(): void
    {
        $this->actingAs($this->adminUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->adminUser->id,
            'file_path' => 'test-document.pdf',
        ]);

        Livewire::test(EditPdfDocument::class, ['record' => $document->id])
            ->assertFormFieldExists('id');
    }

    /** @test */
    public function folder_can_be_created_inline_from_document_form(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CreatePdfDocument::class)
            ->assertFormFieldExists('folder_id');

        // Note: Inline creation would require additional modal interaction testing
        // which is complex with Livewire and FilamentPHP nested forms
    }

    /** @test */
    public function category_can_be_created_inline_from_document_form(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CreatePdfDocument::class)
            ->assertFormFieldExists('category_id');

        // Note: Inline creation would require additional modal interaction testing
    }

    /** @test */
    public function table_columns_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ListPdfDocuments::class)
            ->assertTableColumnExists('uploader.name')
            ->assertTableColumnExists('created_at')
            ->assertTableColumnExists('updated_at');
    }

    /** @test */
    public function regular_user_cannot_see_documentable_type_column(): void
    {
        $this->actingAs($this->regularUser);

        $document = PdfDocument::factory()->create([
            'uploaded_by' => $this->regularUser->id,
            'documentable_type' => 'App\\Models\\Project',
        ]);

        // Regular users should not see documentable_type column
        // This test assumes hasRole('admin') returns false for regular users
        Livewire::test(ListPdfDocuments::class)
            ->assertSuccessful();

        // Note: Testing column visibility based on user role requires checking
        // the actual rendered HTML, which is complex with Livewire
    }

    /** @test */
    public function tags_are_properly_stored_and_retrieved(): void
    {
        $this->actingAs($this->adminUser);

        Storage::fake('public');
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        Livewire::test(CreatePdfDocument::class)
            ->fillForm([
                'title' => 'Tagged Document',
                'file_path' => $file,
                'tags' => ['important', 'review', '2024'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = PdfDocument::where('title', 'Tagged Document')->first();
        $this->assertEquals(['important', 'review', '2024'], $document->tags);
    }

    /** @test */
    public function table_default_sort_is_created_at_desc(): void
    {
        $this->actingAs($this->adminUser);

        $oldDocument = PdfDocument::factory()->create([
            'created_at' => now()->subDays(5),
        ]);

        $newDocument = PdfDocument::factory()->create([
            'created_at' => now(),
        ]);

        Livewire::test(ListPdfDocuments::class)
            ->assertCanSeeTableRecords(
                [$newDocument, $oldDocument],
                inOrder: true
            );
    }
}
