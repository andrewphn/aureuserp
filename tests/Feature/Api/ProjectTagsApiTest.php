<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Tag;

class ProjectTagsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    /** @test */
    public function unauthenticated_users_cannot_update_project_tags(): void
    {
        $response = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => [1, 2, 3],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_sync_project_tags(): void
    {
        $this->actingAs($this->user);

        $tags = Tag::factory()->count(3)->create();
        $tagIds = $tags->pluck('id')->toArray();

        $response = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => $tagIds,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tags updated successfully',
                'tag_count' => 3,
            ]);

        $this->assertEquals(3, $this->project->tags()->count());
        foreach ($tagIds as $tagId) {
            $this->assertTrue($this->project->tags->contains('id', $tagId));
        }
    }

    /** @test */
    public function sync_tags_removes_previously_assigned_tags_not_in_new_list(): void
    {
        $this->actingAs($this->user);

        $initialTags = Tag::factory()->count(3)->create();
        $this->project->tags()->attach($initialTags->pluck('id'));

        $newTags = Tag::factory()->count(2)->create();
        $newTagIds = $newTags->pluck('id')->toArray();

        $response = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => $newTagIds,
        ]);

        $response->assertStatus(200);

        $this->assertEquals(2, $this->project->fresh()->tags()->count());
        foreach ($newTagIds as $tagId) {
            $this->assertTrue($this->project->fresh()->tags->contains('id', $tagId));
        }
    }

    /** @test */
    public function sync_tags_with_empty_array_removes_all_tags(): void
    {
        $this->actingAs($this->user);

        $tags = Tag::factory()->count(3)->create();
        $this->project->tags()->attach($tags->pluck('id'));

        $response = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => [],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tag_count' => 0,
            ]);

        $this->assertEquals(0, $this->project->fresh()->tags()->count());
    }

    /** @test */
    public function sync_tags_validates_tag_ids_are_array(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => 'not-an-array',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function sync_tags_ignores_invalid_tag_ids(): void
    {
        $this->actingAs($this->user);

        $validTags = Tag::factory()->count(2)->create();
        $validTagIds = $validTags->pluck('id')->toArray();

        // Include a non-existent tag ID
        $tagIdsWithInvalid = array_merge($validTagIds, [99999]);

        $response = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => $tagIdsWithInvalid,
        ]);

        $response->assertStatus(200);

        // Only valid tags should be synced
        $this->assertEquals(2, $this->project->fresh()->tags()->count());
    }

    /** @test */
    public function sync_tags_returns_404_for_non_existent_project(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/projects/99999/tags', [
            'tag_ids' => [1, 2, 3],
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function sync_tags_handles_duplicate_tag_ids_gracefully(): void
    {
        $this->actingAs($this->user);

        $tags = Tag::factory()->count(2)->create();
        $tagId = $tags->first()->id;

        // Send same tag ID multiple times
        $duplicateTagIds = [$tagId, $tagId, $tagId];

        $response = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => $duplicateTagIds,
        ]);

        $response->assertStatus(200);

        // Should only attach once
        $this->assertEquals(1, $this->project->fresh()->tags()->count());
    }

    /** @test */
    public function sync_tags_is_idempotent(): void
    {
        $this->actingAs($this->user);

        $tags = Tag::factory()->count(3)->create();
        $tagIds = $tags->pluck('id')->toArray();

        // First sync
        $response1 = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => $tagIds,
        ]);
        $response1->assertStatus(200);

        // Second sync with same tags
        $response2 = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => $tagIds,
        ]);
        $response2->assertStatus(200);

        // Should still have same 3 tags
        $this->assertEquals(3, $this->project->fresh()->tags()->count());
    }

    /** @test */
    public function sync_tags_performance_with_large_tag_set(): void
    {
        $this->actingAs($this->user);

        $tags = Tag::factory()->count(100)->create();
        $tagIds = $tags->pluck('id')->toArray();

        $startTime = microtime(true);

        $response = $this->postJson("/api/projects/{$this->project->id}/tags", [
            'tag_ids' => $tagIds,
        ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        $this->assertEquals(100, $this->project->fresh()->tags()->count());

        // Should complete in reasonable time (< 1 second)
        $this->assertLessThan(1.0, $executionTime);
    }
}
