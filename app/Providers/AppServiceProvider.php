<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->managePermissions();
    }

    public function managePermissions()
    {
        FilamentShield::buildPermissionKeyUsing(function (string $entity, string $affix, string $subject) {
            $affix = Str::snake($affix);

            if (
                $entity == 'BezhanSalleh\FilamentShield\Resources\Roles\RoleResource'
                || $entity == 'App\Filament\Resources\RoleResource'
            ) {
                return $affix . '_role';
            }

            if (class_exists($entity) && method_exists($entity, 'getModel')) {
                $resourceIdentifier = Str::of($entity)
                    ->afterLast('Resources\\')
                    ->beforeLast('Resource')
                    ->replace('\\', '')
                    ->snake()
                    ->replace('_', '::')
                    ->toString();

                return $affix . '_' . $resourceIdentifier;
            }

            if (Str::contains($entity, 'Pages\\')) {
                return 'page_' . Str::snake(class_basename($entity));
            }

            if (Str::contains($entity, 'Widgets\\') || Str::endsWith($entity, 'Widget')) {
                return 'widget_' . Str::snake(class_basename($entity));
            }

            return $affix . '_' . Str::snake($subject);
        });
    }
}
