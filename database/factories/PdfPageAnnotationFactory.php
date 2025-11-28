<?php

namespace Database\Factories;

use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Webkul\Security\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pdf Page Annotation Factory model factory
 *
 */
class PdfPageAnnotationFactory extends Factory
{
    protected $model = PdfPageAnnotation::class;

    /**
     * Definition
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'pdf_page_id' => PdfPage::factory(),
            'parent_annotation_id' => null,
            'annotation_type' => $this->faker->randomElement([
                'cabinet_run',
                'cabinet',
                'room_boundary',
                'measurement',
                'note',
            ]),
            'label' => $this->faker->optional()->words(2, true),
            'x' => $this->faker->randomFloat(2, 0, 800),
            'y' => $this->faker->randomFloat(2, 0, 1000),
            'width' => $this->faker->randomFloat(2, 50, 300),
            'height' => $this->faker->randomFloat(2, 20, 100),
            'room_type' => $this->faker->optional()->randomElement([
                'kitchen',
                'bathroom',
                'office',
                'bedroom',
            ]),
            'color' => $this->faker->optional()->hexColor(),
            'visual_properties' => [
                'strokeColor' => $this->faker->hexColor(),
                'strokeWidth' => $this->faker->numberBetween(1, 5),
                'opacity' => $this->faker->randomFloat(2, 0.5, 1.0),
            ],
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => [],
            'creator_id' => User::factory(),
            'view_type' => $this->faker->randomElement(['plan', 'elevation', 'section', 'detail']),
            'view_orientation' => $this->faker->optional()->randomElement(['north', 'south', 'east', 'west']),
            'view_scale' => $this->faker->optional()->randomFloat(4, 0.25, 2.0),
        ];
    }

    /**
     * State for a cabinet run annotation (top-level)
     */
    /**
     * Cabinet Run
     *
     * @return static
     */
    public function cabinetRun(): static
    {
        return $this->state(fn (array $attributes) => [
            'annotation_type' => 'cabinet_run',
            'parent_annotation_id' => null,
        ]);
    }

    /**
     * State for a cabinet annotation (child of cabinet run)
     */
    /**
     * Cabinet
     *
     * @return static
     */
    public function cabinet(): static
    {
        return $this->state(fn (array $attributes) => [
            'annotation_type' => 'cabinet',
            'parent_annotation_id' => PdfPageAnnotation::factory()->cabinetRun(),
        ]);
    }

    /**
     * State for a room boundary annotation
     */
    /**
     * Room Boundary
     *
     * @return static
     */
    public function roomBoundary(): static
    {
        return $this->state(fn (array $attributes) => [
            'annotation_type' => 'room_boundary',
            'parent_annotation_id' => null,
        ]);
    }
}
