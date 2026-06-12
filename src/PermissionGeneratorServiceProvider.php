<?php

namespace Abather\LaravelPermissionGenerator;

use Abather\LaravelPermissionGenerator\Commands\CreateGuardPermissionConfigFileCommand;
use Abather\LaravelPermissionGenerator\Commands\GeneratePermissionCommand;
use Illuminate\Support\ServiceProvider;

class PermissionGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-permission-generator.php', 'laravel-permission-generator');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            GeneratePermissionCommand::class,
            CreateGuardPermissionConfigFileCommand::class,
        ]);
    }

    /**
     * Register the package's publishable resources.
     */
    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-permission-generator.php' => base_path('config/laravel-permission-generator.php'),
            ], 'config');
        }
    }
}
