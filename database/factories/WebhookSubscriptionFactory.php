<?php

namespace Database\Factories;

use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Security\Models\User;

class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'url' => $this->faker->url(),
            'events' => $this->faker->randomElements([
                'project.created',
                'project.updated',
                'cabinet.created',
                'cabinet.updated',
                'task.created',
            ], rand(1, 3)),
            'secret' => $this->faker->sha256(),
            'is_active' => true,
            'success_count' => 0,
            'failure_count' => 0,
        ];
    }

    /**
     * Indicate that the subscription is inactive.
     */
    public function inactive(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific events for the subscription.
     */
    public function forEvents(array $events): Factory
    {
        return $this->state(fn (array $attributes) => [
            'events' => $events,
        ]);
    }
}
