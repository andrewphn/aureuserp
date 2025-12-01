<?php

namespace Webkul\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Project\Models\ProjectDraft;

/**
 * Project Draft Factory
 */
class ProjectDraftFactory extends Factory
{
    protected $model = ProjectDraft::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'session_id' => $this->faker->uuid(),
            'current_step' => $this->faker->numberBetween(1, 4),
            'form_data' => [
                'project_type' => $this->faker->randomElement(['residential', 'commercial', 'furniture']),
                'lead_source' => $this->faker->randomElement(['referral', 'website', 'social_media']),
            ],
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * State: Expired draft (for testing cleanup)
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * State: Recently expired draft
     */
    public function recentlyExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(2),
        ]);
    }

    /**
     * State: Old draft (created many days ago)
     */
    public function old(int $daysOld = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays($daysOld),
            'updated_at' => now()->subDays($daysOld),
        ]);
    }

    /**
     * State: Active (not expired) draft
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(3),
        ]);
    }

    /**
     * State: No expiration set
     */
    public function noExpiration(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }
}
