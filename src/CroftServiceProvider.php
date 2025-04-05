<?php

declare(strict_types=1);

namespace Croft;

use Croft\Commands\CroftCommand;
use Croft\Commands\CroftRestartCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CroftServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('croft')
            ->hasConfigFile()
            ->hasCommand(CroftCommand::class)
            ->hasCommand(CroftRestartCommand::class);
    }
}
