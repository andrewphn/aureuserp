<?php

namespace Webkul\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Security\Models\User;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roomTypes = ['kitchen', 'bathroom', 'bedroom', 'living_room', 'office', 'closet', 'laundry', 'pantry', 'mudroom', 'garage'];

        return [
            'project_id'       => Project::factory(),
            'name'             => fake()->randomElement(['Kitchen', 'Master Bath', 'Office', 'Pantry', 'Walk-in Closet', 'Laundry Room']),
            'room_type'        => fake()->randomElement($roomTypes),
            'floor_number'     => fake()->optional()->randomElement(['1', '2', '3', 'Basement', 'Main']),
            'pdf_page_number'  => fake()->numberBetween(1, 10),
            'pdf_room_label'   => fake()->optional()->lexify('???'),
            'pdf_detail_number'=> fake()->optional()->numerify('D-##'),
            'pdf_notes'        => fake()->optional()->sentence(),
            'notes'            => fake()->optional()->paragraph(),
            'sort_order'       => fake()->numberBetween(0, 100),
            'cabinet_level'    => fake()->optional()->randomElement(['base', 'wall', 'tall', 'mixed']),
            'material_category'=> fake()->optional()->randomElement(['standard', 'premium', 'custom']),
            'finish_option'    => fake()->optional()->randomElement(['painted', 'stained', 'natural', 'laminate']),
            'creator_id'       => User::factory(),
        ];
    }

    /**
     * Indicate that the room belongs to a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Set the room as a kitchen.
     */
    public function kitchen(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);
    }

    /**
     * Set the room as a bathroom.
     */
    public function bathroom(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Bathroom',
            'room_type' => 'bathroom',
        ]);
    }

    /**
     * Set the room as a closet.
     */
    public function closet(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Closet',
            'room_type' => 'closet',
        ]);
    }
}
