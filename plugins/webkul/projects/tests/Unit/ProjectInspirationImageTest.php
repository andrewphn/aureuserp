<?php

namespace Webkul\Project\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectInspirationImage;
use Webkul\Project\Models\Room;
use Webkul\Security\Models\User;

/**
 * Unit tests for ProjectInspirationImage model
 *
 * Tests all model fields, relationships, scopes, and computed attributes
 */
class ProjectInspirationImageTest extends TestCase
{
    use DatabaseTransactions;

    protected Project $project;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    /** @test */
    public function it_can_create_inspiration_image()
    {
        $image = ProjectInspirationImage::create([
            'project_id' => $this->project->id,
            'file_name' => 'kitchen-design.jpg',
            'title' => 'Kitchen Design Inspiration',
            'file_path' => 'inspiration-images/kitchen-design.jpg',
            'file_size' => 1024000,
            'mime_type' => 'image/jpeg',
            'width' => 1920,
            'height' => 1080,
            'uploaded_by' => $this->user->id,
            'description' => 'Modern kitchen with white cabinets',
            'tags' => [1, 2, 3],
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('project_inspiration_images', [
            'id' => $image->id,
            'project_id' => $this->project->id,
            'file_name' => 'kitchen-design.jpg',
            'title' => 'Kitchen Design Inspiration',
        ]);
    }

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTitle('Factory Test')
            ->withDescription('Created with factory')
            ->create(['uploaded_by' => $this->user->id]);

        $this->assertInstanceOf(ProjectInspirationImage::class, $image);
        $this->assertEquals($this->project->id, $image->project_id);
        $this->assertEquals('Factory Test', $image->title);
        $this->assertEquals('Created with factory', $image->description);
    }

    /** @test */
    public function it_belongs_to_project()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        $this->assertInstanceOf(Project::class, $image->project);
        $this->assertEquals($this->project->id, $image->project->id);
    }

    /** @test */
    public function it_belongs_to_room()
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        $image = ProjectInspirationImage::factory()
            ->forRoom($room)
            ->create(['uploaded_by' => $this->user->id]);

