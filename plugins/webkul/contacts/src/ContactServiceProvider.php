<?php

namespace Webkul\Contact;

use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

/**
 * Contact Service Provider service provider
 *
 */
class ContactServiceProvider extends PackageServiceProvider
{
    public static string $name = 'contacts';

    /**
     * Configure Custom Package
     *
     * @param Package $package
     * @return void
     */
    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {})
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    /**
     * Package Booted
     *
     * @return void
     */
    public function packageBooted(): void
    {
        //
    }
}
