<?php

declare(strict_types=1);

namespace Synchro\Violation;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Synchro\Violation\Commands\QueueViolations;
use Synchro\Violation\Http\Controllers\ViolationController;

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
            ->hasCommand(QueueViolations::class);
    }

    public function packageRegistered(): void
    {
        Route::macro('violations', function (string $baseUrl = 'violations') {
            Route::prefix($baseUrl)
                 ->withoutMiddleware(ValidateCsrfToken::class)
                 ->group(function () use ($baseUrl) {
                     Route::options('csp', [ViolationController::class, 'options'])
                          ->name($baseUrl.'.csp.options');
                     Route::options('nel', [ViolationController::class, 'options'])
                          ->name($baseUrl.'.nel.options');
                     Route::post('csp', [ViolationController::class, 'csp'])
                          ->name($baseUrl.'.csp');
                     Route::post('nel', [ViolationController::class, 'nel'])
                          ->name($baseUrl.'.nel');
                 });
        });
    }
}
