<?php

namespace Tests;

use Abather\LaravelPermissionGenerator\PermissionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionServiceProvider;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            PermissionServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('auth.providers.users.model', \Tests\Models\User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the static naming config cache so command calls inside tests
        // always read from the current application's config.
        $prop = new \ReflectionProperty(PermissionGenerator::class, 'namingConfig');
        $prop->setValue(null, null);
    }
}
