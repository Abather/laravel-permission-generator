<?php

namespace Abather\LaravelPermissionGenerator\Commands;

use Illuminate\Console\GeneratorCommand;

class CreateGuardPermissionConfigFileCommand extends GeneratorCommand
{
    protected $signature = 'permission:guard-config {guard : The name of the guard}';

    protected $description = 'Create a config file for the given guard under config/laravelPermissionGaurds/';

    protected $type = 'Config';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/guard.config.stub');
    }

    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__."/../../$stub";
    }

    protected function getNameInput(): string
    {
        return trim($this->argument('guard'));
    }

    protected function getPath($name): string
    {
        $guardsPath = config('laravel-permission-generator.guards_path', 'laravelPermissionGaurds');

        return $this->laravel->configPath($guardsPath.'/'.$this->getNameInput().'.php');
    }

    protected function buildClass($name): string
    {
        return $this->files->get($this->getStub());
    }
}
