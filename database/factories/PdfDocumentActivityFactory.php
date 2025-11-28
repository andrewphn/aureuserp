<?php

namespace Database\Factories;

use App\Models\PdfDocument;
use App\Models\PdfDocumentActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pdf Document Activity Factory model factory
 *
 */
class PdfDocumentActivityFactory extends Factory
{
    protected $model = PdfDocumentActivity::class;

    /**
     * Definition
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'document_id' => PdfDocument::factory(),
            'user_id' => User::factory(),
            'action_type' => $this->faker->randomElement(['viewed', 'downloaded', 'annotated', 'printed', 'shared']),
            'action_details' => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ],
        ];
    }
}
