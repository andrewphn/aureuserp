<?php

namespace Webkul\Support;

use Filament\Contracts\Plugin;
use Filament\Panel;

use function Illuminate\Filesystem\join_paths;

/**
 * Plugin Manager class
 *
 */
class PluginManager implements Plugin
{
    public function getId(): string
    {
        return 'plugin-manager';
    }

    /**
     * Register
     *
     * @param Panel $panel
     * @return void
     */
    public function register(Panel $panel): void
    {
        $plugins = $this->getPlugins();

        foreach ($plugins as $modulePlugin) {
            $panel->plugin($modulePlugin::make());
        }
    }

    /**
     * Boot
     *
     * @param Panel $panel
     * @return void
     */
    public function boot(Panel $panel): void {}

    /**
     * Make
     *
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get
     *
     * @return static
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    protected function getPlugins(): array
    {
        $plugins = require join_paths(base_path().'/bootstrap', 'plugins.php');

        $plugins = collect($plugins)
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return $plugins;
    }
}
