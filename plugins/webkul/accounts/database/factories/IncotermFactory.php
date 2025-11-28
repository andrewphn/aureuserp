<?php

namespace Webkul\Account\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Security\Models\User;

/**
 * Incoterm Factory model factory
 *
 */
class IncotermFactory extends Factory
{
    /**
     * Definition
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name'       => $this->faker->word,
            'code'       => $this->faker->word,
            'creator_id' => User::factory(),
        ];
    }
}
