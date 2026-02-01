<?php

namespace Webkul\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\State;

/**
 * Company Factory model factory
 *
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                  => $this->faker->company(),
            'company_id'            => $this->faker->uuid(),
            'tax_id'                => $this->faker->bothify('??-########'),
            'registration_number'   => $this->faker->randomNumber(8, true),
            'email'                 => $this->faker->unique()->companyEmail(),
            'phone'                 => $this->faker->phoneNumber(),
            'mobile'                => $this->faker->e164PhoneNumber(),
            'color'                 => $this->faker->hexColor(),
            'is_active'             => true,
            'founded_date'          => $this->faker->date('Y-m-d', '-10 years'),
            'creator_id'            => User::factory(),
            'currency_id'           => null,
            'partner_id'            => 1, // Use existing partner or will be overridden
        ];
    }
}
