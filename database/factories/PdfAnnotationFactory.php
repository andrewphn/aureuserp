<?php

namespace Database\Factories;

use App\Models\PdfAnnotation;
use App\Models\PdfDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PdfAnnotationFactory extends Factory
{
    protected $model = PdfAnnotation::class;

    public function definition(): array
    {
        return [
            'document_id' => PdfDocument::factory(),
            'page_number' => $this->faker->numberBetween(1, 10),
            'annotation_type' => $this->faker->randomElement([
                PdfAnnotation::TYPE_HIGHLIGHT,
                PdfAnnotation::TYPE_TEXT,
                PdfAnnotation::TYPE_DRAWING,
                PdfAnnotation::TYPE_ARROW,
                PdfAnnotation::TYPE_RECTANGLE,
                PdfAnnotation::TYPE_CIRCLE,
                PdfAnnotation::TYPE_STAMP,
            ]),
            'annotation_data' => [
                'color' => $this->faker->hexColor(),
                'position' => [
                    'x' => $this->faker->numberBetween(0, 800),
                    'y' => $this->faker->numberBetween(0, 1000),
                    'width' => $this->faker->numberBetween(50, 300),
                    'height' => $this->faker->numberBetween(20, 100),
                ],
                'text' => $this->faker->optional()->sentence(),
            ],
            'author_id' => User::factory(),
            'author_name' => $this->faker->name(),
        ];
    }
}
