<?php

namespace Webkul\Sale\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Order;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create a single partner to use for both invoice and shipping
        $partner = Partner::factory();

        return [
            'name' => fake()->unique()->numerify('SO-####'),
            'state' => OrderState::SALE,
            'company_id' => Company::factory(),
            'partner_id' => $partner,
            'partner_invoice_id' => $partner,
            'partner_shipping_id' => $partner,
            'currency_id' => 1, // Default to USD or base currency
            'user_id' => User::factory(),
            'creator_id' => User::factory(),
        ];
    }
}
