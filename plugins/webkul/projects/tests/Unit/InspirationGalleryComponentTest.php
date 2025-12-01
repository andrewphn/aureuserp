<?php

namespace Webkul\Project\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Webkul\Project\Livewire\InspirationGalleryComponent;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectInspirationImage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\Tag;
use Webkul\Security\Models\User;

/**
 * Unit tests for InspirationGalleryComponent Livewire component
 *
 * Tests CRUD operations and all related fields:
 * - Create images with title, description, room, tags
 * - Read/list images with filtering
 * - Update image metadata (quick edit and full edit)
 * - Delete images
 * - Batch upload functionality
 * - Room filtering
 * - Tag management
 */
class InspirationGalleryComponentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    /** @test */
    public function it_can_mount_with_project_id()
    {
        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->assertSet('projectId', $this->project->id)
            ->assertSet('selectedRoomId', null)
            ->assertSet('showEditModal', false);
    }

    /** @test */
    public function it_can_mount_without_project_id()
    {
        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class)
            ->assertSet('projectId', null);
    }

    /** @test */
    public function it_displays_empty_state_when_no_images()
    {
        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->assertSee('No inspiration images yet');
    }

    /** @test */
    public function it_displays_images_for_project()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTitle('Kitchen Inspiration')
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->assertSee('Kitchen Inspiration');
    }

    /** @test */
    public function it_can_filter_images_by_room()
    {
        $room = Room::factory()->create(['project_id' => $this->project->id, 'name' => 'Kitchen']);

        $kitchenImage = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTitle('Kitchen Image')
            ->create(['room_id' => $room->id, 'uploaded_by' => $this->user->id]);

        $otherImage = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTitle('Other Image')
            ->create(['uploaded_by' => $this->user->id]);

        $component = Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id]);

        // Initially shows all images
        $component->assertSee('Kitchen Image')
            ->assertSee('Other Image');

        // Filter by room
        $component->call('filterByRoom', $room->id)
            ->assertSet('selectedRoomId', $room->id)
            ->assertSee('Kitchen Image')
            ->assertDontSee('Other Image');

        // Clear filter
        $component->call('filterByRoom', null)
            ->assertSet('selectedRoomId', null)
            ->assertSee('Kitchen Image')
            ->assertSee('Other Image');
    }

    /** @test */
    public function it_can_open_editor_for_image()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTitle('Test Image')
            ->withDescription('Test description')
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('openEditor', $image->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingImageId', $image->id)
            ->assertSet('editTitle', 'Test Image')
            ->assertSet('editDescription', 'Test description');
    }

    /** @test */
    public function it_prevents_opening_editor_for_other_projects_image()
    {
        $otherProject = Project::factory()->create();
        $image = ProjectInspirationImage::factory()
            ->forProject($otherProject)
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('openEditor', $image->id)
            ->assertSet('showEditModal', false)
            ->assertSet('editingImageId', null);
    }

    /** @test */
    public function it_can_close_editor()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('openEditor', $image->id)
            ->assertSet('showEditModal', true)
            ->call('closeEditor')
            ->assertSet('showEditModal', false)
            ->assertSet('editingImageId', null)
            ->assertSet('editTitle', '')
            ->assertSet('editDescription', '');
    }

    /** @test */
    public function it_can_save_image_metadata()
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('openEditor', $image->id)
            ->set('editTitle', 'Updated Title')
            ->set('editDescription', 'Updated Description')
            ->set('editRoomId', $room->id)
            ->set('editTags', [1, 2])
            ->call('saveImageMetadata')
            ->assertSet('showEditModal', false);

        $image->refresh();
        $this->assertEquals('Updated Title', $image->title);
        $this->assertEquals('Updated Description', $image->description);
        $this->assertEquals($room->id, $image->room_id);
        $this->assertEquals([1, 2], $image->tags);
    }

    /** @test */
    public function it_can_delete_image()
    {
        Storage::fake('public');

        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create([
                'uploaded_by' => $this->user->id,
                'file_path' => 'inspiration-images/test.jpg',
            ]);

        // Create a fake file
        Storage::disk('public')->put('inspiration-images/test.jpg', 'test content');

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('deleteImage', $image->id);

        $this->assertSoftDeleted('project_inspiration_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('inspiration-images/test.jpg');
    }

    /** @test */
    public function it_prevents_deleting_other_projects_image()
    {
        $otherProject = Project::factory()->create();
        $image = ProjectInspirationImage::factory()
            ->forProject($otherProject)
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('deleteImage', $image->id);

        $this->assertDatabaseHas('project_inspiration_images', ['id' => $image->id, 'deleted_at' => null]);
    }

    /** @test */
    public function it_can_start_quick_edit()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTitle('Quick Edit Test')
            ->withDescription('Original description')
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('startQuickEdit', $image->id)
            ->assertSet('quickEditImageId', $image->id)
            ->assertSet('quickEditTitle', 'Quick Edit Test')
            ->assertSet('quickEditDescription', 'Original description');
    }

    /** @test */
    public function it_can_save_quick_edit()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTitle('Old Title')
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('startQuickEdit', $image->id)
            ->set('quickEditTitle', 'New Quick Title')
            ->set('quickEditDescription', 'New quick description')
            ->call('saveQuickEdit')
            ->assertSet('quickEditImageId', null);

        $image->refresh();
        $this->assertEquals('New Quick Title', $image->title);
        $this->assertEquals('New quick description', $image->description);
    }

    /** @test */
    public function it_can_cancel_quick_edit()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTitle('Original Title')
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('startQuickEdit', $image->id)
            ->set('quickEditTitle', 'Changed Title')
            ->call('cancelQuickEdit')
            ->assertSet('quickEditImageId', null)
            ->assertSet('quickEditTitle', '')
            ->assertSet('quickEditDescription', '');

        $image->refresh();
        $this->assertEquals('Original Title', $image->title);
    }

    /** @test */
    public function it_can_toggle_tags()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        $component = Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('openEditor', $image->id)
            ->assertSet('editTags', []);

        // Add a tag
        $component->call('toggleTag', 1)
            ->assertSet('editTags', [1]);

        // Add another tag
        $component->call('toggleTag', 2)
            ->assertSet('editTags', [1, 2]);

        // Remove first tag
        $component->call('toggleTag', 1)
            ->assertSet('editTags', [2]);
    }

    /** @test */
    public function it_can_update_sort_order()
    {
        $image1 = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'sort_order' => 0]);

        $image2 = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'sort_order' => 1]);

        $image3 = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'sort_order' => 2]);

        // Reorder: image3, image1, image2
        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('updateOrder', [$image3->id, $image1->id, $image2->id]);

        $this->assertEquals(0, $image3->fresh()->sort_order);
        $this->assertEquals(1, $image1->fresh()->sort_order);
        $this->assertEquals(2, $image2->fresh()->sort_order);
    }

    /** @test */
    public function it_can_add_pending_uploads()
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $component = Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->set('newImages', [$file])
            ->assertCount('pendingUploads', 1);

        $pendingUploads = $component->get('pendingUploads');
        $this->assertEquals('test-image.jpg', $pendingUploads[0]['name']);
        $this->assertEquals('test-image', $pendingUploads[0]['title']);
    }

    /** @test */
    public function it_can_remove_pending_upload()
    {
        $file1 = UploadedFile::fake()->image('image1.jpg');
        $file2 = UploadedFile::fake()->image('image2.jpg');

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->set('newImages', [$file1, $file2])
            ->assertCount('pendingUploads', 2)
            ->call('removePendingUpload', 0)
            ->assertCount('pendingUploads', 1);
    }

    /** @test */
    public function it_can_update_pending_upload_metadata()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $component = Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->set('newImages', [$file])
            ->call('updatePendingUpload', 0, 'title', 'Custom Title')
            ->call('updatePendingUpload', 0, 'description', 'Custom Description');

        $pendingUploads = $component->get('pendingUploads');
        $this->assertEquals('Custom Title', $pendingUploads[0]['title']);
        $this->assertEquals('Custom Description', $pendingUploads[0]['description']);
    }

    /** @test */
    public function it_can_save_pending_uploads()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('kitchen-design.jpg', 1920, 1080);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->set('newImages', [$file])
            ->call('updatePendingUpload', 0, 'title', 'Kitchen Design')
            ->call('updatePendingUpload', 0, 'description', 'Modern kitchen inspiration')
            ->call('savePendingUploads')
            ->assertCount('pendingUploads', 0);

        $this->assertDatabaseHas('project_inspiration_images', [
            'project_id' => $this->project->id,
            'title' => 'Kitchen Design',
            'description' => 'Modern kitchen inspiration',
            'uploaded_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_discard_pending_uploads()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->set('newImages', [$file])
            ->assertCount('pendingUploads', 1)
            ->call('discardPendingUploads')
            ->assertCount('pendingUploads', 0);
    }

    /** @test */
    public function it_assigns_room_to_pending_uploads_when_filtered()
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);
        $file = UploadedFile::fake()->image('test.jpg');

        $component = Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('filterByRoom', $room->id)
            ->set('newImages', [$file]);

        $pendingUploads = $component->get('pendingUploads');
        $this->assertEquals($room->id, $pendingUploads[0]['room_id']);
    }

    /** @test */
    public function it_uses_reactive_project_id_from_parent()
    {
        // ProjectId is a reactive prop from parent, test it's properly initialized
        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->assertSet('projectId', $this->project->id);
    }

    /** @test */
    public function it_displays_room_tabs_when_rooms_exist()
    {
        Room::factory()->create(['project_id' => $this->project->id, 'name' => 'Kitchen']);
        Room::factory()->create(['project_id' => $this->project->id, 'name' => 'Bathroom']);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->assertSee('All Rooms')
            ->assertSee('Kitchen')
            ->assertSee('Bathroom');
    }

    /** @test */
    public function it_closes_editor_when_editing_image_is_deleted()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(InspirationGalleryComponent::class, ['projectId' => $this->project->id])
            ->call('openEditor', $image->id)
            ->assertSet('showEditModal', true)
            ->call('deleteImage', $image->id)
            ->assertSet('showEditModal', false)
            ->assertSet('editingImageId', null);
    }
}
