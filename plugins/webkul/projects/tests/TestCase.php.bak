<?php

namespace Webkul\Project\Tests;

use Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed essential data required for tests
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Webkul\Security\Database\Seeders\DatabaseSeeder::class);
        $this->seed(\Webkul\Support\Database\Seeders\DatabaseSeeder::class);

        // Create admin user for foreign key constraints
        \App\Models\User::factory()->create([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@test.com',
        ]);
    }
}
