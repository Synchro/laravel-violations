<?php

declare(strict_types=1);

namespace Synchro\Violation;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Synchro\Violation\Commands\ViolationCommand;

class ViolationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-violations')
            ->hasConfigFile()
            ->hasMigration('create_violations_table')
            ->hasCommand(ViolationCommand::class);
    }
}
