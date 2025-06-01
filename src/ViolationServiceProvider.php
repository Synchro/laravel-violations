<?php

declare(strict_types=1);

namespace Synchro\Violation;

use Illuminate\Console\Scheduling\Schedule;
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
        Route::macro('violations', function (?string $baseUrl = null) {
            // Use config value as default, allow override for backward compatibility
            $baseUrl = $baseUrl ?? config('violations.route_prefix', 'violations');

            Route::prefix($baseUrl)
                ->withoutMiddleware(ValidateCsrfToken::class)
                ->group(function () use ($baseUrl) {
                    // CSP2 report-uri endpoint (application/csp-report)
                    Route::options('csp', [ViolationController::class, 'options'])
                        ->name($baseUrl.'.csp.options');
                    Route::post('csp', [ViolationController::class, 'csp'])
                        ->name($baseUrl.'.csp');

                    // Modern report-to endpoint (application/reports+json) for CSP3, NEL, etc.
                    Route::options('reports', [ViolationController::class, 'options'])
                        ->name($baseUrl.'.reports.options');
                    Route::post('reports', [ViolationController::class, 'reports'])
                        ->name($baseUrl.'.reports');
                });
        });
    }

    public function packageBooted(): void
    {
        // Register a scheduled task to queue unforwarded violations for forwarding
        $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
            // Only schedule the task if database storage is enabled
            if (config('violations.table')) {
                $schedule->command('violations:queue')
                    ->hourly()
                    ->name('queue-violations')
                    ->withoutOverlapping()
                    ->runInBackground();
            }
        });
    }
}
