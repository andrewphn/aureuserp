<?php

namespace Webkul\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectInspirationImage;
use Webkul\Project\Models\Room;
use Webkul\Security\Models\User;

/**
 * @extends Factory<ProjectInspirationImage>
 */
class ProjectInspirationImageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProjectInspirationImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'room_id'     => null,
            'file_name'   => fake()->word() . '.jpg',
            'title'       => fake()->optional()->sentence(3),
            'file_path'   => 'inspiration-images/' . fake()->uuid() . '.jpg',
            'file_size'   => fake()->numberBetween(100000, 5000000),
            'mime_type'   => 'image/jpeg',
            'width'       => fake()->numberBetween(800, 4000),
            'height'      => fake()->numberBetween(600, 3000),
            'uploaded_by' => User::factory(),
            'description' => fake()->optional()->paragraph(),
            'tags'        => [],
            'metadata'    => [],
            'sort_order'  => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the image belongs to a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Indicate that the image belongs to a specific room.
     */
    public function forRoom(Room $room): static
    {
        return $this->state(fn (array $attributes) => [
            'room_id' => $room->id,
            'project_id' => $room->project_id,
        ]);
    }

    /**
     * Indicate that the image has a title.
     */
    public function withTitle(?string $title = null): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title ?? fake()->sentence(3),
        ]);
    }

    /**
     * Indicate that the image has a description.
     */
    public function withDescription(?string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description ?? fake()->paragraph(),
        ]);
    }

    /**
     * Indicate that the image has tags.
     */
    public function withTags(array $tagIds = []): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => $tagIds ?: [1, 2, 3],
        ]);
    }

    /**
     * Indicate that the image is a PNG.
     */
    public function png(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => fake()->word() . '.png',
            'file_path' => 'inspiration-images/' . fake()->uuid() . '.png',
            'mime_type' => 'image/png',
        ]);
    }

    /**
     * Indicate that the image is a GIF.
     */
    public function gif(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => fake()->word() . '.gif',
            'file_path' => 'inspiration-images/' . fake()->uuid() . '.gif',
            'mime_type' => 'image/gif',
        ]);
    }
}
