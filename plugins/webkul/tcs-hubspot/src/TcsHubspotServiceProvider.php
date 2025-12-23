<?php

namespace Webkul\TcsHubspot;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Webkul\TcsHubspot\Services\HubSpotService;
use Webkul\TcsHubspot\Services\HubSpotWorkflowService;

class TcsHubspotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HubSpotService::class, function ($app) {
            return new HubSpotService;
        });

        $this->app->singleton(HubSpotWorkflowService::class, function ($app) {
            return new HubSpotWorkflowService;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\HubSpotMoveToQuestionnaire::class,
            ]);
        }
    }
}
