<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Define database migrations for tests
     */
    protected function defineDatabaseMigrations(): void
    {
        // Load migrations from all plugin directories
        $pluginPaths = glob(base_path('plugins/webkul/*/database/migrations'));

        foreach ($pluginPaths as $path) {
            $this->loadMigrationsFrom($path);
        }

        // Also load core database migrations
        $this->loadMigrationsFrom(database_path('migrations'));
    }
}
