<?php

namespace Synchro\Violation\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Synchro\Violation\ViolationServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Synchro\\Violation\\Database\\Factories\\'.class_basename($modelName).'Factory',
        );
        $this->artisan('vendor:publish', [
            '--provider' => 'Spatie\LaravelData\LaravelDataServiceProvider',
            '--tag' => 'data-config',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ViolationServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        Config::set('database.default', 'testing');
        Config::set('violations.table', 'violations');
        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }
}
