<?php

namespace Webkul\Security;

use Spatie\LaravelPackageTools\Package as BasePackage;

/**
 * Package class
 *
 */
class Package extends BasePackage
{
    public bool $runsSettings = false;

    public array $settingFileNames = [];

    /**
     * Runs Settings
     *
     * @param bool $runsSettings
     * @return static
     */
    public function runsSettings(bool $runsSettings = true): static
    {
        $this->runsSettings = $runsSettings;

        return $this;
    }

    /**
     * Has Setting
     *
     * @param string $settingFileName
     * @return static
     */
    public function hasSetting(string $settingFileName): static
    {
        $this->settingFileNames[] = $settingFileName;

        return $this;
    }

    /**
     * Has Settings
     *
     * @return static
     */
    public function hasSettings(...$settingFileNames): static
    {
        $this->settingFileNames = array_merge(
            $this->settingFileNames,
            collect($settingFileNames)->flatten()->toArray()
        );

        return $this;
    }
}
