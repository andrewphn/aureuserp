<?php

namespace Database\Factories;

use App\Models\PdfDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PdfDocumentFactory extends Factory
{
    protected $model = PdfDocument::class;

    public function definition(): array
    {
        return [
            'module_type' => 'App\\Models\\Project',
            'module_id' => 1,
            'file_name' => $this->faker->word() . '.pdf',
            'file_path' => 'pdfs/' . $this->faker->uuid() . '.pdf',
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => 'application/pdf',
            'page_count' => $this->faker->numberBetween(1, 50),
            'uploaded_by' => User::factory(),
            'tags' => ['project', 'drawing'],
            'metadata' => [
                'version' => '1.0',
                'created_by_app' => 'TCS ERP',
            ],
        ];
    }
}
