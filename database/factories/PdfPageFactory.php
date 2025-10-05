<?php

namespace Database\Factories;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PdfPageFactory extends Factory
{
    protected $model = PdfPage::class;

    public function definition(): array
    {
        return [
            'document_id' => PdfDocument::factory(),
            'page_number' => $this->faker->numberBetween(1, 100),
            'width' => $this->faker->numberBetween(600, 800),
            'height' => $this->faker->numberBetween(800, 1200),
            'rotation' => $this->faker->randomElement([0, 90, 180, 270]),
            'thumbnail_path' => 'thumbnails/' . $this->faker->uuid() . '.jpg',
        ];
    }
}
