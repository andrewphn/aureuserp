<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The migration paths for plugins.
     *
     * @var array<string>
     */
    protected array $pluginMigrationPaths = [];

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Collect plugin migration paths
        $this->pluginMigrationPaths = glob(base_path('plugins/webkul/*/database/migrations')) ?: [];
    }

    /**
     * The parameters that should be used when running "migrate:fresh".
     * Override to include plugin migration paths.
     *
     * @return array
     */
    protected function migrateFreshUsing()
    {
        // Get all plugin migration paths
        $paths = glob(base_path('plugins/webkul/*/database/migrations')) ?: [];

        // Add core migrations path
        $paths[] = database_path('migrations');

        $seeder = property_exists($this, 'seeder') ? $this->seeder : false;
        $shouldSeed = property_exists($this, 'seed') ? $this->seed : false;

        $params = [
            '--drop-views' => property_exists($this, 'dropViews') ? $this->dropViews : false,
            '--drop-types' => property_exists($this, 'dropTypes') ? $this->dropTypes : false,
            '--path' => $paths,
            '--realpath' => true,
        ];

        if ($seeder) {
            $params['--seeder'] = $seeder;
        } else {
            $params['--seed'] = $shouldSeed;
        }

        return $params;
    }
}