        $this->assertInstanceOf(Room::class, $image->room);
        $this->assertEquals($room->id, $image->room->id);
    }

    /** @test */
    public function it_can_have_null_room()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['room_id' => null, 'uploaded_by' => $this->user->id]);

        $this->assertNull($image->room);
    }

    /** @test */
    public function it_belongs_to_uploader()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        $this->assertInstanceOf(User::class, $image->uploader);
        $this->assertEquals($this->user->id, $image->uploader->id);
    }

    /** @test */
    public function it_casts_tags_to_array()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->withTags([1, 2, 3])
            ->create(['uploaded_by' => $this->user->id]);

        $this->assertIsArray($image->tags);
        $this->assertEquals([1, 2, 3], $image->tags);
    }

    /** @test */
    public function it_casts_metadata_to_array()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create([
                'uploaded_by' => $this->user->id,
                'metadata' => ['source' => 'pinterest', 'original_url' => 'https://example.com'],
            ]);

        $this->assertIsArray($image->metadata);
        $this->assertEquals('pinterest', $image->metadata['source']);
    }

    /** @test */
    public function it_scopes_by_project()
    {
        $otherProject = Project::factory()->create();

        ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->count(3)
            ->create(['uploaded_by' => $this->user->id]);

        ProjectInspirationImage::factory()
            ->forProject($otherProject)
            ->count(2)
            ->create(['uploaded_by' => $this->user->id]);

        $images = ProjectInspirationImage::forProject($this->project->id)->get();

        $this->assertCount(3, $images);
        $images->each(fn ($image) => $this->assertEquals($this->project->id, $image->project_id));
    }

    /** @test */
    public function it_scopes_by_room()
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        ProjectInspirationImage::factory()
            ->forRoom($room)
            ->count(2)
            ->create(['uploaded_by' => $this->user->id]);

        ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->count(3)
            ->create(['room_id' => null, 'uploaded_by' => $this->user->id]);

        $roomImages = ProjectInspirationImage::forProject($this->project->id)
            ->forRoom($room->id)
            ->get();

        $this->assertCount(2, $roomImages);
    }

    /** @test */
    public function it_scopes_unassigned_images()
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        ProjectInspirationImage::factory()
            ->forRoom($room)
            ->count(2)
            ->create(['uploaded_by' => $this->user->id]);

        ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->count(3)
            ->create(['room_id' => null, 'uploaded_by' => $this->user->id]);

        $unassigned = ProjectInspirationImage::forProject($this->project->id)
            ->unassigned()
            ->get();

        $this->assertCount(3, $unassigned);
        $unassigned->each(fn ($image) => $this->assertNull($image->room_id));
    }

    /** @test */
    public function it_scopes_by_uploader()
    {
        $otherUser = User::factory()->create();

        ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->count(3)
            ->create(['uploaded_by' => $this->user->id]);

        ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->count(2)
            ->create(['uploaded_by' => $otherUser->id]);

        $userImages = ProjectInspirationImage::forProject($this->project->id)
            ->byUploader($this->user->id)
            ->get();

        $this->assertCount(3, $userImages);
    }

    /** @test */
    public function it_scopes_recent_images()
    {
        ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->count(15)
            ->create(['uploaded_by' => $this->user->id]);

        $recentImages = ProjectInspirationImage::forProject($this->project->id)
            ->recent(5)
            ->get();

        $this->assertCount(5, $recentImages);
    }

    /** @test */
    public function it_formats_file_size_in_bytes()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'file_size' => 500]);

        $this->assertEquals('500 B', $image->formatted_file_size);
    }

    /** @test */
    public function it_formats_file_size_in_kilobytes()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'file_size' => 2048]);

        $this->assertEquals('2 KB', $image->formatted_file_size);
    }

    /** @test */
    public function it_formats_file_size_in_megabytes()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'file_size' => 3145728]);

        $this->assertEquals('3 MB', $image->formatted_file_size);
    }

    /** @test */
    public function it_returns_dimensions_string()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'width' => 1920, 'height' => 1080]);

        $this->assertEquals('1920x1080', $image->dimensions);
    }

    /** @test */
    public function it_returns_null_dimensions_when_missing()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'width' => null, 'height' => null]);

        $this->assertNull($image->dimensions);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        $image->delete();

        $this->assertSoftDeleted('project_inspiration_images', ['id' => $image->id]);
        $this->assertNull(ProjectInspirationImage::find($image->id));
        $this->assertNotNull(ProjectInspirationImage::withTrashed()->find($image->id));
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        $image->delete();
        $this->assertSoftDeleted('project_inspiration_images', ['id' => $image->id]);

        $image->restore();
        $this->assertNotSoftDeleted('project_inspiration_images', ['id' => $image->id]);
    }

    /** @test */
    public function it_uses_sortable_trait()
    {
        $image1 = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        $image2 = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        // Sortable trait should auto-assign sort_order
        $this->assertNotNull($image1->sort_order);
        $this->assertNotNull($image2->sort_order);
    }

    /** @test */
    public function it_builds_sort_query_by_project()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        $sortQuery = $image->buildSortQuery();

        // MySQL uses backticks, assert on the column name being present
        $this->assertStringContainsString(
            '`project_id` = ?',
            $sortQuery->toSql()
        );
    }

    /** @test */
    public function it_can_update_all_fields()
    {
        $room = Room::factory()->create(['project_id' => $this->project->id]);

        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id]);

        $image->update([
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'room_id' => $room->id,
            'tags' => [4, 5, 6],
            'metadata' => ['updated' => true],
        ]);

        $image->refresh();

        $this->assertEquals('Updated Title', $image->title);
        $this->assertEquals('Updated description', $image->description);
        $this->assertEquals($room->id, $image->room_id);
        $this->assertEquals([4, 5, 6], $image->tags);
        $this->assertEquals(['updated' => true], $image->metadata);
    }

    /** @test */
    public function it_can_have_empty_tags()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create(['uploaded_by' => $this->user->id, 'tags' => []]);

        $this->assertIsArray($image->tags);
        $this->assertEmpty($image->tags);
    }

    /** @test */
    public function it_can_have_null_title_and_description()
    {
        $image = ProjectInspirationImage::factory()
            ->forProject($this->project)
            ->create([
                'uploaded_by' => $this->user->id,
                'title' => null,
                'description' => null,
            ]);

        $this->assertNull($image->title);
        $this->assertNull($image->description);
    }
}
