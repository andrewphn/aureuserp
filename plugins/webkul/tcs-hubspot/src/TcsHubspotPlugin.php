<?php

namespace Webkul\TcsHubspot;

use Filament\Contracts\Plugin;
use Filament\Panel;

class TcsHubspotPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tcs-hubspot';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
