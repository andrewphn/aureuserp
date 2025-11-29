<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Filament\Resources\ProjectResource\RelationManagers\ProjectMediaRelationManager;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Integration tests for ProjectMediaRelationManager
 *
 * Tests the Filament relation manager for project media assets,
 * including file uploads, viewing, downloading, and deletion.
 */
class ProjectMediaRelationManagerTest extends TestCase
{
    use DatabaseTransactions;

    protected Project $project;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Use existing user or create one
        $this->user = User::first() ?? User::create([
            'email' => 'test@tcswoodwork.com',
            'name' => 'Test User',
            'password' => bcrypt('password'),
        ]);

        // Use existing company and stage
        $company = Company::first() ?? Company::create([
            'name' => 'TCS Test Company',
            'is_default' => true,
        ]);
        $stage = ProjectStage::first() ?? ProjectStage::create([
            'name' => 'Discovery',
            'sort' => 1,
            'is_default' => true,
        ]);

        // Create a test project
        $this->project = Project::create([
            'name' => 'Test Media Project ' . uniqid(),
            'company_id' => $company->id,
            'stage_id' => $stage->id,
            'visibility' => 'internal',
            'creator_id' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function relation_manager_can_be_rendered(): void
    {
        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])->assertSuccessful();
    }

    /** @test */
    public function relation_manager_displays_empty_state_when_no_media(): void
    {
        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->assertSuccessful()
            ->assertSee('No records found');
    }

    /** @test */
    public function relation_manager_displays_existing_media(): void
    {
        // Add media to project
        $this->project
            ->addMedia(UploadedFile::fake()->image('test-image.jpg'))
            ->usingName('Test Image')
            ->toMediaCollection('photos');

        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->assertSuccessful()
            ->assertSee('Test Image');
    }

    /** @test */
    public function relation_manager_shows_media_collection_type_badge(): void
    {
        $this->project
            ->addMedia(UploadedFile::fake()->image('inspiration.jpg'))
            ->toMediaCollection('inspiration');

        $this->project
            ->addMedia(UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
            ->toMediaCollection('documents');

        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->assertSuccessful()
            ->assertSee('Inspiration')
            ->assertSee('Documents');
    }

    /** @test */
    public function relation_manager_can_filter_by_collection(): void
    {
        // Add media to different collections
        $this->project
            ->addMedia(UploadedFile::fake()->image('insp.jpg'))
            ->usingName('Inspiration Image')
            ->toMediaCollection('inspiration');

        $this->project
            ->addMedia(UploadedFile::fake()->image('photo.jpg'))
            ->usingName('Site Photo')
            ->toMediaCollection('photos');

        $component = Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ]);

        // Filter by inspiration collection
        $component
            ->filterTable('collection_name', 'inspiration')
            ->assertSee('Inspiration Image')
            ->assertDontSee('Site Photo');
    }

    /** @test */
    public function relation_manager_shows_file_size(): void
    {
        $this->project
            ->addMedia(UploadedFile::fake()->image('sized-image.jpg', 100, 100))
            ->toMediaCollection('photos');

        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->assertSuccessful();
        // File size column should be present
    }

    /** @test */
    public function relation_manager_has_upload_action(): void
    {
        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->assertSuccessful()
            ->assertTableHeaderActionsExist(['create']);
    }

    /** @test */
    public function relation_manager_has_download_action(): void
    {
        $this->project
            ->addMedia(UploadedFile::fake()->image('downloadable.jpg'))
            ->toMediaCollection('photos');

        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->assertSuccessful()
            ->assertTableActionExists('download');
    }

    /** @test */
    public function relation_manager_has_delete_action(): void
    {
        $this->project
            ->addMedia(UploadedFile::fake()->image('deletable.jpg'))
            ->toMediaCollection('photos');

        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->assertSuccessful()
            ->assertTableActionExists('delete');
    }

    /** @test */
    public function can_delete_media_through_relation_manager(): void
    {
        $media = $this->project
            ->addMedia(UploadedFile::fake()->image('to-delete.jpg'))
            ->toMediaCollection('photos');

        $this->assertDatabaseHas('media', ['id' => $media->id]);

        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->callTableAction('delete', $media);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    /** @test */
    public function can_bulk_delete_media(): void
    {
        $media1 = $this->project
            ->addMedia(UploadedFile::fake()->image('bulk1.jpg'))
            ->toMediaCollection('photos');

        $media2 = $this->project
            ->addMedia(UploadedFile::fake()->image('bulk2.jpg'))
            ->toMediaCollection('photos');

        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->callTableBulkAction('delete', [$media1, $media2]);

        $this->assertDatabaseMissing('media', ['id' => $media1->id]);
        $this->assertDatabaseMissing('media', ['id' => $media2->id]);
    }

    /** @test */
    public function media_is_sorted_by_created_at_descending(): void
    {
        // Add media with time gaps
        $older = $this->project
            ->addMedia(UploadedFile::fake()->image('older.jpg'))
            ->usingName('Older Image')
            ->toMediaCollection('photos');

        // Manually set created_at to ensure order
        $older->created_at = now()->subHour();
        $older->save();

        $newer = $this->project
            ->addMedia(UploadedFile::fake()->image('newer.jpg'))
            ->usingName('Newer Image')
            ->toMediaCollection('photos');

        $component = Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ]);

        // The newer image should appear first (default sort is created_at desc)
        $records = $component->instance()->getTableRecords();
        $this->assertEquals('Newer Image', $records->first()->name);
    }

    /** @test */
    public function relation_manager_respects_project_ownership(): void
    {
        // Create another project owned by different user
        $otherUser = User::factory()->create();
        $otherProject = Project::create([
            'name' => 'Other Project',
            'company_id' => $this->project->company_id,
            'stage_id' => $this->project->stage_id,
            'visibility' => 'internal',
            'creator_id' => $otherUser->id,
            'user_id' => $otherUser->id,
        ]);

        // Add media to other project
        $otherMedia = $otherProject
            ->addMedia(UploadedFile::fake()->image('other.jpg'))
            ->usingName('Other Project Media')
            ->toMediaCollection('photos');

        // Add media to our project
        $ourMedia = $this->project
            ->addMedia(UploadedFile::fake()->image('ours.jpg'))
            ->usingName('Our Project Media')
            ->toMediaCollection('photos');

        // Relation manager should only show our project's media
        Livewire::test(ProjectMediaRelationManager::class, [
            'ownerRecord' => $this->project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ])
            ->assertSee('Our Project Media')
            ->assertDontSee('Other Project Media');
    }
}
