<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Tag;
use Webkul\Partner\Models\Partner;

class TagSelectorPanelTest extends TestCase
{
    /** @test */
    public function it_can_attach_tags_to_project()
    {
        $project = Project::factory()->create([
            'partner_id' => Partner::factory()->create()->id
        ]);

        $tag1 = Tag::factory()->create(['name' => 'High Priority', 'type' => 'priority']);
        $tag2 = Tag::factory()->create(['name' => 'Complex', 'type' => 'complexity']);

        $project->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertTrue($project->tags->contains($tag1));
        $this->assertTrue($project->tags->contains($tag2));
        $this->assertCount(2, $project->tags);
    }

    /** @test */
    public function it_can_sync_tags_on_project()
    {
        $project = Project::factory()->create([
            'partner_id' => Partner::factory()->create()->id
        ]);

        $tag1 = Tag::factory()->create(['name' => 'Initial Tag', 'type' => 'priority']);
        $tag2 = Tag::factory()->create(['name' => 'New Tag', 'type' => 'complexity']);

        $project->tags()->sync([$tag1->id]);
        $this->assertCount(1, $project->tags);

        $project->tags()->sync([$tag2->id]);
        $project->refresh();

        $this->assertFalse($project->tags->contains($tag1));
        $this->assertTrue($project->tags->contains($tag2));
        $this->assertCount(1, $project->tags);
    }

    /** @test */
    public function it_can_detach_all_tags_from_project()
    {
        $project = Project::factory()->create([
            'partner_id' => Partner::factory()->create()->id
        ]);

        $tag = Tag::factory()->create(['name' => 'Tag to Remove', 'type' => 'priority']);
        $project->tags()->attach($tag->id);

        $project->tags()->detach();
        $project->refresh();

        $this->assertCount(0, $project->tags);
    }

    /** @test */
    public function it_can_attach_multiple_tags_from_different_types()
    {
        $project = Project::factory()->create([
            'partner_id' => Partner::factory()->create()->id
        ]);

        $priorityTag = Tag::factory()->create(['name' => 'High Priority', 'type' => 'priority']);
        $healthTag = Tag::factory()->create(['name' => 'On Track', 'type' => 'health']);
        $riskTag = Tag::factory()->create(['name' => 'Low Risk', 'type' => 'risk']);

        $project->tags()->attach([$priorityTag->id, $healthTag->id, $riskTag->id]);

        $this->assertCount(3, $project->tags);
        $this->assertTrue($project->tags->contains('type', 'priority'));
        $this->assertTrue($project->tags->contains('type', 'health'));
        $this->assertTrue($project->tags->contains('type', 'risk'));
    }
}
